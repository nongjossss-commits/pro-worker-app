<?php

namespace Tests\Feature\Production;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\FinancialPayment;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionItemPayment;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-07 individual collection, one screen per round:
 *  - save once → each person's entry + ONE Payment for the total, dated the
 *    day the money was received;
 *  - correcting a person later moves that Payment (and the bill) with it;
 *  - once the Payment's receipt / tax invoice is issued it can't be changed;
 *  - a lump-sum bill's payments behave as before.
 */
class IndividualCollectionRoundTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FinancialTransaction $bill;
    private array $items = [];

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);

        $employer = Employer::factory()->create();
        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => WorkType::create(['name' => 'Job', 'slug' => 'job-' . uniqid()])->id,
            'status' => 'active',
        ]);
        foreach (range(1, 3) as $i) {
            $this->items[] = ProductionItem::create([
                'production_order_id' => $order->id,
                'employee_id' => Employee::factory()->create(['employer_id' => $employer->id])->id,
                'status' => 'pending',
            ]);
        }
        $group = $order->financialGroups()->create(['name' => 'G', 'financial_data' => ['pricing_mode' => 'per_head']]);
        $this->bill = $group->transactions()->create([
            'production_order_id' => $order->id, 'amount' => 1500, 'type' => 'installment',
            'status' => 'pending', 'payment_collection_mode' => 'per_employee',
        ]);
        $this->bill->items()->attach(collect($this->items)->pluck('id'));
        $this->actingAs($this->user);
    }

    private function saveRound(array $amounts, string $date = '2026-11-05', ?int $bank = null)
    {
        $rows = [];
        foreach ($amounts as $i => $amount) {
            $rows[] = ['production_item_id' => $this->items[$i]->id, 'amount' => $amount, 'notes' => $i === 0 ? 'cash' : null];
        }

        return $this->postJson("/production/transactions/{$this->bill->id}/item-payments/round", [
            'paid_at' => $date, 'bank_account_id' => $bank, 'rows' => $rows,
        ]);
    }

    private function bankAccount(): BankAccount
    {
        return BankAccount::create(['bank_name' => 'Test Bank', 'account_number' => '123', 'account_name' => 'Co', 'is_active' => true]);
    }

    public function test_saving_a_round_creates_one_payment_for_the_total(): void
    {
        $res = $this->saveRound([100, 250])->assertOk()->json();

        $this->assertEquals(350, $res['payment_amount']);
        $this->assertSame(1, $this->bill->payments()->count());
        $payment = $this->bill->payments()->first();
        $this->assertEquals(350, $payment->amount);
        $this->assertSame('2026-11-05', $payment->paid_at->toDateString(), 'the day the money was received');
        $this->assertSame(2, FinancialTransactionItemPayment::where('financial_payment_id', $payment->id)->count());
        $this->assertSame('cash', FinancialTransactionItemPayment::where('production_item_id', $this->items[0]->id)->value('notes'));

        $bill = $this->bill->fresh();
        $this->assertEquals(350, $bill->paid_amount);
        $this->assertSame('partial', $bill->status);

        // next round = a second Payment; the whole bill → paid
        $this->saveRound([400, 250, 500], '2026-11-06')->assertOk();
        $this->assertSame(2, $this->bill->payments()->count());
        $this->assertEquals(1500, $this->bill->fresh()->paid_amount);
        $this->assertSame('paid', $this->bill->fresh()->status);
    }

    public function test_a_round_posts_to_the_ledger_on_the_received_date_when_an_account_is_chosen(): void
    {
        $bank = $this->bankAccount();
        $today = now()->toDateString();
        $this->saveRound([300], $today, $bank->id)->assertOk();

        $entry = $this->bill->payments()->first()->ledgerEntry()->first();
        $this->assertNotNull($entry);
        $this->assertEquals(300, $entry->gross_amount);
        $this->assertSame($today, \Illuminate\Support\Carbon::parse($entry->entry_date)->toDateString());
    }

    public function test_a_closed_accounting_day_is_refused_and_nothing_is_saved(): void
    {
        $bank = $this->bankAccount();
        $this->saveRound([300], now()->subDays(5)->toDateString(), $bank->id)->assertStatus(423);

        $this->assertSame(0, $this->bill->itemPayments()->count());
        $this->assertSame(0, $this->bill->payments()->count());
        $this->assertEquals(0, (float) $this->bill->fresh()->paid_amount);
    }

    public function test_earlier_entries_not_combined_yet_join_the_round(): void
    {
        $this->bill->itemPayments()->create(['production_item_id' => $this->items[2]->id, 'amount' => 50, 'paid_at' => '2026-11-01']);

        $this->saveRound([100])->assertOk();

        $this->assertEquals(150, $this->bill->payments()->first()->amount);
        $this->assertSame(0, $this->bill->itemPayments()->whereNull('financial_payment_id')->count());
    }

    public function test_more_than_the_bill_or_someone_not_on_it_is_refused_and_nothing_is_saved(): void
    {
        $this->saveRound([1000, 600])->assertStatus(422);
        $this->assertSame(0, $this->bill->itemPayments()->count());
        $this->assertSame(0, $this->bill->payments()->count());

        $this->postJson("/production/transactions/{$this->bill->id}/item-payments/round", [
            'paid_at' => '2026-11-05', 'rows' => [['production_item_id' => 999999, 'amount' => 10]],
        ])->assertStatus(422);
    }

    public function test_first_money_collected_decides_the_mode_whatever_was_picked_at_creation(): void
    {
        // created as lump sum, first collected individually
        $this->bill->update(['payment_collection_mode' => 'lump_sum']);
        $this->saveRound([100])->assertOk();
        $this->assertSame('per_employee', $this->bill->fresh()->payment_collection_mode);
        $this->assertEquals(100, $this->bill->fresh()->paid_amount);

        // remembered: a lump sum is refused now, nothing recorded
        $this->postJson("/production/transactions/{$this->bill->id}/payments", ['amount' => 50, 'paid_at' => now()->toDateString()])->assertStatus(422);
        $this->assertSame(1, $this->bill->payments()->count());

        // everything deleted → free to choose again
        $this->deleteJson('/production/payments/' . $this->bill->payments()->first()->id)->assertOk();
        $this->assertSame(0, $this->bill->itemPayments()->count());

        // created as individual (still marked so), first collected as a lump sum
        $this->postJson("/production/transactions/{$this->bill->id}/payments", ['amount' => 200, 'paid_at' => now()->toDateString()])->assertOk();
        $this->assertSame('lump_sum', $this->bill->fresh()->payment_collection_mode);

        // remembered the other way too
        $this->saveRound([10])->assertStatus(422);
        $this->postJson("/production/transactions/{$this->bill->id}/item-payments", ['production_item_id' => $this->items[0]->id, 'amount' => 10, 'paid_at' => now()->toDateString()])->assertStatus(422);
        $this->assertSame(0, $this->bill->itemPayments()->count());
        $this->assertEquals(200, $this->bill->fresh()->paid_amount);
    }

    public function test_a_refused_first_collection_does_not_change_the_mode(): void
    {
        $this->bill->update(['payment_collection_mode' => 'lump_sum']);
        $bank = $this->bankAccount();

        $this->saveRound([300], now()->subDays(5)->toDateString(), $bank->id)->assertStatus(423); // closed day
        $this->saveRound([1000, 600])->assertStatus(422); // more than the bill

        $this->assertSame('lump_sum', $this->bill->fresh()->payment_collection_mode);
        $this->assertSame(0, $this->bill->itemPayments()->count());
    }

    public function test_correcting_a_person_moves_the_payment_and_the_bill(): void
    {
        $bank = $this->bankAccount();
        $today = now()->toDateString();
        $this->saveRound([100, 250], $today, $bank->id)->assertOk();
        $payment = $this->bill->payments()->first();
        $entry = FinancialTransactionItemPayment::where('production_item_id', $this->items[0]->id)->first();

        $this->putJson("/production/item-payments/{$entry->id}", ['amount' => 150, 'paid_at' => $today])->assertOk();
        $this->assertEquals(400, $payment->fresh()->amount);
        $this->assertEquals(400, $this->bill->fresh()->paid_amount);
        $this->assertEquals(400, $payment->ledgerEntry()->first()->gross_amount);

        // correcting past the bill's worth is refused
        $this->putJson("/production/item-payments/{$entry->id}", ['amount' => 1300, 'paid_at' => $today])->assertStatus(422);
        $this->assertEquals(150, $entry->fresh()->amount);

        // removing a person lowers it; removing the last removes the Payment
        $this->deleteJson("/production/item-payments/{$entry->id}")->assertOk();
        $this->assertEquals(250, $payment->fresh()->amount);
        $other = FinancialTransactionItemPayment::where('financial_payment_id', $payment->id)->first();
        $this->deleteJson("/production/item-payments/{$other->id}")->assertOk();
        $this->assertNull(FinancialPayment::find($payment->id));
        $this->assertEquals(0, $this->bill->fresh()->paid_amount);
        $this->assertSame('pending', $this->bill->fresh()->status);
    }

    public function test_the_total_of_a_round_is_not_edited_directly(): void
    {
        $this->saveRound([100])->assertOk();
        $payment = $this->bill->payments()->first();

        $this->putJson("/production/payments/{$payment->id}", ['amount' => 999, 'paid_at' => '2026-11-05'])->assertStatus(422);
        $this->assertEquals(100, $payment->fresh()->amount);
    }

    public function test_issued_document_locks_the_round(): void
    {
        $this->saveRound([100, 250])->assertOk();
        $payment = $this->bill->payments()->first();
        $entry = FinancialTransactionItemPayment::where('financial_payment_id', $payment->id)->first();
        $amountBefore = $entry->amount;
        $payment->update(['receipt_generated_at' => now()]);

        $this->putJson("/production/item-payments/{$entry->id}", ['amount' => 1, 'paid_at' => '2026-11-05'])->assertStatus(422);
        $this->deleteJson("/production/item-payments/{$entry->id}")->assertStatus(422);
        $this->deleteJson("/production/payments/{$payment->id}")->assertStatus(422);
        $this->deleteJson("/production/transactions/{$this->bill->id}")->assertStatus(422);

        $this->assertEquals($amountBefore, $entry->fresh()->amount);
        $this->assertEquals(350, $payment->fresh()->amount);
        $this->assertEquals(350, $this->bill->fresh()->paid_amount);

        // a round with no document is still free to change
        $this->saveRound([50], '2026-11-06')->assertOk();
        $open = FinancialTransactionItemPayment::whereNotIn('financial_payment_id', [$payment->id])->first();
        $this->putJson("/production/item-payments/{$open->id}", ['amount' => 60, 'paid_at' => '2026-11-06'])->assertOk();
    }

    public function test_lump_sum_payments_keep_their_behaviour(): void
    {
        $this->bill->update(['payment_collection_mode' => 'lump_sum']);
        $this->postJson("/production/transactions/{$this->bill->id}/payments", ['amount' => 200, 'paid_at' => '2026-11-05'])->assertOk();
        $payment = $this->bill->payments()->first();
        $payment->update(['receipt_generated_at' => now()]);

        // the new lock is for individually collected rounds only — decision pending for lump sum
        $this->putJson("/production/payments/{$payment->id}", ['amount' => 300, 'paid_at' => '2026-11-05'])->assertOk();
        $this->assertEquals(300, $payment->fresh()->amount);
    }

    public function test_popup_markup_is_wired(): void
    {
        $blade = file_get_contents(resource_path('views/production/partials/financial-tab.blade.php'));
        $js = file_get_contents(public_path('js/financial-manager.js'));

        $this->assertStringContainsString('saveIndividualRound()', $blade);
        $this->assertStringContainsString('roundEntries(pay.id)', $blade);
        $this->assertStringContainsString('name="ic_round_date"', $blade, 'date box needs a name for date-form-sync');
        $this->assertStringNotContainsString("item.title_en + ' ' + item.name_en", $blade, 'no "null NAME"');
        $this->assertStringContainsString('/item-payments/round', $js);

        // issuing a document: one click on the card, no menu cut off by a scrolling box
        $rounds = substr($blade, strpos($blade, 'class="ic-rounds"'), 6000);
        $this->assertStringNotContainsString('dropdown-toggle', $rounds);
        $this->assertStringContainsString("generatePaymentDocument(pay.id, 'tax_invoice')", $rounds);
        $this->assertStringContainsString('data-bs-popper-config', $blade, 'lump-sum Doc menu escapes its list');
        $css = file_get_contents(public_path('css/large-modals.css'));
        $this->assertStringNotContainsString('.ic-rounds { max-height', $css);
        $this->assertStringNotContainsString('itemPaymentDraft', $js);
    }
}

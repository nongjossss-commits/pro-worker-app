<?php

namespace Tests\Feature\Production;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\FinancialPayment;
use App\Models\FinancialTransaction;
use App\Models\LedgerEntry;
use App\Models\ProductionFinancialGroup;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-21 — a per-head price can change even after the person was paid
 * for. When the bill then holds more money than it is worth, Update asks
 * (422 code "overpaid") and the operator picks: keep as income / refund /
 * move to another bill of the employer / count toward others on this bill.
 * Every way keeps: payments sum == paid_amount <= bill worth, and (individual
 * collection) per-person entries sum == payments sum, each person <= price.
 */
class BillOverpaymentTest extends TestCase
{
    use RefreshDatabase;

    private Employer $employer;
    private ProductionOrder $order;
    private ProductionFinancialGroup $group;
    /** @var int[] */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);

        $this->employer = Employer::factory()->create();
        $this->order = ProductionOrder::create([
            'employer_id' => $this->employer->id,
            'work_type_id' => WorkType::create(['name' => 'Job', 'slug' => 'job-' . uniqid()])->id,
            'status' => 'active',
        ]);
        foreach (['PERSON A', 'PERSON B'] as $name) {
            $this->ids[] = ProductionItem::create([
                'production_order_id' => $this->order->id,
                'employee_id' => Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => $name])->id,
                'status' => 'pending',
            ])->id;
        }
        $this->group = $this->order->financialGroups()->create(['name' => 'G', 'financial_data' => [
            'pricing_mode' => 'per_head',
            'pricing_tiers' => [
                ['price' => 6500, 'count' => 2, 'item_ids' => $this->ids],
                ['price' => 5000, 'count' => 0, 'item_ids' => []],
                ['price' => 8000, 'count' => 0, 'item_ids' => []],
            ],
        ]]);
    }

    private function bill(): FinancialTransaction
    {
        $id = $this->postJson("/production/{$this->order->id}/transactions", [
            'amount' => 13000, 'type' => 'installment', 'financial_group_id' => $this->group->id, 'item_ids' => $this->ids,
        ])->assertOk()->json('transaction.id');

        return FinancialTransaction::findOrFail($id);
    }

    private function bank(): BankAccount
    {
        return BankAccount::create(['bank_name' => 'Test Bank', 'account_number' => '123', 'account_name' => 'Co', 'is_active' => true]);
    }

    private function payLump(FinancialTransaction $bill, float $amount): void
    {
        $this->postJson("/production/transactions/{$bill->id}/payments", ['amount' => $amount, 'paid_at' => now()->toDateString()])->assertOk();
    }

    private function payEach(FinancialTransaction $bill, array $rows): void
    {
        $this->postJson("/production/transactions/{$bill->id}/item-payments/round", [
            'paid_at' => now()->toDateString(),
            'rows' => collect($rows)->map(fn ($amt, $id) => ['production_item_id' => $id, 'amount' => $amt])->values()->all(),
        ])->assertOk();
    }

    /** Person A to the 5,000 tier — what Update sends (status untouched). */
    private function lowerA(FinancialTransaction $bill, array $extra = [])
    {
        return $this->post("/production/transactions/{$bill->id}", array_merge([
            '_method' => 'PUT', 'sync_items' => 1, 'item_ids' => $this->ids, 'amount' => 11500,
            'item_tiers_json' => json_encode([$this->ids[0] => ['tier' => 1, 'price' => 5000]]),
        ], $extra), ['Accept' => 'application/json']);
    }

    private function assertBooksBalance(FinancialTransaction $bill): void
    {
        $bill = $bill->fresh();
        $sum = round((float) FinancialPayment::where('financial_transaction_id', $bill->id)->sum('amount'), 2);
        $this->assertEqualsWithDelta($sum, (float) $bill->paid_amount, 0.001, 'paid_amount == sum of payments');
        $worth = (float) $bill->amount - (float) $bill->discount_amount - (float) $bill->credit_amount;
        $this->assertLessThanOrEqual($worth + 0.005, (float) $bill->paid_amount, 'never more money than the bill is worth');
        if ($bill->payment_collection_mode === 'per_employee') {
            $this->assertEqualsWithDelta($sum, (float) $bill->itemPayments()->sum('amount'), 0.001, 'entries add up to the payments');
            foreach ($bill->items as $item) {
                $this->assertLessThanOrEqual((float) $item->pivot->unit_price + 0.005, (float) $bill->itemPayments()->where('production_item_id', $item->id)->sum('amount'));
            }
        }
    }

    public function test_lowering_a_paid_price_asks_what_to_do_and_keep_adds_an_income_line(): void
    {
        $bill = $this->bill();
        $this->payLump($bill, 13000);

        $ask = $this->lowerA($bill)->assertStatus(422)->assertJson(['code' => 'overpaid', 'excess' => 1500]);
        $this->assertSame(13000.0, (float) $bill->fresh()->amount, 'nothing saved until a choice is made');
        $this->assertSame(6500.0, (float) $bill->fresh()->items->firstWhere('id', $this->ids[0])->pivot->unit_price);
        $this->assertFalse($ask->json('can_reallocate'));

        $this->lowerA($bill, ['overpayment_action' => 'keep', 'overpayment_note' => 'Kept'])->assertOk();
        $bill->refresh();
        $this->assertSame(5000.0, (float) $bill->items->firstWhere('id', $this->ids[0])->pivot->unit_price, 'the new price is saved');
        $this->assertSame(13000.0, (float) $bill->amount, '11,500 + 1,500 kept');
        $this->assertSame([['id' => $bill->extra_charges[0]['id'], 'description' => 'Kept', 'amount' => 1500, 'kind' => 'overpayment']], $bill->extra_charges);
        $this->assertSame('paid', $bill->status);
        $this->assertBooksBalance($bill);
    }

    public function test_refund_records_money_paid_back_in_the_books_and_can_be_undone(): void
    {
        $bill = $this->bill();
        $this->payLump($bill, 13000);
        $bank = $this->bank();

        $this->lowerA($bill, ['overpayment_action' => 'refund', 'overpayment_date' => now()->toDateString(), 'overpayment_bank_account_id' => $bank->id])->assertOk();
        $bill->refresh();
        $refund = $bill->payments()->where('kind', 'refund')->firstOrFail();
        $this->assertSame(-1500.0, (float) $refund->amount);
        $this->assertSame(11500.0, (float) $bill->amount);
        $this->assertSame(11500.0, (float) $bill->paid_amount);
        $this->assertSame('paid', $bill->status);
        $entry = LedgerEntry::where('source_type', FinancialPayment::class)->where('source_id', $refund->id)->firstOrFail();
        $this->assertSame('expense', $entry->type);
        $this->assertSame(1500.0, (float) $entry->gross_amount);
        $this->assertBooksBalance($bill);

        // system rows: no edit, no receipt; delete = undo
        $this->postJson("/production/payments/{$refund->id}", ['amount' => 1, 'paid_at' => now()->toDateString()])->assertStatus(422);
        $this->deleteJson("/production/payments/{$refund->id}")->assertOk();
        $this->assertSame(13000.0, (float) $bill->fresh()->paid_amount);
        $this->assertNull(LedgerEntry::where('source_type', FinancialPayment::class)->where('source_id', $refund->id)->first());
        // the bill is overpaid again → the next Update asks again
        $this->lowerA($bill)->assertStatus(422)->assertJson(['code' => 'overpaid', 'excess' => 1500]);
    }

    public function test_transfer_moves_the_extra_onto_another_bill_of_the_employer(): void
    {
        $bill = $this->bill();
        $this->payLump($bill, 13000);
        $other = FinancialTransaction::create([
            'production_order_id' => $this->order->id, 'production_financial_group_id' => $this->group->id,
            'type' => 'installment', 'amount' => 5000, 'status' => 'pending',
        ]);

        $targets = $this->lowerA($bill)->assertStatus(422)->json('targets');
        $this->assertSame([$other->id], array_column($targets, 'id'));

        $this->lowerA($bill, ['overpayment_action' => 'transfer', 'overpayment_target_id' => $other->id])
            ->assertOk()->assertJsonPath('other_transactions.0.id', $other->id);
        $this->assertSame(11500.0, (float) $bill->fresh()->paid_amount);
        $this->assertSame(1500.0, (float) $other->fresh()->paid_amount);
        $this->assertSame('partial', $other->fresh()->status);
        $this->assertSame(0, LedgerEntry::count(), 'no bank movement for a transfer');
        $this->assertBooksBalance($bill);
        $this->assertBooksBalance($other);

        // a bill with a transfer can't be deleted; undoing either half undoes both
        $this->deleteJson("/production/transactions/{$other->id}")->assertStatus(422);
        $in = $other->payments()->where('kind', 'transfer_in')->firstOrFail();
        $this->deleteJson("/production/payments/{$in->id}")->assertOk();
        $this->assertSame(0, FinancialPayment::whereIn('kind', ['transfer_in', 'transfer_out'])->count());
        $this->assertSame(13000.0, (float) $bill->fresh()->paid_amount);
        $this->assertSame(0.0, (float) $other->fresh()->paid_amount);
        $this->assertSame('pending', $other->fresh()->status);
    }

    public function test_transfer_target_must_owe_enough(): void
    {
        $bill = $this->bill();
        $this->payLump($bill, 13000);
        $small = FinancialTransaction::create([
            'production_order_id' => $this->order->id, 'production_financial_group_id' => $this->group->id,
            'type' => 'installment', 'amount' => 1000, 'status' => 'pending',
        ]);
        $this->lowerA($bill, ['overpayment_action' => 'transfer', 'overpayment_target_id' => $small->id])->assertStatus(422)->assertJson(['code' => 'overpaid']);
        $this->assertSame(0, FinancialPayment::whereNotNull('kind')->count());
        $this->assertSame(13000.0, (float) $bill->fresh()->amount, 'nothing saved');
    }

    public function test_individual_collection_refund_takes_it_off_that_person(): void
    {
        $bill = $this->bill();
        $this->payEach($bill, [$this->ids[0] => 6500, $this->ids[1] => 6500]);

        $ask = $this->lowerA($bill)->assertStatus(422)->json();
        $this->assertSame(1500, $ask['excess']);
        $this->assertSame('PERSON A', $ask['lines'][0]['name']);

        $this->lowerA($bill, ['overpayment_action' => 'refund', 'overpayment_date' => now()->toDateString()])->assertOk();
        $bill->refresh();
        $this->assertSame(5000.0, (float) $bill->itemPayments()->where('production_item_id', $this->ids[0])->sum('amount'));
        $this->assertSame(6500.0, (float) $bill->itemPayments()->where('production_item_id', $this->ids[1])->sum('amount'));
        $this->assertSame(0, LedgerEntry::count(), 'no bank account picked — nothing posted');
        $this->assertBooksBalance($bill);

        // its per-person entry is part of the refund — can't be edited on its own
        $neg = $bill->itemPayments()->where('amount', '<', 0)->firstOrFail();
        $this->deleteJson("/production/item-payments/{$neg->id}")->assertStatus(422);
    }

    public function test_individual_collection_reallocate_counts_it_toward_someone_who_still_owes(): void
    {
        $bill = $this->bill();
        $this->payEach($bill, [$this->ids[0] => 6500]); // B has not paid

        $ask = $this->lowerA($bill)->assertStatus(422)->json();
        $this->assertTrue($ask['can_reallocate']);

        $this->lowerA($bill, ['overpayment_action' => 'reallocate'])->assertOk();
        $bill->refresh();
        $this->assertSame(11500.0, (float) $bill->amount);
        $this->assertSame(6500.0, (float) $bill->paid_amount, 'no money moved in or out');
        $this->assertSame(5000.0, (float) $bill->itemPayments()->where('production_item_id', $this->ids[0])->sum('amount'));
        $this->assertSame(1500.0, (float) $bill->itemPayments()->where('production_item_id', $this->ids[1])->sum('amount'));
        $this->assertSame('partial', $bill->status);
        $this->assertBooksBalance($bill);
    }

    public function test_individual_collection_keep_moves_the_extra_onto_the_income_line_and_undo_removes_it(): void
    {
        $bill = $this->bill();
        $this->payEach($bill, [$this->ids[0] => 6500, $this->ids[1] => 6500]);

        $this->lowerA($bill, ['overpayment_action' => 'keep'])->assertOk();
        $bill->refresh();
        $line = $bill->extra_charges[0];
        $this->assertSame('overpayment', $line['kind']);
        $this->assertSame(13000.0, (float) $bill->amount);
        $this->assertSame(1500.0, (float) $bill->itemPayments()->where('extra_charge_id', $line['id'])->sum('amount'));
        $this->assertSame('paid', $bill->status);
        $this->assertBooksBalance($bill);

        // the kept line can't be collected again
        $this->postJson("/production/transactions/{$bill->id}/item-payments/round", [
            'paid_at' => now()->toDateString(), 'rows' => [['extra_charge_id' => $line['id'], 'amount' => 1]],
        ])->assertStatus(422);

        $adj = $bill->payments()->where('kind', 'adjustment')->firstOrFail();
        $this->deleteJson("/production/payments/{$adj->id}")->assertOk();
        $bill->refresh();
        $this->assertNull($bill->extra_charges, 'undo takes the kept line off again');
        $this->assertSame(11500.0, (float) $bill->amount);
        $this->assertSame(6500.0, (float) $bill->itemPayments()->where('production_item_id', $this->ids[0])->sum('amount'));
    }

    public function test_raising_a_paid_price_reopens_the_bill(): void
    {
        $bill = $this->bill();
        $this->payLump($bill, 13000);
        $this->assertSame('paid', $bill->fresh()->status);

        $this->post("/production/transactions/{$bill->id}", [
            '_method' => 'PUT', 'sync_items' => 1, 'item_ids' => $this->ids, 'amount' => 14500,
            'item_tiers_json' => json_encode([$this->ids[0] => ['tier' => 2, 'price' => 8000]]),
        ], ['Accept' => 'application/json'])->assertOk();

        $bill->refresh();
        $this->assertSame(8000.0, (float) $bill->items->firstWhere('id', $this->ids[0])->pivot->unit_price);
        $this->assertSame('partial', $bill->status, '1,500 is owed again');
        $this->assertSame(0, FinancialPayment::whereNotNull('kind')->count());
    }

    public function test_no_receipt_for_a_refund(): void
    {
        $bill = $this->bill();
        $this->payLump($bill, 13000);
        $this->lowerA($bill, ['overpayment_action' => 'refund', 'overpayment_date' => now()->toDateString()])->assertOk();
        $refund = $bill->payments()->where('kind', 'refund')->firstOrFail();
        $this->get("/production/{$this->order->id}/documents/payment/{$refund->id}/receipt")->assertStatus(422);
    }

    public function test_quick_pay_panel_is_the_bills_finance_panel(): void
    {
        $bill = $this->bill();
        $html = $this->get(route('production.transactions.panel', $bill->id))->assertOk()->getContent();
        $this->assertStringContainsString('Payment History & Details', $html);
        $this->assertStringContainsString('"id":' . $bill->id, html_entity_decode($html));
    }
}

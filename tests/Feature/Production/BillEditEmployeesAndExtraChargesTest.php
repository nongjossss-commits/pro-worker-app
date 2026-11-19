<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\FinancialTransaction;
use App\Models\ProductionFinancialGroup;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-20 — Payment History & Details:
 *  - Edit Employees: an employee ticked off leaves the installment on Update
 *    (back to "not billed yet", tier price editable, can be added again at the
 *    then-current price); "Change price tier" keeps them at another tier's
 *    price; someone already paid for on the bill can't be taken off.
 *  - ค่าใช้จ่ายเพิ่มเติม: each one its own line (extra_charges) — on the printed
 *    bill and as a row in individual collection; nothing is lost when the
 *    employee list is changed; can't be removed once money was received.
 */
class BillEditEmployeesAndExtraChargesTest extends TestCase
{
    use RefreshDatabase;

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

        $employer = Employer::factory()->create();
        $this->order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => WorkType::create(['name' => 'Job', 'slug' => 'job-' . uniqid()])->id,
            'status' => 'active',
        ]);
        foreach (['PHYO LAI LAI KHAING', 'SECOND PERSON', 'THIRD PERSON'] as $name) {
            $this->ids[] = ProductionItem::create([
                'production_order_id' => $this->order->id,
                'employee_id' => Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => $name])->id,
                'status' => 'pending',
            ])->id;
        }
        $this->group = $this->order->financialGroups()->create(['name' => 'G', 'financial_data' => [
            'pricing_mode' => 'per_head',
            'pricing_tiers' => [
                ['price' => 6500, 'count' => 3, 'item_ids' => $this->ids, 'note' => 'Passport'],
                ['price' => 5000, 'count' => 0, 'item_ids' => [], 'note' => 'No passport'],
            ],
        ]]);
    }

    private function bill(array $itemIds): FinancialTransaction
    {
        $id = $this->postJson("/production/{$this->order->id}/transactions", [
            'amount' => 6500 * count($itemIds), 'type' => 'installment', 'financial_group_id' => $this->group->id, 'item_ids' => $itemIds,
        ])->assertOk()->json('transaction.id');

        return FinancialTransaction::findOrFail($id);
    }

    /** What the Update button sends (FormData → plain fields here). */
    private function update(FinancialTransaction $bill, array $fields)
    {
        return $this->post("/production/transactions/{$bill->id}", array_merge(['_method' => 'PUT', 'sync_items' => 1], $fields), ['Accept' => 'application/json']);
    }

    public function test_ticked_off_employee_leaves_the_installment_and_can_come_back_at_the_current_price(): void
    {
        $bill = $this->bill([$this->ids[0], $this->ids[1]]);

        $this->update($bill, ['item_ids' => [$this->ids[1]], 'amount' => 6500])->assertOk();
        $this->assertSame([$this->ids[1]], $bill->fresh()->items->pluck('id')->all(), 'PHYO is off the bill');
        $this->assertSame(0, DB::table('financial_transaction_items')->where('production_item_id', $this->ids[0])->count(), 'not billed anywhere — back to "not billed yet"');

        // their tier price can now be changed; adding them again takes the new price
        $data = $this->group->fresh()->financial_data;
        $data['pricing_tiers'][0]['item_ids'] = [$this->ids[1], $this->ids[2]];
        $data['pricing_tiers'][1]['item_ids'] = [$this->ids[0]];
        $this->group->update(['financial_data' => $data]);

        $this->update($bill, ['item_ids' => [$this->ids[1], $this->ids[0]], 'amount' => 11500])->assertOk();
        $prices = $bill->fresh()->items->mapWithKeys(fn ($i) => [$i->id => (float) $i->pivot->unit_price])->all();
        $this->assertSame(5000.0, $prices[$this->ids[0]]);
        $this->assertSame(6500.0, $prices[$this->ids[1]], 'the other one keeps the price they were billed at');
    }

    public function test_change_price_tier_keeps_the_employee_on_the_bill_at_the_new_price(): void
    {
        $bill = $this->bill([$this->ids[0], $this->ids[1]]);

        $this->update($bill, [
            'item_ids' => [$this->ids[0], $this->ids[1]],
            'amount' => 11500,
            'item_tiers_json' => json_encode([$this->ids[0] => ['tier' => 1, 'price' => 5000]]),
        ])->assertOk();

        $pivot = $bill->fresh()->items->firstWhere('id', $this->ids[0])->pivot;
        $this->assertSame(5000.0, (float) $pivot->unit_price);
        $tiers = $this->group->fresh()->financial_data['pricing_tiers'];
        $this->assertContains($this->ids[0], $tiers[1]['item_ids']);
        $this->assertNotContains($this->ids[0], $tiers[0]['item_ids']);

        // a price that is not the tier's saved price (unsaved settings) is refused
        $this->update($bill, [
            'item_ids' => [$this->ids[0], $this->ids[1]], 'amount' => 11500,
            'item_tiers_json' => json_encode([$this->ids[1] => ['tier' => 1, 'price' => 4000]]),
        ])->assertStatus(422);
    }

    public function test_someone_already_paid_for_cannot_be_taken_off_and_a_per_head_bill_keeps_one_employee(): void
    {
        $bill = $this->bill([$this->ids[0], $this->ids[1]]);
        $this->postJson("/production/transactions/{$bill->id}/item-payments/round", [
            'paid_at' => now()->toDateString(), 'rows' => [['production_item_id' => $this->ids[0], 'amount' => 1000]],
        ])->assertOk();

        $res = $this->update($bill, ['item_ids' => [$this->ids[1]], 'amount' => 6500])->assertStatus(422);
        $this->assertStringContainsString('PHYO LAI LAI KHAING', $res->json('message'));
        $this->assertCount(2, $bill->fresh()->items);

        $other = $this->bill([$this->ids[2]]);
        $this->update($other, ['amount' => 0])->assertStatus(422); // nobody left
        $this->assertCount(1, $other->fresh()->items);
    }

    public function test_additional_charges_are_their_own_lines_and_survive_employee_changes(): void
    {
        $bill = $this->bill([$this->ids[0], $this->ids[1]]);
        $charges = [['id' => 'xcar', 'description' => 'ค่ารถ', 'amount' => 300], ['id' => 'xfood', 'description' => 'ค่าอาหาร', 'amount' => 200]];

        $this->update($bill, ['item_ids' => [$this->ids[0], $this->ids[1]], 'amount' => 13500, 'extra_charges_json' => json_encode($charges), 'notes' => 'งวดแรก'])->assertOk();
        $fresh = $bill->fresh();
        $this->assertSame(500.0, $fresh->extraChargesTotal());
        $this->assertSame('งวดแรก', $fresh->notes, 'no "+ ค่ารถ" line in the notes any more');

        // the printed bill: one row per charge, rows add up to the total
        $html = $this->get("/production/{$this->order->id}/documents/invoice?transaction_ids={$bill->id}")->assertOk()->getContent();
        $this->assertStringContainsString('ค่ารถ', $html);
        $this->assertStringContainsString('ค่าอาหาร', $html);
        $this->assertStringContainsString('300.00', $html);
        $this->assertStringContainsString('13,000.00', $html); // 2 × 6,500 work rows

        // a total below the additional charges, or below what was received, is refused
        $this->update($bill, ['item_ids' => [$this->ids[0]], 'amount' => 400, 'extra_charges_json' => json_encode($charges)])->assertStatus(422);
    }

    public function test_individual_collection_takes_money_for_an_additional_charge(): void
    {
        $bill = $this->bill([$this->ids[0]]);
        $this->update($bill, ['item_ids' => [$this->ids[0]], 'amount' => 6800, 'extra_charges_json' => json_encode([['id' => 'xcar', 'description' => 'ค่ารถ', 'amount' => 300]])])->assertOk();

        $this->postJson("/production/transactions/{$bill->id}/item-payments/round", [
            'paid_at' => now()->toDateString(),
            'rows' => [['production_item_id' => $this->ids[0], 'amount' => 6500], ['extra_charge_id' => 'xcar', 'amount' => 300]],
        ])->assertOk();
        $fresh = $bill->fresh();
        $this->assertSame(6800.0, (float) $fresh->paid_amount);
        $this->assertSame('paid', $fresh->status);
        $this->assertSame(300.0, (float) $fresh->itemPayments()->where('extra_charge_id', 'xcar')->sum('amount'));

        // the charge can't be removed now
        $this->update($bill, ['item_ids' => [$this->ids[0]], 'amount' => 6500, 'extra_charges_json' => '[]'])->assertStatus(422);
    }

    public function test_additional_charge_rows_are_checked(): void
    {
        $bill = $this->bill([$this->ids[0]]);
        $this->update($bill, ['item_ids' => [$this->ids[0]], 'amount' => 6800, 'extra_charges_json' => json_encode([['id' => 'xcar', 'description' => 'ค่ารถ', 'amount' => 300]])])->assertOk();

        $this->postJson("/production/transactions/{$bill->id}/item-payments/round", [
            'paid_at' => now()->toDateString(), 'rows' => [['extra_charge_id' => 'xcar', 'amount' => 301]],
        ])->assertStatus(422);
        $this->postJson("/production/transactions/{$bill->id}/item-payments/round", [
            'paid_at' => now()->toDateString(), 'rows' => [['extra_charge_id' => 'nope', 'amount' => 10]],
        ])->assertStatus(422);
        $this->assertSame(0, $bill->fresh()->itemPayments()->count());
    }

    public function test_old_note_lines_move_into_additional_charges(): void
    {
        $bill = $this->bill([$this->ids[0]]);
        DB::table('financial_transactions')->where('id', $bill->id)->update(['notes' => "งวด 1\n+ ค่ารถตู้: ฿1,250.00\n+ ค่าอาหาร: ฿300.00", 'amount' => 8050]);

        (require database_path('migrations/2026_11_20_000000_add_extra_charges_to_financial_transactions.php'))->up();

        $fresh = $bill->fresh();
        $this->assertSame('งวด 1', $fresh->notes);
        $this->assertSame(['ค่ารถตู้', 'ค่าอาหาร'], collect($fresh->extra_charges)->pluck('description')->all());
        $this->assertSame(1550.0, $fresh->extraChargesTotal());
        $this->assertSame(8050.0, (float) $fresh->amount, 'amount unchanged — it already included them');
    }
}

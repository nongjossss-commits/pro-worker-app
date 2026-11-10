<?php

namespace Tests\Feature\Production;

use App\Models\Employer;
use App\Models\FinancialTransaction;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers two additions to the Finance popup:
 * - "Fixed Total" pricing can now hold several named line items (fixed_items),
 *   summed into fixed_base_amount for backward compatibility with older readers (e.g. quotation.blade.php).
 * - The Payment History modal's "ค่าใช้จ่ายเพิ่มเติม" (additional charge) button adds straight onto
 *   a transaction's existing amount/notes via the already-existing update endpoint — no new business logic.
 */
class FixedPriceItemsAndExtraChargeTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_saving_multiple_fixed_items_persists_and_sums_into_fixed_base_amount(): void
    {
        $user = $this->makeAdmin();
        $employer = Employer::factory()->create();
        $workType = WorkType::create(['name' => 'Fixed Job', 'slug' => 'fixed-job']);
        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'pre_production',
        ]);
        $group = $order->financialGroups()->create([
            'name' => 'Group 1',
            'financial_data' => ['pricing_mode' => 'fixed', 'fixed_base_amount' => 0],
        ]);

        $response = $this->actingAs($user)->putJson("/production/{$order->id}/financial-groups/{$group->id}", [
            'pricing_mode' => 'fixed',
            'fixed_base_amount' => 8000,
            'fixed_items' => [
                ['description' => 'ค่ารถ', 'amount' => 5000],
                ['description' => 'ค่าอาหารกลางวัน', 'amount' => 3000],
            ],
            'pricing_tiers' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $group->refresh();
        $this->assertEquals(8000, $group->financial_data['fixed_base_amount']);
        $this->assertCount(2, $group->financial_data['fixed_items']);
        $this->assertEquals('ค่ารถ', $group->financial_data['fixed_items'][0]['description']);
        $this->assertEquals(5000, $group->financial_data['fixed_items'][0]['amount']);
    }

    public function test_adding_an_extra_charge_bumps_transaction_amount_and_appends_notes(): void
    {
        $user = $this->makeAdmin();
        $employer = Employer::factory()->create();
        $workType = WorkType::create(['name' => 'Per Head Job', 'slug' => 'per-head-job']);
        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'pre_production',
        ]);
        $group = $order->financialGroups()->create([
            'name' => 'Group 1',
            'financial_data' => ['pricing_mode' => 'per_head'],
        ]);
        $transaction = $group->transactions()->create([
            'production_order_id' => $order->id,
            'amount' => 1500,
            'type' => 'installment',
            'status' => 'pending',
            'notes' => 'งวดที่ 1',
        ]);

        // Mirrors what addExtraCharge() does client-side before the user presses Update:
        // old amount + the extra charge, notes gets a new line.
        $response = $this->actingAs($user)->putJson("/production/transactions/{$transaction->id}", [
            'amount' => 1500 + 3000,
            'notes' => "งวดที่ 1\n+ ค่าอาหารกลางวัน: ฿3,000.00",
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $transaction->refresh();
        $this->assertEquals(4500, (float) $transaction->amount);
        $this->assertStringContainsString('ค่าอาหารกลางวัน', $transaction->notes);

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $transaction->id,
            'amount' => 4500,
        ]);
    }

    public function test_installment_built_from_several_fixed_items_stores_structured_line_items_and_document_splits_them(): void
    {
        $user = $this->makeAdmin();
        $employer = Employer::factory()->create();
        $workType = WorkType::create(['name' => 'Fixed Job 2', 'slug' => 'fixed-job-2']);
        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'pre_production',
        ]);
        $group = $order->financialGroups()->create([
            'name' => 'Group 1',
            'financial_data' => [
                'pricing_mode' => 'fixed',
                'fixed_base_amount' => 35000,
                'fixed_items' => [
                    ['id' => 'fx_a', 'description' => '46464', 'amount' => 20000],
                    ['id' => 'fx_b', 'description' => '000020', 'amount' => 15000],
                ],
            ],
        ]);

        // Mirrors what toggleFixedItemForNewTransaction() + addTransaction() build client-side
        // when both fixed items are picked for one installment.
        $response = $this->actingAs($user)->postJson("/production/{$order->id}/transactions", [
            'type' => 'installment',
            'amount' => 35000,
            'financial_group_id' => $group->id,
            'line_items' => [
                ['description' => '46464', 'amount' => 20000, 'fixed_item_id' => 'fx_a'],
                ['description' => '000020', 'amount' => 15000, 'fixed_item_id' => 'fx_b'],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $transactionId = $response->json('transaction.id');

        $this->assertDatabaseHas('financial_transactions', ['id' => $transactionId, 'amount' => 35000]);
        $transaction = FinancialTransaction::find($transactionId);
        $this->assertCount(2, $transaction->line_items);

        // Creating the installment must NOT remove the items from financial_data.fixed_items —
        // the Pricing tab "hiding" a used item is a pure frontend view (availableFixedItems),
        // never a data mutation, so the Grand Total Receivable (summed from every fixed item,
        // used or not) stays correct and the item's original data survives for the "reappears
        // on delete" behaviour.
        $group->refresh();
        $this->assertCount(2, $group->financial_data['fixed_items']);
        $this->assertEquals('46464', $transaction->line_items[0]['description']);
        $this->assertEquals(20000, $transaction->line_items[0]['amount']);
        $this->assertEquals('000020', $transaction->line_items[1]['description']);
        $this->assertEquals(15000, $transaction->line_items[1]['amount']);

        // The invoice must show each fixed item as its own line, not one combined "46464, 000020" row.
        $invoiceResponse = $this->actingAs($user)->get("/production/{$order->id}/documents/invoice?group_id={$group->id}&transaction_ids={$transactionId}");
        $invoiceResponse->assertStatus(200);
        $invoiceResponse->assertSee('46464');
        $invoiceResponse->assertSee('000020');
        $invoiceResponse->assertSee('20,000.00');
        $invoiceResponse->assertSee('15,000.00');
        $invoiceResponse->assertDontSee('46464, 000020');

        // Deleting the installment removes it outright — the fixed items it referenced become
        // available again purely because they're no longer attached to any live transaction
        // (usedFixedItemIds / availableFixedItems in financial-manager.js are derived from this.transactions,
        // so nothing extra needs releasing server-side).
        $deleteResponse = $this->actingAs($user)->deleteJson("/production/transactions/{$transactionId}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('financial_transactions', ['id' => $transactionId]);

        // financial_data.fixed_items still holds both items with their original description/amount
        // untouched — once the frontend refetches transactions, usedFixedItemIds no longer includes
        // fx_a/fx_b, so availableFixedItems (and the Pricing tab list) shows them again, editable.
        $group->refresh();
        $this->assertCount(2, $group->financial_data['fixed_items']);
        $this->assertEquals('fx_a', $group->financial_data['fixed_items'][0]['id']);
        $this->assertEquals(20000, $group->financial_data['fixed_items'][0]['amount']);
    }
}

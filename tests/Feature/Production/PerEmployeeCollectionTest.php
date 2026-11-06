<?php

namespace Tests\Feature\Production;

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
 * Per-employee bill collection (FinancialTransaction.payment_collection_mode
 * === 'per_employee') — each employee pays their own share, possibly over
 * several partial payments, which roll up into one receipt-worthy
 * FinancialPayment when the user finalizes a round. These tests also check
 * the existing lump-sum flow is completely unaffected.
 */
class PerEmployeeCollectionTest extends TestCase
{
    use RefreshDatabase;

    private function makeBill(int $perHeadPrice = 15000, int $employeeCount = 2): array
    {
        Role::findOrCreate('admin');
        $user = User::factory()->create();
        $user->assignRole('admin');

        $employer = Employer::factory()->create();
        $workType = WorkType::create(['name' => 'Test Job', 'slug' => 'test-job-' . uniqid()]);
        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'pre_production',
        ]);

        $items = [];
        $tierItemIds = [];
        for ($i = 0; $i < $employeeCount; $i++) {
            $employee = Employee::factory()->create(['employer_id' => $employer->id]);
            $item = ProductionItem::create([
                'production_order_id' => $order->id,
                'employee_id' => $employee->id,
                'status' => 'pending',
            ]);
            $items[] = $item;
            $tierItemIds[] = $item->id;
        }

        $group = $order->financialGroups()->create([
            'name' => 'Group 1',
            'financial_data' => [
                'pricing_mode' => 'per_head',
                'pricing_tiers' => [
                    ['price' => $perHeadPrice, 'count' => $employeeCount, 'item_ids' => $tierItemIds],
                ],
            ],
        ]);

        $transaction = $group->transactions()->create([
            'production_order_id' => $order->id,
            'amount' => $perHeadPrice * $employeeCount,
            'type' => 'installment',
            'status' => 'pending',
            'payment_collection_mode' => 'per_employee',
        ]);
        $transaction->items()->attach(collect($items)->pluck('id'));

        return compact('user', 'order', 'group', 'transaction', 'items');
    }

    public function test_new_transaction_defaults_to_lump_sum_mode(): void
    {
        ['transaction' => $transaction] = $this->makeBill();
        // makeBill() set it explicitly to 'per_employee' — check the raw
        // column default by creating a bare one the same way storeTransaction() would.
        $bare = FinancialTransaction::create([
            'production_order_id' => $transaction->production_order_id,
            'amount' => 100,
            'type' => 'installment',
            'status' => 'pending',
        ]);
        $this->assertSame('lump_sum', $bare->fresh()->payment_collection_mode);
    }

    public function test_store_item_payment_requires_item_attached_to_this_bill(): void
    {
        ['user' => $user, 'transaction' => $transaction, 'order' => $order] = $this->makeBill();

        $otherEmployee = Employee::factory()->create();
        $otherItem = ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => $otherEmployee->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments", [
            'production_item_id' => $otherItem->id,
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseCount('financial_transaction_item_payments', 0);
    }

    public function test_finalize_rolls_up_multiple_employees_into_one_receipt(): void
    {
        ['user' => $user, 'transaction' => $transaction, 'items' => $items] = $this->makeBill(15000, 2);

        // Employee A pays 5,000 of their 15,000.
        $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments", [
            'production_item_id' => $items[0]->id,
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
        ])->assertStatus(200)->assertJson(['success' => true]);

        // Employee B pays in full, 15,000.
        $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments", [
            'production_item_id' => $items[1]->id,
            'amount' => 15000,
            'paid_at' => now()->toDateString(),
        ])->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseCount('financial_payments', 0); // not rolled up yet
        $this->assertSame(0.0, (float) $transaction->fresh()->paid_amount);

        $response = $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments/finalize", []);
        $response->assertStatus(200)->assertJson(['success' => true, 'payment_amount' => 20000]);

        $this->assertDatabaseCount('financial_payments', 1); // exactly ONE combined receipt
        $payment = FinancialPayment::first();
        $this->assertEquals(20000, $payment->amount);

        $transaction->refresh();
        $this->assertEquals(20000, $transaction->paid_amount);
        $this->assertSame('partial', $transaction->status); // 20,000 of 30,000 total

        // Both entries are now linked to that one receipt.
        $this->assertSame(2, FinancialTransactionItemPayment::where('financial_payment_id', $payment->id)->count());

        // A second finalize with nothing new pending is a no-op, not a second receipt.
        $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments/finalize", [])
            ->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseCount('financial_payments', 1);
    }

    public function test_editing_a_finalized_entry_recalculates_its_receipt(): void
    {
        ['user' => $user, 'transaction' => $transaction, 'items' => $items] = $this->makeBill(15000, 2);

        $entryA = $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments", [
            'production_item_id' => $items[0]->id, 'amount' => 5000, 'paid_at' => now()->toDateString(),
        ])->json('entry');
        $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments", [
            'production_item_id' => $items[1]->id, 'amount' => 15000, 'paid_at' => now()->toDateString(),
        ]);
        $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments/finalize", []);

        $payment = FinancialPayment::first();
        $this->assertEquals(20000, $payment->amount);

        // Correct employee A's entry: they actually only paid 4,000, not 5,000.
        $response = $this->actingAs($user)->putJson("/production/item-payments/{$entryA['id']}", [
            'amount' => 4000, 'paid_at' => now()->toDateString(),
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);

        $payment->refresh();
        $this->assertEquals(19000, $payment->amount); // 4,000 + 15,000
        $this->assertEquals(19000, $transaction->fresh()->paid_amount);
    }

    public function test_deleting_the_last_entry_in_a_round_removes_its_receipt(): void
    {
        ['user' => $user, 'transaction' => $transaction, 'items' => $items] = $this->makeBill(15000, 2);

        $entry = $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments", [
            'production_item_id' => $items[0]->id, 'amount' => 5000, 'paid_at' => now()->toDateString(),
        ])->json('entry');
        $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments/finalize", []);

        $this->assertDatabaseCount('financial_payments', 1);

        $response = $this->actingAs($user)->deleteJson("/production/item-payments/{$entry['id']}");
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseCount('financial_payments', 0); // round emptied out, receipt removed
        $this->assertEquals(0, $transaction->fresh()->paid_amount);
        $this->assertSame('pending', $transaction->fresh()->status);
    }

    /**
     * Existing lump-sum "Add Payment" flow must behave exactly as before —
     * this feature is purely additive.
     */
    public function test_lump_sum_flow_is_unaffected(): void
    {
        ['user' => $user, 'order' => $order, 'group' => $group] = $this->makeBill();
        $lumpTransaction = $group->transactions()->create([
            'production_order_id' => $order->id,
            'amount' => 10000,
            'type' => 'installment',
            'status' => 'pending',
            // payment_collection_mode omitted — must default to lump_sum.
        ]);
        $this->assertSame('lump_sum', $lumpTransaction->fresh()->payment_collection_mode);

        $response = $this->actingAs($user)->postJson("/production/transactions/{$lumpTransaction->id}/payments", [
            'amount' => 10000,
            'paid_at' => now()->toDateString(),
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);

        $lumpTransaction->refresh();
        $this->assertEquals(10000, $lumpTransaction->paid_amount);
        $this->assertSame('paid', $lumpTransaction->status);
        $this->assertDatabaseCount('financial_transaction_item_payments', 0); // untouched by the lump-sum path
    }

    /**
     * A bill must never mix lump-sum and per-employee payments at once —
     * switching payment_collection_mode is blocked once the CURRENT mode
     * has real recorded amounts, and allowed again once those are deleted.
     */
    public function test_mode_switch_is_blocked_once_lump_sum_payments_exist_and_allowed_after_deleting_them(): void
    {
        ['user' => $user, 'order' => $order, 'group' => $group] = $this->makeBill();
        $lumpTransaction = $group->transactions()->create([
            'production_order_id' => $order->id,
            'amount' => 10000,
            'type' => 'installment',
            'status' => 'pending',
            'payment_collection_mode' => 'lump_sum',
        ]);

        // No payments yet — free to switch.
        $this->actingAs($user)->postJson("/production/transactions/{$lumpTransaction->id}", [
            '_method' => 'PUT', 'status' => 'pending', 'payment_collection_mode' => 'per_employee',
        ])->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame('per_employee', $lumpTransaction->fresh()->payment_collection_mode);

        // Switch back to lump_sum, then record a lump-sum payment.
        // refresh() (not fresh()) so this in-memory object actually learns
        // about the mode change the earlier HTTP request made — otherwise
        // Eloquent's own dirty-check thinks 'lump_sum' is unchanged (it's
        // still what THIS object was created with) and silently skips the
        // UPDATE entirely.
        $lumpTransaction->refresh()->update(['payment_collection_mode' => 'lump_sum']);
        $this->actingAs($user)->postJson("/production/transactions/{$lumpTransaction->id}/payments", [
            'amount' => 2000, 'paid_at' => now()->toDateString(),
        ])->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseCount('financial_payments', 1);

        // Now blocked.
        $blocked = $this->actingAs($user)->postJson("/production/transactions/{$lumpTransaction->id}", [
            '_method' => 'PUT', 'status' => 'partial', 'payment_collection_mode' => 'per_employee',
        ]);
        $blocked->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame('lump_sum', $lumpTransaction->fresh()->payment_collection_mode);

        // Delete the payment, then the switch is allowed again.
        $paymentId = $lumpTransaction->fresh()->payments()->first()->id;
        $this->actingAs($user)->deleteJson("/production/payments/{$paymentId}")->assertStatus(200);

        $this->actingAs($user)->postJson("/production/transactions/{$lumpTransaction->id}", [
            '_method' => 'PUT', 'status' => 'pending', 'payment_collection_mode' => 'per_employee',
        ])->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame('per_employee', $lumpTransaction->fresh()->payment_collection_mode);
    }

    public function test_mode_switch_is_blocked_once_per_employee_payments_exist_even_before_finalize(): void
    {
        ['user' => $user, 'transaction' => $transaction, 'items' => $items] = $this->makeBill(15000, 2);
        $this->assertSame('per_employee', $transaction->payment_collection_mode);

        // One pending (not yet finalized) item payment is enough to lock the mode.
        $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}/item-payments", [
            'production_item_id' => $items[0]->id, 'amount' => 5000, 'paid_at' => now()->toDateString(),
        ])->assertStatus(200);

        $blocked = $this->actingAs($user)->postJson("/production/transactions/{$transaction->id}", [
            '_method' => 'PUT', 'status' => 'pending', 'payment_collection_mode' => 'lump_sum',
        ]);
        $blocked->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame('per_employee', $transaction->fresh()->payment_collection_mode);
    }

    public function test_store_transaction_accepts_payment_collection_mode_at_creation(): void
    {
        ['user' => $user, 'order' => $order, 'group' => $group] = $this->makeBill();

        $response = $this->actingAs($user)->postJson("/production/{$order->id}/transactions", [
            'financial_group_id' => $group->id,
            'type' => 'installment',
            'amount' => 5000,
            'payment_collection_mode' => 'per_employee',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $newId = $response->json('transaction.id');
        $this->assertSame('per_employee', FinancialTransaction::find($newId)->payment_collection_mode);
    }
}

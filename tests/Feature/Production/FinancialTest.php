<?php

namespace Tests\Feature\Production;

use App\Models\ProductionOrder;
use App\Models\ProductionFinancialGroup;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_transaction_syncs_pricing_tiers()
    {
        // 1. Setup
        // Create Role
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        $user = User::factory()->create();
        $user->assignRole('admin'); // Ensure permission

        $employer = Employer::factory()->create();
        $workType = WorkType::create(['name' => 'Test Job', 'slug' => 'test-job']);

        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'pre_production'
        ]);

        $employee = Employee::factory()->create();
        $candidateId = $employee->id;

        // Create Financial Group with pricing tier using emp_{id}
        $group = $order->financialGroups()->create([
            'name' => 'Group 1',
            'financial_data' => [
                'pricing_tiers' => [
                    [
                        'price' => 1000,
                        'count' => 1,
                        'item_ids' => ['emp_' . $candidateId] // Placeholder
                    ]
                ]
            ]
        ]);

        // 2. Act: Call storeTransaction
        $response = $this->actingAs($user)->postJson("/production/{$order->id}/transactions", [
            'type' => 'installment',
            'amount' => 1000,
            'financial_group_id' => $group->id,
            'employee_ids' => [$candidateId] // Passing candidate ID
        ]);

        // 3. Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify Items Created
        $this->assertDatabaseHas('production_items', [
            'production_order_id' => $order->id,
            'employee_id' => $candidateId
        ]);

        $newItem = \App\Models\ProductionItem::where('employee_id', $candidateId)->first();
        $this->assertNotNull($newItem);

        // Verify Pricing Tier Updated
        $group->refresh();
        $tiers = $group->financial_data['pricing_tiers'];
        $this->assertCount(1, $tiers);
        $this->assertContains($newItem->id, $tiers[0]['item_ids']);
        $this->assertNotContains('emp_' . $candidateId, $tiers[0]['item_ids']);
    }

    public function test_update_transaction_syncs_pricing_tiers()
    {
        // 1. Setup
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $employer = Employer::factory()->create();
        $workType = WorkType::create(['name' => 'Test Job 2', 'slug' => 'test-job-2']);

        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'pre_production'
        ]);

        $employee = Employee::factory()->create();
        $candidateId = $employee->id;

        // Create Financial Group
        $group = $order->financialGroups()->create([
            'name' => 'Group 1',
            'financial_data' => [
                'pricing_tiers' => [
                    [
                        'price' => 1000,
                        'count' => 1,
                        'item_ids' => ['emp_' . $candidateId]
                    ]
                ]
            ]
        ]);

        // Create transaction first
        $transaction = $group->transactions()->create([
            'production_order_id' => $order->id,
            'amount' => 1000,
            'type' => 'installment',
            'status' => 'pending'
        ]);

        // 2. Act: Call updateTransaction adding the candidate
        $response = $this->actingAs($user)->putJson("/production/transactions/{$transaction->id}", [
            'employee_ids' => [$candidateId]
        ]);

        // 3. Assert
        $response->assertStatus(200);

        // Verify Items Created
        $newItem = \App\Models\ProductionItem::where('employee_id', $candidateId)->first();
        $this->assertNotNull($newItem);

        // Verify Pricing Tier Updated
        $group->refresh();
        $tiers = $group->financial_data['pricing_tiers'];
        $this->assertContains($newItem->id, $tiers[0]['item_ids']);
        $this->assertNotContains('emp_' . $candidateId, $tiers[0]['item_ids']);
    }
}

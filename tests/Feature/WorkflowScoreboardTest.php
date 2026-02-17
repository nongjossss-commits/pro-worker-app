<?php

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed WorkTypes
    WorkType::create(['name' => 'Notify In', 'slug' => 'notify_in', 'order' => 1]);
    WorkType::create(['name' => 'Notify Out', 'slug' => 'notify_out', 'order' => 2]);
    WorkType::create(['name' => 'MOU Import', 'slug' => 'mou_import', 'order' => 3]);

    // Seed Employer
    $this->employer = Employer::create([
        'employerNameTh' => 'Test Employer',
        'employerId' => 'EMP-001',
    ]);

    // Seed User
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('dashboard stats exclude items from cancelled orders', function () {
    // 1. Create WorkType Steps
    $workType = WorkType::where('slug', 'notify_in')->first();
    WorkTypeStep::create(['work_type_id' => $workType->id, 'name' => 'Step 1', 'order' => 1]);

    // 2. Create Active Order with Pending Item
    $activeOrder = ProductionOrder::create([
        'employer_id' => $this->employer->id,
        'work_type_id' => $workType->id,
        'status' => 'active',
        'type' => 'employer',
        'project_name' => 'Active Project',
        'created_by' => $this->user->id,
    ]);
    ProductionItem::create([
        'production_order_id' => $activeOrder->id,
        'status' => 'pending',
        'group_name' => 'Active Batch',
    ]);

    // 3. Create Cancelled Order with Pending Item (The Bug Case)
    $cancelledOrder = ProductionOrder::create([
        'employer_id' => $this->employer->id,
        'work_type_id' => $workType->id,
        'status' => 'cancelled', // CANCELLED
        'type' => 'employer',
        'project_name' => 'Cancelled Project',
        'created_by' => $this->user->id,
    ]);
    ProductionItem::create([
        'production_order_id' => $cancelledOrder->id,
        'status' => 'pending', // Still Pending
        'group_name' => 'Cancelled Batch',
    ]);

    // 4. Create Active Order with Completed Item
    $completedOrder = ProductionOrder::create([
        'employer_id' => $this->employer->id,
        'work_type_id' => $workType->id,
        'status' => 'active',
        'type' => 'employer',
        'project_name' => 'Completed Project',
        'created_by' => $this->user->id,
    ]);
    ProductionItem::create([
        'production_order_id' => $completedOrder->id,
        'status' => 'completed',
        'group_name' => 'Completed Batch',
    ]);

    // Call Dashboard (no tab)
    $response = $this->get(route('workflow.index'));

    $response->assertStatus(200);

    // Get View Data
    $stats = $response->viewData('stats');

    // Currently (Before Fix):
    // not_started: 2 (Active Pending + Cancelled Pending)
    // cancelled: 0 (Only checks item status)

    // We want (After Fix):
    // not_started: 1 (Only Active Pending)
    // cancelled: 1 (Cancelled Order's Item)

    expect($stats['not_started'])->toBe(1)
        ->and($stats['cancelled'])->toBe(1)
        ->and($stats['completed'])->toBe(1);
});

test('tab stats exclude items from cancelled orders', function () {
    $workType = WorkType::where('slug', 'notify_in')->first();
    WorkTypeStep::create(['work_type_id' => $workType->id, 'name' => 'Step 1', 'order' => 1]);

    // Active
    $activeOrder = ProductionOrder::create([
        'employer_id' => $this->employer->id,
        'work_type_id' => $workType->id,
        'status' => 'active',
        'type' => 'employer',
        'project_name' => 'Active Project',
        'created_by' => $this->user->id,
    ]);
    ProductionItem::create([
        'production_order_id' => $activeOrder->id,
        'status' => 'pending',
    ]);

    // Cancelled Order
    $cancelledOrder = ProductionOrder::create([
        'employer_id' => $this->employer->id,
        'work_type_id' => $workType->id,
        'status' => 'cancelled',
        'type' => 'employer',
        'project_name' => 'Cancelled Project',
        'created_by' => $this->user->id,
    ]);
    ProductionItem::create([
        'production_order_id' => $cancelledOrder->id,
        'status' => 'pending',
    ]);

    $response = $this->get(route('workflow.index', ['tab' => 'notify_in']));

    $stats = $response->viewData('stats');

    // Expect: Not Started = 1, Cancelled = 1.
    expect($stats['not_started'])->toBe(1)
        ->and($stats['cancelled'])->toBe(1);
});

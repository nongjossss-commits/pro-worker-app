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
    // Workflow is a staff-only menu (role gate since 2026-09-02)
    \Spatie\Permission\Models\Role::findOrCreate('admin');
    $this->user->assignRole('admin');
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

test('dashboard stats exclude orders from deleted employers', function () {
    $workType = WorkType::where('slug', 'notify_in')->first();

    // Active Employer
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

    // Deleted Employer
    $deletedEmployer = Employer::create([
        'employerNameTh' => 'Deleted Employer',
        'employerId' => 'EMP-002',
    ]);
    $deletedOrder = ProductionOrder::create([
        'employer_id' => $deletedEmployer->id,
        'work_type_id' => $workType->id,
        'status' => 'active',
        'type' => 'employer',
        'project_name' => 'Deleted Project',
        'created_by' => $this->user->id,
    ]);
    ProductionItem::create([
        'production_order_id' => $deletedOrder->id,
        'status' => 'pending',
    ]);

    $deletedEmployer->delete();

    $response = $this->get(route('workflow.index'));
    $stats = $response->viewData('stats');

    // Expect: Not Started = 1 (Active only)
    expect($stats['not_started'])->toBe(1)
        ->and($stats['total_employees'])->toBe(1);
});

test('not started never counts finished employees and matches the filter', function () {
    // 2026-11-19: "ยังไม่เริ่ม" used to also count FINISHED employees who never
    // ticked step 1 (all of them, ever) — the number never matched the list
    // the "not started" filter shows.
    $workType = WorkType::where('slug', 'notify_in')->first();
    $step1 = WorkTypeStep::create(['work_type_id' => $workType->id, 'name' => 'Step 1', 'order' => 1]);
    $order = ProductionOrder::create([
        'employer_id' => $this->employer->id, 'work_type_id' => $workType->id, 'status' => 'active',
        'type' => 'employer', 'project_name' => 'Mixed Project', 'created_by' => $this->user->id,
    ]);
    ProductionItem::create(['production_order_id' => $order->id, 'status' => 'pending']); // not started
    $started = ProductionItem::create(['production_order_id' => $order->id, 'status' => 'pending']);
    $started->completedWorkTypeSteps()->attach($step1->id);
    ProductionItem::create(['production_order_id' => $order->id, 'status' => 'completed', 'completed_at' => now()->subMonths(3)]); // finished, no steps
    ProductionItem::create(['production_order_id' => $order->id, 'status' => 'completed', 'completed_at' => now()->subHour()]);    // finished, no steps

    expect($this->get(route('workflow.index'))->viewData('stats')['not_started'])->toBe(1);
    expect($this->get(route('workflow.index', ['tab' => 'notify_in']))->viewData('stats')['not_started'])->toBe(1);

    // the card still shows up under the "not started" filter
    $this->get(route('workflow.index', ['tab' => 'notify_in', 'filter' => 'not_started']))
        ->assertOk()->assertSee('Mixed Project');
});

<?php

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed WorkTypes
    WorkType::create(['name' => 'Notify In', 'slug' => 'notify_in', 'order' => 1]);

    // Seed Employer
    $this->employer = Employer::create([
        'employerNameTh' => 'Test Employer Search',
        'employerId' => 'EMP-SEARCH-001',
    ]);

    // Seed User
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can search by production item request number in workflow index', function () {
    $workType = WorkType::where('slug', 'notify_in')->first();

    $order = ProductionOrder::create([
        'employer_id' => $this->employer->id,
        'work_type_id' => $workType->id,
        'status' => 'active',
        'type' => 'employer',
        'project_name' => 'Active Project Search',
        'created_by' => $this->user->id,
    ]);

    $employee = Employee::create([
        'employer_id' => $this->employer->id,
        'employeeNameEn' => 'John Doe',
        'request_number' => 'EMP-REQ-001', // Base employee request number
        'status' => 'active',
    ]);

    $item = ProductionItem::create([
        'production_order_id' => $order->id,
        'employee_id' => $employee->id,
        'status' => 'pending',
        'request_number' => 'WORKFLOW-REQ-123', // ProductionItem request number
    ]);

    // Call index with search matching ProductionItem request number
    $response = $this->get(route('workflow.index', ['tab' => 'notify_in', 'search' => 'WORKFLOW-REQ-123']));
    $response->assertStatus(200);

    // The order should be found and present in the view's $orders variable
    $orders = $response->viewData('orders');
    expect($orders->count())->toBeGreaterThan(0);
    expect($orders->first()->id)->toBe($order->id);

    // Call batch stats with search matching ProductionItem request number
    $response = $this->postJson(route('workflow.stats.batch'), [
        'order_ids' => [$order->id],
        'search' => 'WORKFLOW-REQ-123'
    ]);
    $response->assertStatus(200);
    $response->assertJsonPath("stats.{$order->id}.total", 1);
});


test('can search by employee request number in workflow index', function () {
    $workType = WorkType::where('slug', 'notify_in')->first();

    $order = ProductionOrder::create([
        'employer_id' => $this->employer->id,
        'work_type_id' => $workType->id,
        'status' => 'active',
        'type' => 'employer',
        'project_name' => 'Active Project Search',
        'created_by' => $this->user->id,
    ]);

    $employee = Employee::create([
        'employer_id' => $this->employer->id,
        'employeeNameEn' => 'John Doe',
        'request_number' => 'EMP-REQ-002', // Base employee request number
        'status' => 'active',
    ]);

    $item = ProductionItem::create([
        'production_order_id' => $order->id,
        'employee_id' => $employee->id,
        'status' => 'pending',
    ]);

    // Call index with search matching Employee request number
    $response = $this->get(route('workflow.index', ['tab' => 'notify_in', 'search' => 'EMP-REQ-002']));
    $response->assertStatus(200);

    // The order should be found and present in the view's $orders variable
    $orders = $response->viewData('orders');
    expect($orders->count())->toBeGreaterThan(0);
    expect($orders->first()->id)->toBe($order->id);

    // Call batch stats with search matching Employee request number
    $response = $this->postJson(route('workflow.stats.batch'), [
        'order_ids' => [$order->id],
        'search' => 'EMP-REQ-002'
    ]);
    $response->assertStatus(200);
    $response->assertJsonPath("stats.{$order->id}.total", 1);
});

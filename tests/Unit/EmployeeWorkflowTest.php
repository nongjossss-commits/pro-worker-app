<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_workflows_accessor()
    {
        // Setup
        $workType = WorkType::create(['name' => 'Test Work Type', 'slug' => 'test-work-type', 'order' => 1]);
        $employer = Employer::create(['employerNameTh' => 'Test Employer', 'employerNameEn' => 'Test Employer EN', 'employerId' => 'EMP001']);
        $employee = Employee::create([
            'employer_id' => $employer->id,
            'employeeNameTh' => 'Somchai',
            'employeeNameEn' => 'Somchai',
            'status' => 'active'
        ]);

        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'active',
            'type' => 'employer',
            'project_name' => 'Test Project'
        ]);

        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => $employee->id,
            'status' => 'pending'
        ]);

        // Act
        $activeWorkflows = $employee->active_workflows;

        // Assert
        $this->assertCount(1, $activeWorkflows);
        $this->assertEquals('Test Work Type', $activeWorkflows->first()->name);
        $this->assertEquals('Processing', $activeWorkflows->first()->status_label);
        $this->assertEquals($item->id, $activeWorkflows->first()->item_id);
    }

    public function test_active_workflows_accessor_excludes_completed()
    {
        // Setup
        $workType = WorkType::create(['name' => 'Test Work Type', 'slug' => 'test-work-type', 'order' => 1]);
        $employer = Employer::create(['employerNameTh' => 'Test Employer', 'employerNameEn' => 'Test Employer EN', 'employerId' => 'EMP001']);
        $employee = Employee::create([
            'employer_id' => $employer->id,
            'employeeNameTh' => 'Somchai',
            'employeeNameEn' => 'Somchai',
            'status' => 'active'
        ]);

        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'active',
            'type' => 'employer',
            'project_name' => 'Test Project'
        ]);

        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => $employee->id,
            'status' => 'completed' // COMPLETED
        ]);

        // Act
        $activeWorkflows = $employee->active_workflows;

        // Assert
        $this->assertCount(0, $activeWorkflows);
    }
}

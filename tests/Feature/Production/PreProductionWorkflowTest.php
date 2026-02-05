<?php

namespace Tests\Feature\Production;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use App\Models\User;
use App\Models\Employer;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreProductionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $employer;
    protected $workType;
    protected $preparationStep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->employer = Employer::factory()->create();

        // Create a WorkType (e.g., Notify In)
        $this->workType = WorkType::create([
            'name' => 'Notify In',
            'slug' => 'notify_in',
            'order' => 1
        ]);

        // Create a Preparation Step
        $this->preparationStep = WorkTypeStep::create([
            'work_type_id' => $this->workType->id,
            'name' => 'Check Documents',
            'order' => 1,
            'stage' => 'preparation'
        ]);
    }

    /** @test */
    public function can_view_pre_production_dashboard_with_tabs()
    {
        $response = $this->actingAs($this->user)->get(route('production.index'));
        $response->assertStatus(200);
        $response->assertSee('Pre-Production / Preparation');
        $response->assertSee('Notify In'); // Tab name
    }

    /** @test */
    public function can_create_and_view_pre_production_order()
    {
        $order = ProductionOrder::create([
            'employer_id' => $this->employer->id,
            'work_type_id' => $this->workType->id,
            'type' => 'employer',
            'project_name' => 'Test Project',
            'status' => 'pre_production',
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)->get(route('production.index', ['tab' => 'notify_in']));
        $response->assertStatus(200);
        // We now prioritize showing the Employer Name directly
        $response->assertSee($this->employer->employerNameTh ?? 'Test Project');
    }

    /** @test */
    public function can_add_preparation_step()
    {
        $response = $this->actingAs($this->user)->postJson(route('production.steps.store'), [
            'work_type_id' => $this->workType->id,
            'name' => 'Verify Passport'
        ]);

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('work_type_steps', [
            'work_type_id' => $this->workType->id,
            'name' => 'Verify Passport',
            'stage' => 'preparation'
        ]);
    }

    /** @test */
    public function can_send_item_to_workflow()
    {
        // 1. Create Pre-Production Order & Item
        $order = ProductionOrder::create([
            'employer_id' => $this->employer->id,
            'work_type_id' => $this->workType->id,
            'type' => 'employer',
            'project_name' => 'Prep Project',
            'status' => 'pre_production',
            'created_by' => $this->user->id
        ]);

        $employee = Employee::factory()->create(['employer_id' => $this->employer->id]);

        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => $employee->id,
            'status' => 'pending'
        ]);

        // 2. Mock 'Send to Workflow' Action
        $response = $this->actingAs($this->user)->postJson(route('production.item.send_to_workflow', ['item' => $item->id]));

        $response->assertJson(['success' => true]);

        // 3. Verify Item moved to a NEW Active Order
        $item->refresh();
        $newOrder = $item->order;

        $this->assertNotEquals($order->id, $newOrder->id);
        $this->assertEquals('active', $newOrder->status);
        $this->assertEquals($this->employer->id, $newOrder->employer_id);
    }

    /** @test */
    public function cannot_add_employee_if_already_in_active_workflow()
    {
        $employee = Employee::factory()->create(['employer_id' => $this->employer->id]);

        // 1. Create Active Order & Item
        $activeOrder = ProductionOrder::create([
            'employer_id' => $this->employer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active', // Already in workflow
            'created_by' => $this->user->id
        ]);

        ProductionItem::create([
            'production_order_id' => $activeOrder->id,
            'employee_id' => $employee->id,
            'status' => 'pending'
        ]);

        // 2. Try to add same employee to Pre-Production (Create new job)
        // We simulate the form submission to 'workflow.store' with 'is_pre_production' = true
        $response = $this->actingAs($this->user)
            ->from(route('production.index'))
            ->post(route('workflow.store'), [
                'employer_id' => $this->employer->id,
                'work_type_id' => $this->workType->id,
                'employee_ids' => [$employee->id],
                'is_pre_production' => true
            ]);

        // 3. Assert redirected back with error
        $response->assertRedirect(route('production.index'));
        $response->assertSessionHas('duplicate_error');

        // Verify NO new pre-production order created (because validation happens first)
        $this->assertDatabaseMissing('production_orders', [
            'status' => 'pre_production',
            'employer_id' => $this->employer->id
        ]);
    }

    /** @test */
    public function create_job_from_pre_production_sets_correct_status()
    {
        // Simulate creating a new empty job (bucket) from Pre-Prod modal
        $response = $this->actingAs($this->user)
            ->post(route('workflow.store'), [
                'employer_id' => $this->employer->id,
                'work_type_id' => $this->workType->id,
                'is_pre_production' => true // The flag
            ]);

        $response->assertRedirect(route('production.index', ['tab' => 'notify_in']));

        $this->assertDatabaseHas('production_orders', [
            'employer_id' => $this->employer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'pre_production'
        ]);
    }
}

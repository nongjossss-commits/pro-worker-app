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
        $response->assertSee('Test Project');
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
}

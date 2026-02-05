<?php

namespace Tests\Feature\Production;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkType;
use App\Models\User;
use App\Models\Employer;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WorkflowAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employerUser;
    protected $caretakerUser;
    protected $otherUser;
    protected $workType;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'employer']);
        Role::firstOrCreate(['name' => 'caretaker']);
        Role::firstOrCreate(['name' => 'staff']);

        // Permissions
        Permission::firstOrCreate(['name' => 'manage-own-workflow']);

        // Create WorkType
        $this->workType = WorkType::create([
            'name' => 'Test Workflow',
            'slug' => 'test_wf',
            'order' => 1
        ]);

        // Create Users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->employerUser = User::factory()->create();
        $this->employerUser->assignRole('employer');

        $this->caretakerUser = User::factory()->create();
        $this->caretakerUser->assignRole('caretaker');

        $this->otherUser = User::factory()->create(); // Just a random user
    }

    /** @test */
    public function employer_can_only_see_own_orders()
    {
        // Employer Profile linked to User
        $myEmployer = Employer::factory()->create(['user_id' => $this->employerUser->id]);

        // Create My Order
        $myOrder = ProductionOrder::create([
            'employer_id' => $myEmployer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);

        // Create Other Order
        $otherEmployer = Employer::factory()->create();
        $otherOrder = ProductionOrder::create([
            'employer_id' => $otherEmployer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->employerUser)->get(route('workflow.index', ['tab' => 'test_wf']));

        $response->assertStatus(200);
        $response->assertSee($myOrder->project_name); // Should see own
        $response->assertDontSee($otherOrder->project_name); // Should NOT see other
    }

    /** @test */
    public function caretaker_can_only_see_assigned_employers_orders()
    {
        // 1. Assigned Employer
        $assignedEmployer = Employer::factory()->create(['assigned_staff_id' => $this->caretakerUser->id]);
        $assignedOrder = ProductionOrder::create([
            'employer_id' => $assignedEmployer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);

        // 2. Unassigned Employer
        $randomUser = User::factory()->create();
        $unassignedEmployer = Employer::factory()->create(['assigned_staff_id' => $randomUser->id]); // Someone else
        $unassignedOrder = ProductionOrder::create([
            'employer_id' => $unassignedEmployer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->caretakerUser)->get(route('workflow.index', ['tab' => 'test_wf']));

        $response->assertStatus(200);
        $response->assertSee($assignedOrder->project_name);
        $response->assertDontSee($unassignedOrder->project_name);
    }

    /** @test */
    public function admin_can_see_all_orders()
    {
        $emp1 = Employer::factory()->create();
        $order1 = ProductionOrder::create([
            'employer_id' => $emp1->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);

        $emp2 = Employer::factory()->create();
        $order2 = ProductionOrder::create([
            'employer_id' => $emp2->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)->get(route('workflow.index', ['tab' => 'test_wf']));

        $response->assertStatus(200);
        $response->assertSee($order1->project_name);
        $response->assertSee($order2->project_name);
    }

    /** @test */
    public function employer_is_read_only_by_default()
    {
        // Create an item for the employer
        $myEmployer = Employer::factory()->create(['user_id' => $this->employerUser->id]);
        $order = ProductionOrder::create([
            'employer_id' => $myEmployer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);
        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'status' => 'pending'
        ]);

        // 1. Check Index - "Add Employee" button should be hidden
        $response = $this->actingAs($this->employerUser)->get(route('workflow.index', ['tab' => 'test_wf']));
        $response->assertDontSee('Add Employee'); // Main button text

        // 2. Check Item Card HTML - "Finish", "Cancel" buttons hidden
        // We fetch the item card specifically via the AJAX endpoint logic or just check the view if possible.
        // Controller `getItemHtml` returns the partial.
        $response = $this->actingAs($this->employerUser)->get("/workflow/item/{$item->id}/card");
        $response->assertStatus(200);

        $response->assertDontSee('Finish');
        $response->assertDontSee('Cancel Item');
        $response->assertDontSee('Manage Team');

        // Should NOT see Delete button
        $response->assertDontSee('Delete');
    }

    /** @test */
    public function employer_with_permission_can_manage()
    {
        // Give permission
        $this->employerUser->givePermissionTo('manage-own-workflow');

        $myEmployer = Employer::factory()->create(['user_id' => $this->employerUser->id]);
        $order = ProductionOrder::create([
            'employer_id' => $myEmployer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);
        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->employerUser)->get(route('workflow.index', ['tab' => 'test_wf']));
        $response->assertSee('Add Employee');

        $response = $this->actingAs($this->employerUser)->get("/workflow/item/{$item->id}/card");
        $response->assertSee('Finish'); // or whatever unique string identifies the button
    }
}

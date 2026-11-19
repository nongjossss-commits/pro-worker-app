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
        // Since 2026-09-02 (bb53bd5d) employer accounts have no access to the
        // Workflow menu at all — WorkflowController's role gate allows only
        // admin / super-admin / staff / caretaker. Not even their own cards.
        $myEmployer = Employer::factory()->create(['user_id' => $this->employerUser->id]);
        ProductionOrder::create([
            'employer_id' => $myEmployer->id,
            'work_type_id' => $this->workType->id,
            'status' => 'active',
            'created_by' => $this->admin->id
        ]);

        $this->actingAs($this->employerUser)->get(route('workflow.index', ['tab' => 'test_wf']))->assertForbidden();
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
        // Employer accounts are blocked from the Workflow menu (see above) —
        // the card endpoint as well, so nothing is readable, let alone editable.
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

        $this->actingAs($this->employerUser)->get(route('workflow.index', ['tab' => 'test_wf']))->assertForbidden();
        $this->actingAs($this->employerUser)->get("/workflow/item/{$item->id}/card")->assertForbidden();
    }
    /** @test */
    public function employer_with_permission_can_manage()
    {
        // Even holding manage-own-workflow, an employer account stays out —
        // the role gate comes first.
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

        $this->actingAs($this->employerUser)->get(route('workflow.index', ['tab' => 'test_wf']))->assertForbidden();
        $this->actingAs($this->employerUser)->get("/workflow/item/{$item->id}/card")->assertForbidden();
    }
}

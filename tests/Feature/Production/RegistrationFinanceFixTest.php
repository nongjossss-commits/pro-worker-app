<?php

namespace Tests\Feature\Production;

use App\Models\User;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\RegistrationStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RegistrationFinanceFixTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $employer;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Role & Permission
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'view-finance']));
        $role->givePermissionTo(Permission::create(['name' => 'edit-employees']));

        // 2. Create Admin User
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        // 3. Create Employer
        $this->employer = Employer::factory()->create([
            'employerNameTh' => 'Test Employer TH',
            'employerNameEn' => 'Test Employer EN',
        ]);

        // 4. Create Registration Step (needed for some logic possibly)
        RegistrationStep::create(['name' => 'Step 1', 'order' => 1]);
    }

    public function test_financial_tab_excludes_employees_already_in_order()
    {
        // Target 1: Should be visible (Not in order)
        $targetVisible = Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'status' => 'registration_pending',
            'employeeNameTh' => 'Visible Candidate'
        ]);

        // Target 2: Should be hidden (Already in order)
        $targetHidden = Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'status' => 'registration_pending',
            'employeeNameTh' => 'Hidden Candidate'
        ]);

        // Create the Production Order for this employer
        $order = ProductionOrder::create([
            'employer_id' => $this->employer->id,
            'status' => 'registration_resolution',
            'financial_data' => []
        ]);

        // Add Target 2 to the order
        ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => $targetHidden->id,
            'status' => 'pending'
        ]);

        // Route: production.registration.finance.tab
        // We need to define the route name correctly. Checking RegistrationController routes.
        // Usually it's defined in web.php.
        // Assuming 'production.registration.finance.tab' from previous test file reading.

        $response = $this->actingAs($this->adminUser)
                         ->get(route('production.registration.finance.tab', $this->employer->id));

        $response->assertStatus(200);

        // Check view data
        $employees = $response->viewData('employees');

        // Should contain Visible Candidate
        $this->assertTrue($employees->contains('id', $targetVisible->id), 'Visible candidate should be in the list.');

        // Should NOT contain Hidden Candidate
        $this->assertFalse($employees->contains('id', $targetHidden->id), 'Hidden candidate (already in order) should NOT be in the list.');
    }
}

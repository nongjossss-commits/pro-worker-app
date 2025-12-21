<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Models\SystemConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RenewalResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $employer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin User
        $this->user = User::factory()->create();
        $role = Role::create(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->user->givePermissionTo(Permission::create(['name' => 'view-employers']));
        $this->user->givePermissionTo(Permission::create(['name' => 'edit-employees']));
        $this->user->givePermissionTo(Permission::create(['name' => 'edit-employers']));

        // Create Employer
        $this->employer = Employer::factory()->create();
    }

    public function test_can_view_renewal_dashboard()
    {
        $response = $this->actingAs($this->user)->get(route('production.renewal.index'));
        $response->assertStatus(200);
        $response->assertSee('Renewal Resolution');
    }

    public function test_can_configure_expiry_date_and_auto_import()
    {
        // Create an employee with specific expiry
        $targetDate = now()->addDays(30)->format('Y-m-d');
        $employee = Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'workPermitExpiryDate' => $targetDate,
            'status' => 'active'
        ]);

        // Post Configuration
        $response = $this->actingAs($this->user)->post(route('production.renewal.configure_expiry'), [
            'target_expiry_date' => $targetDate
        ]);

        $response->assertRedirect(route('production.renewal.index'));
        $response->assertSessionHas('success');

        // Check Config
        $this->assertEquals($targetDate, SystemConfig::where('key', 'renewal_target_expiry_date')->value('value'));

        // Check Employee Status Updated
        $this->assertEquals('renewal_pending', $employee->fresh()->status);
    }

    public function test_observer_auto_imports_on_update()
    {
        // Set Config First
        $targetDate = '2025-12-31';
        SystemConfig::create(['key' => 'renewal_target_expiry_date', 'value' => $targetDate]);

        // Create Employee (Not matching)
        $employee = Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'workPermitExpiryDate' => '2024-01-01',
            'status' => 'active'
        ]);

        // Update Employee to match
        $employee->update(['workPermitExpiryDate' => $targetDate]);

        // Check Status
        $this->assertEquals('renewal_pending', $employee->fresh()->status);
    }

    public function test_observer_ignores_mou()
    {
        // Set Config First
        $targetDate = '2025-12-31';
        SystemConfig::create(['key' => 'renewal_target_expiry_date', 'value' => $targetDate]);

        // Create Employee (Matching Date but MOU)
        $employee = Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'workPermitExpiryDate' => $targetDate,
            'workPermitMOUGroup' => 'MOU 2025',
            'status' => 'active'
        ]);

        // Trigger update (Observer runs on update/create)
        $employee->update(['employeeNameTh' => 'Test Update']);

        // Check Status should NOT change
        $this->assertNotEquals('renewal_pending', $employee->fresh()->status);
    }
}

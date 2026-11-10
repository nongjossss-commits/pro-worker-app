<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\RegistrationStep;
use App\Models\ResolutionTab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * An employee mid-renewal under Employer A who gets terminated and
 * transferred to Employer B must keep their in-progress renewal card —
 * it should follow them to Employer B's renewal list (both the AJAX
 * sub-fetch and the main operations page), not vanish. Registration/Renewal
 * group employees by their CURRENT employer_id dynamically, so this is
 * expected to already work — this test locks that behavior in.
 */
class EmployerTransferResolutionTabTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('admin');
        $user->assignRole($role);
        foreach (['view-employers', 'edit-employees', 'edit-employers', 'terminate-employees'] as $perm) {
            $user->givePermissionTo(Permission::findOrCreate($perm));
        }
        return $user;
    }

    public function test_renewal_card_follows_employee_to_new_employer_after_terminate_and_transfer(): void
    {
        $user = $this->makeUser();
        $employerA = Employer::factory()->create();
        $employerB = Employer::factory()->create();
        $tab = ResolutionTab::firstOrCreate(['type' => 'renewal'], ['name' => 'Test Renewal Tab', 'slug' => 'test-renewal']);
        $step = RegistrationStep::create(['name' => 'Step 1', 'type' => 'renewal', 'resolution_tab_id' => $tab->id, 'order' => 1]);

        $employee = Employee::factory()->create([
            'employer_id' => $employerA->id,
            'status' => 'renewal_pending',
            'resolution_tab_id' => $tab->id,
        ]);
        $employee->registrationSteps()->attach($step->id, ['completed_at' => now()]);

        // Sanity check: employee shows up under Employer A before anything happens.
        $this->actingAs($user)->get(route('production.renewal.employer.employees', [
            'resolutionTab' => $tab->id, 'employer' => $employerA->id,
        ]))->assertStatus(200)->assertSee('data-employee-id="' . $employee->id . '"', false);

        // Terminate from Employer A, then transfer to Employer B — the
        // real-world "picked up by a new employer mid-renewal" case.
        $this->actingAs($user)->post(route('employees.terminate', $employee->id), [
            'termination_date' => now()->toDateString(),
            'termination_reason' => 'Left employer A',
        ])->assertRedirect();

        $this->actingAs($user)->postJson(route('employees.transfer', $employee->id), [
            'new_employer_id' => $employerB->id,
        ])->assertStatus(200)->assertJson(['success' => true]);

        $employee->refresh();
        $this->assertEquals($employerB->id, $employee->employer_id);
        $this->assertNull($employee->terminated_at);

        // The employee's renewal progress must be untouched by the transfer.
        $this->assertEquals('renewal_pending', $employee->status);
        $this->assertEquals($tab->id, $employee->resolution_tab_id);
        $this->assertTrue($employee->registrationSteps()->where('registration_steps.id', $step->id)->exists());

        // The employee's card must now show up under Employer B — both the
        // AJAX sub-fetch and the main operations page a fresh load shows.
        $this->actingAs($user)->get(route('production.renewal.employer.employees', [
            'resolutionTab' => $tab->id, 'employer' => $employerB->id,
        ]))->assertStatus(200)->assertSee('data-employee-id="' . $employee->id . '"', false);

        $mainPage = $this->actingAs($user)->get(route('production.renewal.operations', ['resolutionTab' => $tab->id]));
        $mainPage->assertStatus(200);
        $employerBName = $employerB->getRawOriginal('employerNameTh') ?: $employerB->getRawOriginal('employerNameEn');
        $mainPage->assertSee($employerBName);
    }
}

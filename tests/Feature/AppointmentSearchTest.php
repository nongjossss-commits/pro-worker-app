<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAppointment;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cross-date appointment search — the top-bar combined calendar and each of
 * Registration/Renewal/Workflow's own calendars can search by
 * employer/employee name, passport, ID no., etc. across EVERY upcoming
 * appointment (not just one day), grouped by date.
 */
class AppointmentSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('admin');
        $user->assignRole($role);
        foreach (['edit-employees', 'view-employers'] as $perm) {
            $user->givePermissionTo(Permission::findOrCreate($perm));
        }
        return $user;
    }

    public function test_combined_calendar_search_finds_matches_across_registration_renewal_and_workflow(): void
    {
        $user = $this->makeUser();

        // Registration-tab appointment.
        $regTab = ResolutionTab::create(['type' => 'registration', 'name' => 'Reg Tab', 'slug' => 'reg-tab']);
        $regEmployer = Employer::factory()->create(['employerNameTh' => 'บริษัท ค้นหาทดสอบ จำกัด']);
        $regEmployee = Employee::factory()->create([
            'employer_id' => $regEmployer->id,
            'status' => 'registration_pending',
            'resolution_tab_id' => $regTab->id,
            'employeePassport' => 'SEARCHME1234',
        ]);
        EmployeeAppointment::create([
            'employee_id' => $regEmployee->id,
            'resolution_tab_id' => $regTab->id,
            'appointment_date' => now()->addDays(3),
        ]);

        // Renewal-tab appointment, different employer, matched by passport.
        $renTab = ResolutionTab::create(['type' => 'renewal', 'name' => 'Ren Tab', 'slug' => 'ren-tab']);
        $renEmployer = Employer::factory()->create();
        $renEmployee = Employee::factory()->create([
            'employer_id' => $renEmployer->id,
            'status' => 'renewal_pending',
            'resolution_tab_id' => $renTab->id,
            'employeePassport' => 'SEARCHME5678',
        ]);
        EmployeeAppointment::create([
            'employee_id' => $renEmployee->id,
            'resolution_tab_id' => $renTab->id,
            'appointment_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->getJson(route('appointments.search', ['q' => 'ค้นหาทดสอบ']));
        $response->assertStatus(200);
        $this->assertStringContainsString($regEmployee->employeeNameEn, $response->json('html'));
        $this->assertStringNotContainsString($renEmployee->employeeNameEn, $response->json('html'));

        // Passport search finds the renewal one too.
        $byPassport = $this->actingAs($user)->getJson(route('appointments.search', ['q' => 'SEARCHME5678']));
        $byPassport->assertStatus(200);
        $this->assertStringContainsString($renEmployee->employeeNameEn, $byPassport->json('html'));

        // Empty search returns no html (not an error).
        $empty = $this->actingAs($user)->getJson(route('appointments.search', ['q' => '']));
        $empty->assertStatus(200)->assertJson(['html' => '', 'count' => 0]);
    }

    public function test_registration_tab_search_is_scoped_to_that_tab_only(): void
    {
        $user = $this->makeUser();
        $tabA = ResolutionTab::create(['type' => 'registration', 'name' => 'Tab A', 'slug' => 'tab-a']);
        $tabB = ResolutionTab::create(['type' => 'registration', 'name' => 'Tab B', 'slug' => 'tab-b']);

        $employer = Employer::factory()->create();
        $employeeInTabA = Employee::factory()->create([
            'employer_id' => $employer->id, 'status' => 'registration_pending', 'resolution_tab_id' => $tabA->id,
        ]);
        $employeeInTabB = Employee::factory()->create([
            'employer_id' => $employer->id, 'status' => 'registration_pending', 'resolution_tab_id' => $tabB->id,
        ]);
        EmployeeAppointment::create(['employee_id' => $employeeInTabA->id, 'resolution_tab_id' => $tabA->id, 'appointment_date' => now()->addDay()]);
        EmployeeAppointment::create(['employee_id' => $employeeInTabB->id, 'resolution_tab_id' => $tabB->id, 'appointment_date' => now()->addDay()]);

        $response = $this->actingAs($user)->getJson(route('production.registration.api.appointments_search', [
            'resolutionTab' => $tabA->id, 'q' => $employer->getRawOriginal('employerNameTh'),
        ]));
        $response->assertStatus(200);
        $html = $response->json('html');
        $this->assertStringContainsString($employeeInTabA->employeeNameEn, $html);
        $this->assertStringNotContainsString($employeeInTabB->employeeNameEn, $html);
    }

    public function test_renewal_tab_search_groups_results_by_date(): void
    {
        $user = $this->makeUser();
        $tab = ResolutionTab::create(['type' => 'renewal', 'name' => 'Renewal Tab', 'slug' => 'renewal-tab-grp']);
        $employer = Employer::factory()->create();

        $empDay1 = Employee::factory()->create(['employer_id' => $employer->id, 'status' => 'renewal_pending', 'resolution_tab_id' => $tab->id]);
        $empDay2 = Employee::factory()->create(['employer_id' => $employer->id, 'status' => 'renewal_pending', 'resolution_tab_id' => $tab->id]);
        EmployeeAppointment::create(['employee_id' => $empDay1->id, 'resolution_tab_id' => $tab->id, 'appointment_date' => now()->addDays(2)]);
        EmployeeAppointment::create(['employee_id' => $empDay2->id, 'resolution_tab_id' => $tab->id, 'appointment_date' => now()->addDays(9)]);

        $response = $this->actingAs($user)->getJson(route('production.renewal.api.appointments_search', [
            'resolutionTab' => $tab->id, 'q' => $employer->getRawOriginal('employerNameTh'),
        ]));
        $response->assertStatus(200)->assertJson(['count' => 2]);
        $html = $response->json('html');
        $this->assertStringContainsString($empDay1->employeeNameEn, $html);
        $this->assertStringContainsString($empDay2->employeeNameEn, $html);
        // Two distinct date headers present.
        $this->assertSame(2, substr_count($html, 'bi-calendar-event'));
    }

    public function test_workflow_search_matches_employee_and_employer_fields(): void
    {
        $user = $this->makeUser();
        $employer = Employer::factory()->create();
        $workType = WorkType::create(['name' => 'Search WT', 'slug' => 'search-wt-' . uniqid()]);
        $order = ProductionOrder::create(['employer_id' => $employer->id, 'work_type_id' => $workType->id, 'status' => 'active']);
        $employee = Employee::factory()->create(['employer_id' => $employer->id, 'employeePassport' => 'WFSEARCH999']);
        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => $employee->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(4),
        ]);

        $response = $this->actingAs($user)->getJson(route('workflow.api.appointments_search', ['q' => 'WFSEARCH999']));
        $response->assertStatus(200);
        $this->assertStringContainsString($employee->employeeNameEn, $response->json('html'));
    }
}

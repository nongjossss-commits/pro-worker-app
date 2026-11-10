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
 * 2026-11-10: the combined appointment calendar (top bar, every menu) lets
 * staff work in place — Edit the employee and Mark Completed (with undo) —
 * like each menu's own calendar. Checked in a browser on pages with and
 * without their own edit popup.
 */
class CombinedCalendarActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Employer $employer;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->employer = Employer::factory()->create();
    }

    private function resolutionAppointment(string $type): array
    {
        $tab = ResolutionTab::create(['name' => 'มติ ' . $type, 'type' => $type, 'slug' => 'cal-' . $type, 'sort_order' => 1]);
        $employee = Employee::factory()->create(['employer_id' => $this->employer->id, 'resolution_tab_id' => $tab->id, 'status' => $type . '_pending']);
        $appointment = EmployeeAppointment::create(['employee_id' => $employee->id, 'resolution_tab_id' => $tab->id, 'appointment_date' => now()->setTime(10, 0)]);

        return [$tab, $employee, $appointment];
    }

    private function workflowItem(): ProductionItem
    {
        $order = ProductionOrder::create([
            'employer_id' => $this->employer->id,
            'work_type_id' => WorkType::create(['name' => 'WF', 'slug' => 'wf-' . uniqid()])->id,
            'status' => 'active',
        ]);

        return ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => Employee::factory()->create(['employer_id' => $this->employer->id])->id,
            'status' => 'pending',
            'appointment_date' => now()->setTime(11, 0),
        ]);
    }

    public function test_cards_carry_edit_and_done_buttons_for_every_source(): void
    {
        [$tab, $employee] = $this->resolutionAppointment('registration');
        $item = $this->workflowItem();

        $html = $this->actingAs($this->admin)->getJson(route('appointments.by-date', ['date' => now()->toDateString()]))->assertOk()->json('html');

        $this->assertSame(2, substr_count($html, 'data-appt-edit'));
        $this->assertSame(2, substr_count($html, 'data-appt-done="1"'));
        $this->assertStringContainsString('data-appt-tab="' . $tab->id . '"', $html);
        $this->assertStringContainsString('data-appt-employee="' . $employee->id . '"', $html);
        $this->assertStringContainsString('data-appt-item="' . $item->id . '"', $html);

        // the cross-date search uses the same cards
        $search = $this->actingAs($this->admin)->getJson(route('appointments.search', ['q' => $employee->employeeNameEn]))->assertOk()->json('html');
        $this->assertStringContainsString('data-appt-done="1"', $search);
    }

    public function test_mark_completed_and_undo_for_each_source(): void
    {
        foreach (['registration', 'renewal'] as $type) {
            [$tab, $employee, $appointment] = $this->resolutionAppointment($type);
            $payload = ['source' => $type, 'employee_id' => $employee->id, 'resolution_tab_id' => $tab->id];

            $this->actingAs($this->admin)->postJson(route('appointments.complete'), $payload + ['done' => true])->assertOk();
            $this->assertNotNull($appointment->fresh()->appointment_completed_at, $type);
            // gone from the open list, like in the menu's own calendar
            $html = $this->getJson(route('appointments.by-date', ['date' => now()->toDateString()]))->json('html');
            $this->assertStringNotContainsString('data-appt-tab="' . $tab->id . '"', $html);

            $this->postJson(route('appointments.complete'), $payload + ['done' => false])->assertOk();
            $this->assertNull($appointment->fresh()->appointment_completed_at, $type);
        }

        $item = $this->workflowItem();
        $this->actingAs($this->admin)->postJson(route('appointments.complete'), ['source' => 'workflow', 'item_id' => $item->id, 'employee_id' => null, 'resolution_tab_id' => null, 'done' => true])->assertOk();
        $this->assertNotNull($item->fresh()->appointment_completed_at);
        $this->postJson(route('appointments.complete'), ['source' => 'workflow', 'item_id' => $item->id, 'done' => false])->assertOk();
        $this->assertNull($item->fresh()->appointment_completed_at);
    }

    public function test_only_people_allowed_to_edit_employees_and_only_what_they_can_see(): void
    {
        [$tab, $employee, $appointment] = $this->resolutionAppointment('registration');
        $item = $this->workflowItem();

        Permission::findOrCreate('edit-employees');
        $viewer = User::factory()->create();
        $this->actingAs($viewer)->postJson(route('appointments.complete'), ['source' => 'workflow', 'item_id' => $item->id, 'done' => true])->assertForbidden();
        $this->actingAs($viewer)->get(route('appointments.edit-kit'))->assertForbidden();

        // an employer account cannot touch another employer's appointments
        Role::findOrCreate('employer');
        $otherEmployer = Employer::factory()->create();
        $employerUser = User::factory()->create();
        $employerUser->assignRole('employer');
        $employerUser->givePermissionTo('edit-employees');
        $otherEmployer->update(['user_id' => $employerUser->id]);
        $this->actingAs($employerUser)->postJson(route('appointments.complete'), ['source' => 'workflow', 'item_id' => $item->id, 'done' => true])->assertNotFound();
        $this->actingAs($employerUser)->postJson(route('appointments.complete'), ['source' => 'registration', 'employee_id' => $employee->id, 'resolution_tab_id' => $tab->id, 'done' => true])->assertNotFound();
        $this->assertNull($item->fresh()->appointment_completed_at);
        $this->assertNull($appointment->fresh()->appointment_completed_at);
    }

    public function test_edit_popup_is_served_for_pages_without_one(): void
    {
        $html = $this->actingAs($this->admin)->get(route('appointments.edit-kit'))->assertOk()->getContent();
        $this->assertStringContainsString('id="editEmployeeModal"', $html);
        $this->assertStringContainsString('window.openEditEmployeeModal', $html);
        $this->assertStringContainsString('id="cropperModal"', $html);

        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString("route('appointments.edit-kit')", $layout);
        $this->assertStringContainsString('document.body.appendChild(editModal)', $layout, 'menu edit popups open above the calendar');
    }
}

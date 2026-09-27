<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Notification;
use App\Models\SuperAdminSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Notification cards/rows carry an "Edit Employee" button that opens the
 * shared edit modal (components/edit-employee-modal).
 */
class NotificationEditEmployeeButtonTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        return $user;
    }

    private function notificationFor(Employee $employee): Notification
    {
        return Notification::create([
            'employee_id' => $employee->id,
            'type' => 'visa_expiry',
            'message' => 'Visa expiring',
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => 'pending',
            'days_remaining' => 20,
        ]);
    }

    public function test_notification_card_has_edit_employee_button_and_modal(): void
    {
        $employee = Employee::factory()->create();
        $this->notificationFor($employee);

        $html = $this->actingAs($this->admin())->get(route('notifications.index'))->assertOk()->getContent();

        $this->assertStringContainsString('btn-notification-edit-employee', $html);
        $this->assertStringContainsString('data-edit-employee-id="' . $employee->id . '"', $html);
        $this->assertStringContainsString(route('employees.edit', $employee->id), $html);
        $this->assertStringContainsString('id="editEmployeeModal"', $html);
        $this->assertStringContainsString('window.openEditEmployeeModal', $html);
    }

    public function test_button_is_hidden_when_employees_menu_is_disabled(): void
    {
        $employee = Employee::factory()->create();
        $this->notificationFor($employee);
        SuperAdminSetting::create(['key' => 'employees', 'is_visible' => false]);
        Cache::flush();

        $html = $this->actingAs($this->admin())->get(route('notifications.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-edit-employee-id=', $html);
        $this->assertStringNotContainsString('id="editEmployeeModal"', $html);
    }
}

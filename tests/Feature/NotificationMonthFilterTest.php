<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Notifications → month + year filter searches the employees' actual dates,
 * so people whose document expires that month show up even before the alert
 * window opens (as "Not alerted yet", nothing is saved).
 */
class NotificationMonthFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_filter_lists_existing_alerts_and_employees_not_alerted_yet(): void
    {
        Role::findOrCreate('super-admin');
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); }
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);

        $target = now()->addMonths(5)->startOfMonth()->addDays(9);   // well outside any alert window
        $employer = Employer::factory()->create();
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'Future Visa', 'visaExpiryDate' => $target->toDateString()]);
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'Other Month', 'visaExpiryDate' => $target->copy()->addMonth()->toDateString()]);
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'Next Year', 'visaExpiryDate' => $target->copy()->addYear()->toDateString()]);
        $alerted = Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'Already Alerted', 'visaExpiryDate' => $target->toDateString()]);
        Notification::create(['employee_id' => $alerted->id, 'type' => 'visa_expiry', 'due_date' => $target->toDateString(), 'status' => 'unread', 'message' => 'x']);
        $notificationCount = Notification::count();

        $html = $this->get(route('notifications.index', ['month' => $target->month, 'year' => $target->year, 'active_tab' => 'visa_expiry']))
            ->assertOk()->getContent();

        $this->assertTrue(str_contains($html, 'Future Visa'), 'employee not alerted yet is missing');
        $this->assertTrue(str_contains($html, 'Already Alerted'), 'existing alert is missing');
        $this->assertFalse(str_contains($html, 'Other Month'), 'other month leaked in');
        $this->assertFalse(str_contains($html, 'Next Year'), 'same month of another year leaked in');
        $this->assertTrue(str_contains($html, __('Not alerted yet')), 'no "not alerted yet" label');
        $this->assertSame($notificationCount, Notification::count(), 'the filter must not create notifications');

        // Table view renders the same items.
        $table = $this->get(route('notifications.index', ['month' => $target->month, 'year' => $target->year, 'active_tab' => 'visa_expiry', 'view' => 'table']))
            ->assertOk()->getContent();
        $this->assertTrue(str_contains($table, 'Future Visa'), 'table: employee not alerted yet is missing');
    }
}

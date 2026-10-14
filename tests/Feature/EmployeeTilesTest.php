<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Employee lists outside the step-ticking menus show compact tiles
 * (partials/_employee_tiles, class "emp-tile-grid"); the card markup and
 * its controls are unchanged. Workflow keeps its own row cards.
 */
class EmployeeTilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_lists_use_the_tile_grid_and_workflow_does_not(): void
    {
        Role::findOrCreate('super-admin');
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); }
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $employer = Employer::factory()->create();
        Employee::factory()->count(3)->create(['employer_id' => $employer->id]);
        Employee::factory()->create(['employer_id' => $employer->id, 'terminated_at' => now()->subDays(3)]);

        $this->actingAs($admin);
        $employees = $this->get(route('employees.index'))->assertOk()->getContent();
        $this->assertStringContainsString('class="list-group emp-tile-grid"', $employees);
        // Same card controls as before inside the tiles.
        $this->assertStringContainsString('employee-checkbox', $employees);
        $this->assertStringContainsString('employee-actions-unified', $employees);
        // Running number as its own element (badge in tiles, "1." in rows).
        $this->assertStringContainsString('<span class="emp-card-no">1<span class="emp-card-no-dot">.</span></span>', $employees);
        // Selection: bigger checkboxes in selection mode; the card surface no longer toggles.
        $this->assertStringContainsString("classList.toggle('selection-mode'", $employees);
        $this->assertStringNotContainsString('function handleCardClick', $employees);
        // Nationality hook (flag + name beside the photo in tiles).
        $this->assertStringContainsString('emp-card-nat', $employees);

        $this->assertStringContainsString('class="list-group emp-tile-grid"', $this->get(route('employees.history'))->assertOk()->getContent());
        $this->assertStringContainsString('class="list-group emp-tile-grid"', $this->get(route('employers.edit', $employer))->assertOk()->getContent());

        $workflow = $this->get(route('workflow.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('class="list-group emp-tile-grid"', $workflow);
    }

    public function test_incomplete_data_and_notifications_use_tiles(): void
    {
        Role::findOrCreate('super-admin');
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); }
        \Spatie\Permission\Models\Permission::findOrCreate('manage-tickets');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $admin->givePermissionTo('manage-tickets');
        \App\Models\SystemConfig::updateOrCreate(['key' => 'employee_mandatory_fields'], ['value' => json_encode(['employeePhone', 'father_name'])]);
        $employer = Employer::factory()->create();
        Employee::factory()->create(['employer_id' => $employer->id, 'employeePhone' => null, 'father_name' => null, 'employeeNameEn' => 'Missing Two']);

        $this->actingAs($admin);
        $html = $this->get(route('admin.incomplete_employees.index'))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'class="list-group emp-tile-grid"'), 'incomplete: no tile grid');
        $this->assertTrue(str_contains($html, 'Missing Two'), 'incomplete: employee missing');
        $this->assertTrue(str_contains($html, 'emp-card-missing'), 'incomplete: missing-fields badge');

        $notifications = $this->get(route('notifications.index'))->assertOk()->getContent();
        $this->assertTrue(str_contains($notifications, '<div class="emp-tile-grid">'), 'notifications: no tile grid');
    }
}

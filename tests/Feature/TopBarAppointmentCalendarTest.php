<?php

namespace Tests\Feature;

use App\Models\JobCheckSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-07: a top-bar button (after Job Check Mode) opens the combined
 * appointment calendar from every menu; the bar's buttons collapse to icons
 * and stay inside the screen. The layout itself was checked in a browser at
 * 320–1700 px; these guard the wiring.
 */
class TopBarAppointmentCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::findOrCreate('super-admin');
        $u = User::factory()->create();
        $u->assignRole('super-admin');

        return $u;
    }

    public function test_button_opens_the_calendar_and_comes_right_after_job_check(): void
    {
        $html = $this->actingAs($this->superAdmin())->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="appointmentCalendarBtn"', $html);
        $this->assertStringContainsString('data-bs-target="#appointmentReminderModal"', $html);
        $this->assertStringContainsString('id="appointmentReminderModal"', $html);
        $this->assertTrue(strpos($html, 'id="jobCheckBtn"') < strpos($html, 'id="appointmentCalendarBtn"'), 'after Job Check Mode');
        $this->assertStringContainsString('app-topbar', $html);
    }

    public function test_download_center_and_job_check_are_no_longer_hidden_on_phones(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringNotContainsString('btn btn-outline-secondary d-none d-md-block" onclick="openDownloadCenter()"', $layout);
        $widget = file_get_contents(resource_path('views/components/job-check-widget.blade.php'));
        $this->assertStringNotContainsString('d-none d-md-flex', $widget);

        $css = file_get_contents(public_path('css/mobile-fit.css'));
        $this->assertStringContainsString('.app-topbar { flex-wrap: wrap;', $css);
    }

    public function test_no_button_without_the_permission_the_popup_needs(): void
    {
        Permission::findOrCreate('edit-employees');
        $u = User::factory()->create();

        $html = $this->actingAs($u)->get(route('profile.edit'));
        if ($html->status() === 200) {
            $this->assertStringNotContainsString('id="appointmentCalendarBtn"', $html->getContent());
        }
        $this->assertStringContainsString("@can('edit-employees')\n                    <button type=\"button\" id=\"appointmentCalendarBtn\"", str_replace("\r\n", "\n", file_get_contents(resource_path('views/layouts/app.blade.php'))));
    }

    public function test_calendar_data_opens_in_a_job_check_tab_without_fetch_headers(): void
    {
        $u = $this->superAdmin();
        JobCheckSession::create(['user_id' => $u->id, 'status' => 'active', 'started_at' => now(), 'business_date' => now()->toDateString(), 'sequence_in_day' => 1]);

        // no Sec-Fetch-Dest (plain-HTTP connections, older browsers)
        $this->actingAs($u)->get(route('appointments.calendar', ['month' => 11, 'year' => 2026, '_jc' => 1]))->assertOk();
        $this->actingAs($u)->get(route('appointments.by-date', ['date' => '2026-11-07', '_jc' => 1]))->assertOk();
        // other menus are still confined
        $this->actingAs($u)->get(route('employers.index', ['_jc' => 1]))->assertRedirect(route('workflow.index'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-10-30: the appointment calendar that pops up after login was 68% of
 * the screen on phones too (calendar spilled out, headers squeezed). Below
 * 992px it now uses the screen width with smaller day boxes; computers keep
 * the 68% dialog. The layout was checked in a browser at 390 / 768 / 1400 px.
 */
class AppointmentReminderMobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_modal_has_the_phone_layout(): void
    {
        Role::findOrCreate('super-admin');
        $u = User::factory()->create();
        $u->assignRole('super-admin');

        $html = $this->actingAs($u)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('modal-dialog modal-dialog-scrollable appt-reminder-dialog', $html);
        $this->assertStringContainsString('.appt-reminder-dialog { max-width: 68%; }', $html);
        $this->assertStringContainsString('.appt-reminder-dialog { max-width: none; width: auto; margin: .75rem; }', $html);
        foreach (['appt-cal-row', 'appt-cal-head', 'appt-cal-nav', 'appt-cal-grid', 'appt-cal-day', 'appt-list-head'] as $cls) {
            $this->assertStringContainsString($cls . '"', $html, $cls);
        }
        $this->assertStringNotContainsString('style="max-width: 68%;"', $html);
    }
}

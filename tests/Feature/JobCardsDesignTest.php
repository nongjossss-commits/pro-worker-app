<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ResolutionTab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-11: Registration / Renewal Resolution employee cards group their
 * info fields in one framed panel like Workflow (.reg-panel), and the
 * employer + employee cards in the three menus get the raised card design
 * (public/css/job-cards.css). Visual only — checked in a browser in light /
 * dark mode at 390, 1100 and 1440 px.
 */
class JobCardsDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_and_renewal_cards_use_the_panel_and_card_design(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);
        $employer = Employer::factory()->create();

        foreach (['registration', 'renewal'] as $type) {
            $tab = ResolutionTab::create(['name' => 'มติ ' . $type, 'type' => $type, 'slug' => 'jc-' . $type, 'sort_order' => 1]);
            $employee = Employee::factory()->create(['employer_id' => $employer->id, 'resolution_tab_id' => $tab->id, 'status' => $type . '_pending']);

            $page = $this->actingAs($admin)->get(route("production.{$type}.operations", $tab))->assertOk()->getContent();
            $this->assertStringContainsString('css/job-cards.css', $page, $type);

            // the employee list of the employer card (loaded on open)
            $cards = $this->get(route("production.{$type}.employer.employees", [$tab, $employer->id]))->assertOk()->getContent();
            $this->assertStringContainsString('class="reg-panel"', $cards, $type);
            foreach (['rp-cred', 'rp-appt', 'rp-remark', 'rp-ins', 'rp-ra', 'rp-req', 'rp-ref'] as $slot) {
                $this->assertStringContainsString($slot, $cards, "$type $slot");
            }
            $this->assertStringContainsString('.reg-panel {', $cards, 'panel styles come with the card');
            $this->assertStringContainsString('employee-card-' . $employee->id, $cards);
        }

        $this->assertStringContainsString('css/job-cards.css', $this->get(route('workflow.index', ['tab' => 'x']))->getContent());
    }

    public function test_card_colours_follow_the_theme(): void
    {
        $css = file_get_contents(public_path('css/job-cards.css'));
        // card surfaces come from the card's own theme variable (dark mode safe)
        $this->assertStringContainsString('var(--bs-card-bg, #ffffff)', $css);
        $this->assertStringNotContainsString('--jc-surface', $css);
    }
}

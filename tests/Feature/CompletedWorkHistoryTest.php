<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "Completed Work History" popup (2026-10-29): new title, a near-full-screen
 * popup of Employees-menu tiles (no steps), the selection bar works above the
 * popup, and tiles flag employees who have since been notified out or moved
 * to another employer.
 */
class CompletedWorkHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_cards_flag_notified_out_and_moved_employees(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $er = Employer::factory()->create();
        $other = Employer::factory()->create(['employerNameTh' => 'นายจ้างใหม่ทดสอบ']);
        $wt = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'is_system' => true, 'order' => 1]);
        $order = ProductionOrder::create(['employer_id' => $er->id, 'work_type_id' => $wt->id, 'status' => 'active']);

        $stayed = Employee::factory()->create(['employer_id' => $er->id]);
        $out = Employee::factory()->create(['employer_id' => $er->id]);
        $out->forceFill(['terminated_at' => '2026-10-20 00:00:00'])->save();
        $moved = Employee::factory()->create(['employer_id' => $other->id]);
        foreach ([$stayed, $out, $moved] as $emp) {
            ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp->id,
                'status' => 'completed', 'completed_at' => now()->subDays(3)]);
        }

        $html = $this->get(route('workflow.history', $order->id) . '?month=all')->assertOk()->getContent();

        $this->assertStringContainsString('emp-tile-grid', $html);
        $this->assertSame(3, substr_count($html, 'class="employee-card card'));
        $this->assertStringNotContainsString('id="item-card-', $html); // no step cards
        $this->assertSame(2, substr_count($html, 'history-status-badge'));
        $this->assertStringContainsString(__('Notified out :date', ['date' => '20/10/2026']), $html);
        $this->assertStringContainsString(__('Now with another employer: :name', ['name' => 'นายจ้างใหม่ทดสอบ']), $html);

        $page = $this->get(route('workflow.index', ['tab' => 'notify_in']))->assertOk()->getContent();
        $this->assertStringContainsString(__('Completed Work History'), $page);
        $this->assertStringContainsString('history-modal-wide', $page);
        $this->assertStringContainsString('id="terminateEmployeeModal"', $page); // the tiles' buttons need it
    }

    public function test_popup_lifts_the_selection_bar_and_select_all_covers_finished_cards(): void
    {
        $js = file_get_contents(public_path('js/history-filter.js'));
        $this->assertStringContainsString("document.getElementById('bulkActionBar')", $js);
        $this->assertStringContainsString('document.body.appendChild(bar)', $js);
        $this->assertStringContainsString('refreshGlobalSelectionUI', $js);

        $scripts = file_get_contents(resource_path('views/layouts/_app_scripts.blade.php'));
        $this->assertStringContainsString(".modal.show [data-history-list]", $scripts);
        $this->assertSame(2, substr_count($scripts, 'countsForSelectAll(cb, cardWrapper, historyScope)'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeRenewalLink;
use App\Models\EmployeeTeamAssignment;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Finished employees move into the employer's "Job History" popup after 24
 * hours (Workflow, Registration / Renewal resolution). The popup filters by
 * month / dates and has two search boxes: employee and team. Finished
 * employees keep the team they finished in — renaming / deleting a team only
 * touches employees still in progress.
 */
class CompletedHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
    }

    private function employee(Employer $employer, string $name, array $attrs = []): Employee
    {
        $e = Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => $name] + $attrs);
        if (array_key_exists('resolution_completed_at', $attrs)) {
            $e->forceFill(['resolution_completed_at' => $attrs['resolution_completed_at']])->saveQuietly();
        }
        return $e;
    }

    private function team(ResolutionTab $tab, Employee $e, string $name): void
    {
        EmployeeTeamAssignment::create(['employee_id' => $e->id, 'resolution_tab_id' => $tab->id, 'employer_id' => $e->employer_id, 'team_name' => $name]);
    }

    public function test_registration_finished_over_24h_move_to_history_with_month_and_search_filters(): void
    {
        $tab = ResolutionTab::create(['name' => 'มติทดสอบ', 'type' => 'registration', 'slug' => 'reg-' . uniqid()]);
        $er = Employer::factory()->create();
        $base = ['resolution_tab_id' => $tab->id];
        $this->employee($er, 'PENDINGPERSON', $base + ['status' => 'registration_pending']);
        $this->employee($er, 'FRESHDONE', $base + ['status' => 'registration_completed', 'resolution_completed_at' => now()->subHours(2)]);
        $sep = $this->employee($er, 'SEPDONE', $base + ['status' => 'registration_completed', 'resolution_completed_at' => '2026-09-10 10:00:00']);
        $aug = $this->employee($er, 'AUGDONE', $base + ['status' => 'registration_completed', 'resolution_completed_at' => '2026-08-05 10:00:00']);
        $this->employee($er, 'NODATEDONE', $base + ['status' => 'registration_completed', 'resolution_completed_at' => null]);
        $this->team($tab, $sep, 'ทีมโรงงาน A');
        $this->team($tab, $aug, 'ทีมโรงงาน B');

        $list = $this->get(route('production.registration.employer.employees', [$tab->id, $er->id]))->assertOk()->getContent();
        $this->assertTrue(str_contains($list, 'PENDINGPERSON') && str_contains($list, 'FRESHDONE'));
        $this->assertFalse(str_contains($list, 'SEPDONE') || str_contains($list, 'AUGDONE') || str_contains($list, 'NODATEDONE'));
        // the "Saved" counter filter still lists every finished employee
        $saved = $this->get(route('production.registration.employer.employees', [$tab->id, $er->id]) . '?filter=saved')->getContent();
        $this->assertTrue(str_contains($saved, 'SEPDONE') && str_contains($saved, 'NODATEDONE'));

        $url = route('production.registration.employer.history', [$tab->id, $er->id]);
        $h = $this->get($url)->assertOk()->getContent(); // default: latest month
        $this->assertTrue(str_contains($h, 'data-history-filter') && str_contains($h, 'SEPDONE'));
        $this->assertFalse(str_contains($h, 'AUGDONE') || str_contains($h, 'FRESHDONE'));

        $h = $this->get($url . '?month=2026-08')->getContent();
        $this->assertTrue(str_contains($h, 'AUGDONE') && ! str_contains($h, 'SEPDONE'));
        $h = $this->get($url . '?month=none')->getContent();
        $this->assertTrue(str_contains($h, 'NODATEDONE') && ! str_contains($h, 'SEPDONE'));
        // searching looks in every month
        $h = $this->get($url . '?q=AUGDONE')->getContent();
        $this->assertTrue(str_contains($h, 'AUGDONE') && ! str_contains($h, 'SEPDONE'));
        $h = $this->get($url . '?team=' . urlencode('โรงงาน B'))->getContent();
        $this->assertTrue(str_contains($h, 'AUGDONE') && ! str_contains($h, 'SEPDONE'));
    }

    public function test_registration_finished_employees_keep_their_team_on_rename_delete_and_reassign(): void
    {
        $tab = ResolutionTab::create(['name' => 'มติทดสอบ', 'type' => 'registration', 'slug' => 'reg-' . uniqid()]);
        $er = Employer::factory()->create();
        $doing = $this->employee($er, 'DOING', ['resolution_tab_id' => $tab->id, 'status' => 'registration_pending']);
        $done = $this->employee($er, 'DONE', ['resolution_tab_id' => $tab->id, 'status' => 'registration_completed', 'resolution_completed_at' => now()->subDays(3)]);
        $this->team($tab, $doing, 'ทีมเก่า');
        $this->team($tab, $done, 'ทีมเก่า');

        $this->postJson(route('production.registration.team.rename', $tab->id), ['employer_id' => $er->id, 'old_name' => 'ทีมเก่า', 'new_name' => 'ทีมใหม่'])->assertOk();
        $this->assertSame('ทีมใหม่', EmployeeTeamAssignment::where('employee_id', $doing->id)->value('team_name'));
        $this->assertSame('ทีมเก่า', EmployeeTeamAssignment::where('employee_id', $done->id)->value('team_name'));

        $this->postJson(route('production.registration.team.delete', $tab->id), ['employer_id' => $er->id, 'name' => 'ทีมเก่า'])->assertOk();
        $this->assertSame('ทีมเก่า', EmployeeTeamAssignment::where('employee_id', $done->id)->value('team_name'));

        $this->postJson(route('production.registration.team.update', [$tab->id, $done->id]), ['team_name' => 'อื่น'])->assertStatus(422);
        $this->assertSame('ทีมเก่า', EmployeeTeamAssignment::where('employee_id', $done->id)->value('team_name'));
        // still found by the old team name in the history popup
        $h = $this->get(route('production.registration.employer.history', [$tab->id, $er->id]) . '?team=' . urlencode('ทีมเก่า'))->getContent();
        $this->assertTrue(str_contains($h, 'DONE') && ! str_contains($h, 'DOING'));
    }

    public function test_renewal_history_includes_dual_listed_employees_finished_on_their_link(): void
    {
        $tab = ResolutionTab::create(['name' => 'มติต่ออายุทดสอบ', 'type' => 'renewal', 'slug' => 'ren-' . uniqid()]);
        $er = Employer::factory()->create();
        $this->employee($er, 'PRIMARYOLD', ['resolution_tab_id' => $tab->id, 'status' => 'renewal_completed', 'resolution_completed_at' => now()->subDays(2)]);
        $linked = $this->employee($er, 'LINKEDOLD', ['status' => 'registration_completed']);
        $link = EmployeeRenewalLink::create(['employee_id' => $linked->id, 'resolution_tab_id' => $tab->id, 'status' => 'renewal_completed']);
        $link->forceFill(['resolution_completed_at' => now()->subDays(2)])->save();
        $this->team($tab, $linked, 'ทีมลิงก์');

        $list = $this->get(route('production.renewal.employer.employees', [$tab->id, $er->id]))->assertOk()->getContent();
        $this->assertFalse(str_contains($list, 'PRIMARYOLD') || str_contains($list, 'LINKEDOLD'));

        $h = $this->get(route('production.renewal.employer.history', [$tab->id, $er->id]) . '?month=all')->assertOk()->getContent();
        $this->assertTrue(str_contains($h, 'PRIMARYOLD') && str_contains($h, 'LINKEDOLD'));
        $h = $this->get(route('production.renewal.employer.history', [$tab->id, $er->id]) . '?team=' . urlencode('ลิงก์'))->getContent();
        $this->assertTrue(str_contains($h, 'LINKEDOLD') && ! str_contains($h, 'PRIMARYOLD'));

        $this->postJson(route('production.renewal.team.rename', $tab->id), ['employer_id' => $er->id, 'old_name' => 'ทีมลิงก์', 'new_name' => 'X'])->assertOk();
        $this->assertSame('ทีมลิงก์', EmployeeTeamAssignment::where('employee_id', $linked->id)->value('team_name'));
    }

    public function test_workflow_history_filters_and_team_freeze(): void
    {
        $wt = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'is_system' => true, 'order' => 1]);
        $er = Employer::factory()->create();
        $order = ProductionOrder::create(['employer_id' => $er->id, 'work_type_id' => $wt->id, 'status' => 'active']);
        $mk = fn ($name, $status, $at, $team) => ProductionItem::create([
            'production_order_id' => $order->id, 'employee_id' => Employee::factory()->create(['employer_id' => $er->id, 'employeeNameEn' => $name])->id,
            'status' => $status, 'completed_at' => $at, 'group_name' => $team,
        ]);
        $doing = $mk('WFDOING', 'pending', null, 'ทีม 1');
        $sep = $mk('WFSEP', 'completed', '2026-09-03 09:00:00', 'ทีม 1');
        $mk('WFAUG', 'completed', '2026-08-03 09:00:00', 'ทีม 2');

        $url = route('workflow.history', $order->id);
        $h = $this->get($url)->assertOk()->getContent();
        $this->assertTrue(str_contains($h, 'WFSEP') && ! str_contains($h, 'WFAUG'));
        $h = $this->get($url . '?team=' . urlencode('ทีม 2'))->getContent();
        $this->assertTrue(str_contains($h, 'WFAUG') && ! str_contains($h, 'WFSEP'));

        $this->postJson(route('workflow.order.team.rename', $order->id), ['old_name' => 'ทีม 1', 'new_name' => 'ทีม 9'])->assertOk();
        $this->assertSame('ทีม 9', $doing->fresh()->group_name);
        $this->assertSame('ทีม 1', $sep->fresh()->group_name);
        $this->postJson(route('workflow.item.group', $sep->id), ['group_name' => 'อื่น'])->assertStatus(422);
        $this->assertSame('ทีม 1', $sep->fresh()->group_name);
    }
}

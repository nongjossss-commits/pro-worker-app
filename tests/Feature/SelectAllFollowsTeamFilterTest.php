<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-03: with a card's team filter on, "select all" on that card took
 * EVERY employee of the card, not just the filtered team (Workflow,
 * Pre-production, Registration, Renewal). The select-all endpoints now apply
 * the same team rule as the list, and the page sends the team pick plus the
 * other list filters (checked in a browser on Workflow).
 */
class SelectAllFollowsTeamFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $u = User::factory()->create();
        $u->assignRole('super-admin');
        $this->actingAs($u);
    }

    private function ids(array $json): array
    {
        $rows = $json['items'] ?? $json['employees'] ?? $json;
        return collect($rows)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    public function test_workflow_and_preproduction_select_all_take_only_the_filtered_teams(): void
    {
        $er = Employer::factory()->create();
        $tab = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'is_system' => true, 'order' => 1]);
        foreach (['active' => 'workflow.order.select_all_ids', 'pre_production' => 'production.order.select_all_ids'] as $status => $route) {
            $order = ProductionOrder::create(['employer_id' => $er->id, 'work_type_id' => $tab->id, 'status' => $status]);
            $a = Employee::factory()->create(['employer_id' => $er->id]);
            $b = Employee::factory()->create(['employer_id' => $er->id]);
            $c = Employee::factory()->create(['employer_id' => $er->id]);
            ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $a->id, 'status' => 'pending', 'group_name' => 'ทีม 1']);
            ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $b->id, 'status' => 'pending', 'group_name' => 'ทีม 2']);
            ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $c->id, 'status' => 'pending', 'group_name' => null]);

            $all = $this->getJson(route($route, $order->id))->assertOk()->json();
            $this->assertSame([$a->id, $b->id, $c->id], $this->ids($all), $route . ' no filter');

            $one = $this->getJson(route($route, $order->id) . '?team_filter[]=' . urlencode('ทีม 1'))->assertOk()->json();
            $this->assertSame([$a->id], $this->ids($one), $route . ' team 1');

            $two = $this->getJson(route($route, $order->id) . '?team_filter[]=' . urlencode('ทีม 1') . '&team_filter[]=' . urlencode('ทีม 2'))->assertOk()->json();
            $this->assertSame([$a->id, $b->id], $this->ids($two), $route . ' team 1 + 2');
        }
    }

    public function test_registration_and_renewal_select_all_take_only_the_filtered_teams(): void
    {
        $er = Employer::factory()->create();
        foreach (['registration', 'renewal'] as $type) {
            $tab = ResolutionTab::create(['name' => 'มติ ' . $type, 'type' => $type, 'slug' => 'sel-' . $type, 'sort_order' => 1]);
            $people = Employee::factory()->count(3)->create(['employer_id' => $er->id, 'resolution_tab_id' => $tab->id, 'status' => $type . '_pending']);
            DB::table('employee_team_assignments')->insert([
                ['employee_id' => $people[0]->id, 'resolution_tab_id' => $tab->id, 'team_name' => 'A', 'employer_id' => $er->id, 'created_at' => now(), 'updated_at' => now()],
                ['employee_id' => $people[1]->id, 'resolution_tab_id' => $tab->id, 'team_name' => 'B', 'employer_id' => $er->id, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $url = route('production.' . $type . '.employer.select_all_ids', ['resolutionTab' => $tab->id, 'employer' => $er->id]);

            $this->assertSame($people->pluck('id')->sort()->values()->all(), $this->ids($this->getJson($url)->assertOk()->json()), $type . ' no filter');
            $this->assertSame([$people[0]->id], $this->ids($this->getJson($url . '?team_filter[]=A')->assertOk()->json()), $type . ' team A');
        }
    }

    public function test_pages_send_the_team_pick_and_every_list_filter(): void
    {
        $helper = file_get_contents(resource_path('views/layouts/_app_scripts.blade.php'));
        $this->assertStringContainsString("url.searchParams.append('team_filter[]', name)", $helper);
        foreach (['employer_filter_name', 'employee_pick_id', 'renewal_filters'] as $param) {
            $this->assertStringContainsString("'{$param}'", $helper);
        }
        foreach ([
            'views/workflow/index.blade.php' => 'window.orderTeamFilters[orderId]',
            'views/production/_index_scripts.blade.php' => 'window.orderTeamFilters[orderId]',
            'views/production/registration/index.blade.php' => 'window.employerTeamFilters[employerId]',
            'views/production/renewal/index.blade.php' => 'window.employerTeamFilters[employerId]',
        ] as $view => $state) {
            $this->assertStringContainsString('teams: (' . explode('[', $state)[0] . ' && ' . $state . ') || []', file_get_contents(resource_path($view)), $view);
        }
    }
}

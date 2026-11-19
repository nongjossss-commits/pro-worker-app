<?php

namespace Tests\Feature\Production;

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
 * 2026-11-19:
 *  - Workflow: searching for a person shows them in the card even if finished
 *    long ago (green, labelled) or cancelled (grey, labelled); no search = as before.
 *  - "แจ้งเข้า บต.52/55/56": the team from "แจ้งเข้า / เปลี่ยนนายจ้าง" comes along;
 *    team chips list every team of the employer's cards; filtering gathers that
 *    team from all of the employer's cards (finished cards too), never other teams.
 *  - Employees menu search offers notified-out matches as links to their page.
 *  - app:repair-missed-notify-in-transfers moves employees whose finished
 *    change-employer job never moved them (only with --apply).
 */
class WorkflowSearchAndTeamsTest extends TestCase
{
    use RefreshDatabase;

    private Employer $employer;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);
        $this->employer = Employer::factory()->create(['employerNameTh' => 'บริษัท ซีล จำกัด']);
    }

    public function test_person_search_shows_finished_and_cancelled_employees_in_the_card(): void
    {
        $type = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'order' => 1]);
        $order = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => $type->id, 'type' => 'employer', 'status' => 'active']);
        $done = Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => 'PATY TI OO']);
        $gone = Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => 'CANCELLED GUY']);
        ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $done->id, 'status' => 'completed', 'completed_at' => now()->subDays(40)]);
        ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $gone->id, 'status' => 'cancelled']);
        ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => Employee::factory()->create(['employer_id' => $this->employer->id])->id, 'status' => 'pending']);

        // no search: the long-finished employee stays out of the card (unchanged)
        $plain = $this->get(route('workflow.items', $order->id))->assertOk()->getContent();
        $this->assertStringNotContainsString('PATY TI OO', $plain);
        $this->assertStringNotContainsString('search-reveal', $plain);

        // picked from the search box → shown, green, labelled
        $html = $this->get(route('workflow.items', [$order->id, 'employee_pick_id' => $done->id, 'employer_filter_name' => 'PATY TI OO']))->getContent();
        $this->assertStringContainsString('PATY TI OO', $html);
        $this->assertStringContainsString(__('Finished — shown because you searched for this employee'), $html);

        // typed name of a cancelled employee → shown (grey), labelled
        $html = $this->get(route('workflow.items', [$order->id, 'search' => 'CANCELLED GUY']))->getContent();
        $this->assertStringContainsString('status-cancelled search-reveal', $html);
        $this->assertStringContainsString(__('Cancelled — shown because you searched for this employee'), $html);

        // searching the employer itself is not a person search → card as usual
        $html = $this->get(route('workflow.items', [$order->id, 'search' => 'บริษัท ซีล จำกัด']))->getContent();
        $this->assertStringNotContainsString('PATY TI OO', $html);
    }

    public function test_bt52_brings_the_team_and_filters_it_across_the_employers_cards(): void
    {
        $notifyIn = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'is_system' => true, 'order' => 1]);
        $report = WorkType::where('slug', WorkType::SLUG_NOTIFY_IN_REPORT)->firstOrFail();
        $change = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => $notifyIn->id, 'status' => 'active']);
        $src = [];
        foreach (['ALPHA ONE' => 'ทีม A', 'ALPHA TWO' => 'ทีม A', 'BRAVO ONE' => 'ทีม B'] as $name => $team) {
            $e = Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => $name]);
            $src[$name] = ProductionItem::create(['production_order_id' => $change->id, 'employee_id' => $e->id, 'status' => 'completed', 'completed_at' => now(), 'group_name' => $team]);
        }

        // two rounds → two cards of the same employer
        $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => [$src['ALPHA ONE']->id, $src['BRAVO ONE']->id]])->assertOk();
        $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => [$src['ALPHA TWO']->id]])->assertOk();
        $cards = ProductionOrder::where('work_type_id', $report->id)->orderBy('id')->get();
        $this->assertCount(2, $cards);
        $this->assertSame(['ทีม A', 'ทีม B'], ProductionItem::whereIn('production_order_id', $cards->pluck('id'))->orderBy('id')->pluck('group_name')->unique()->sort()->values()->all());

        // the first card is finished — still counts
        $cards[0]->update(['status' => 'completed']);

        // chips on the second card list the teams of BOTH cards
        $page = $this->get(route('workflow.index', ['tab' => $report->slug, 'view_completed' => 1]))->getContent()
              . $this->get(route('workflow.index', ['tab' => $report->slug]))->getContent();
        $this->assertStringContainsString('data-team-name="ทีม B"', $page, 'team from the other (finished) card');

        // filtering "ทีม A" on the second card gathers ALPHA ONE from the first card too, but not team B
        $html = $this->get(route('workflow.items', [$cards[1]->id, 'team_filter' => ['ทีม A']]))->assertOk()->getContent();
        $this->assertStringContainsString('ALPHA ONE', $html);
        $this->assertStringContainsString('ALPHA TWO', $html);
        $this->assertStringNotContainsString('BRAVO ONE', $html);
        $this->assertStringContainsString(__('From another card: :name', ['name' => $cards[0]->project_name]), $html);
        $this->assertStringContainsString(__('Team (change employer): :team', ['team' => 'ทีม A']), $html);

        // two teams ticked → both
        $html = $this->get(route('workflow.items', [$cards[1]->id, 'team_filter' => ['ทีม A', 'ทีม B']]))->getContent();
        $this->assertStringContainsString('BRAVO ONE', $html);

        // no filter → only this card's own employees
        $html = $this->get(route('workflow.items', $cards[1]->id))->getContent();
        $this->assertStringContainsString('ALPHA TWO', $html);
        $this->assertStringNotContainsString('ALPHA ONE', $html);
    }

    public function test_employees_search_offers_notified_out_matches_as_links(): void
    {
        Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => 'PATY TI OO', 'terminated_at' => '2026-07-21']);

        $rows = collect($this->getJson(route('employees.preview_search_combined', ['q' => 'PATY']))->assertOk()->json());
        $hit = $rows->firstWhere('label', fn ($l) => str_contains($l, 'PATY TI OO')) ?? $rows->first(fn ($r) => str_contains($r['label'], 'PATY TI OO'));
        $this->assertNotNull($hit);
        $this->assertSame(__('Notified out :date', ['date' => '21/07/2026']), $hit['group']);
        $this->assertStringContainsString('/employees/history', $hit['url']);
        $this->assertArrayNotHasKey('pick', $hit);
    }

    public function test_repair_moves_employees_whose_finished_change_employer_job_never_moved_them(): void
    {
        $notifyIn = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'order' => 1]);
        $old = Employer::factory()->create();
        $card = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => $notifyIn->id, 'type' => 'employer', 'status' => 'active']);

        $stuck = Employee::factory()->create(['employer_id' => $old->id, 'terminated_at' => '2026-07-21', 'status' => 'resigned']);
        ProductionItem::create(['production_order_id' => $card->id, 'employee_id' => $stuck->id, 'status' => 'completed', 'completed_at' => '2026-07-23 10:00:00', 'is_transfer_processed' => false]);
        // notified out AFTER the job finished → a separate, later event: left alone
        $later = Employee::factory()->create(['employer_id' => $old->id, 'terminated_at' => '2026-09-01']);
        ProductionItem::create(['production_order_id' => $card->id, 'employee_id' => $later->id, 'status' => 'completed', 'completed_at' => '2026-07-23 10:00:00', 'is_transfer_processed' => false]);
        // not notified out (already moved / working elsewhere) → left alone
        $active = Employee::factory()->create(['employer_id' => $old->id]);
        ProductionItem::create(['production_order_id' => $card->id, 'employee_id' => $active->id, 'status' => 'completed', 'completed_at' => '2026-07-23 10:00:00', 'is_transfer_processed' => false]);

        $this->artisan('app:repair-missed-notify-in-transfers')->assertSuccessful();
        $this->assertSame($old->id, $stuck->fresh()->employer_id, 'list only without --apply');

        $this->artisan('app:repair-missed-notify-in-transfers', ['--apply' => true])->assertSuccessful();
        $fresh = $stuck->fresh();
        $this->assertSame($this->employer->id, $fresh->employer_id);
        $this->assertNull($fresh->terminated_at);
        $this->assertSame('active', $fresh->status);
        $this->assertSame($old->id, $later->fresh()->employer_id);
        $this->assertSame($old->id, $active->fresh()->employer_id);
    }
}

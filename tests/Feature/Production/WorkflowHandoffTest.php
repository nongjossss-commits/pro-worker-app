<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkflowHandoff;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-11: Workflow "send to another tab" — tick employees, send them to
 * another Workflow tab, and whoever opens that tab adds them to an employer
 * card (one-card tabs: that card; multi-card tabs: a chosen card or a new one).
 */
class WorkflowHandoffTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Employer $employer;
    private WorkType $source;
    private WorkType $oneCard;
    private WorkType $multiCard;
    private ProductionOrder $sourceCard;
    /** @var Employee[] */
    private array $people = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);
        Permission::findOrCreate('manage-own-workflow');
        Role::findOrCreate('staff')->givePermissionTo('manage-own-workflow');
        $this->staff = User::factory()->create();
        $this->staff->assignRole('staff');

        $this->employer = Employer::factory()->create();
        $this->source = WorkType::create(['name' => 'แจ้งเข้า', 'slug' => 'src-' . uniqid(), 'order' => 1]);
        $this->oneCard = WorkType::create(['name' => 'ประกันสังคม', 'slug' => 'sso-' . uniqid(), 'order' => 2]);
        $this->multiCard = WorkType::create(['name' => 'MOU', 'slug' => 'mou-' . uniqid(), 'order' => 3, 'allow_multiple_orders' => true]);

        $this->sourceCard = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => $this->source->id, 'type' => 'employer', 'status' => 'active']);
        foreach (range(1, 3) as $i) {
            $e = Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => "WORKER {$i}"]);
            ProductionItem::create(['production_order_id' => $this->sourceCard->id, 'employee_id' => $e->id, 'status' => 'pending']);
            $this->people[] = $e;
        }
        $this->actingAs($this->staff);
    }

    private function send(WorkType $to, array $people, ?int $employerId = null)
    {
        return $this->postJson(route('workflow.handoffs.send'), [
            'target_work_type_id' => $to->id,
            'source_work_type_id' => $this->source->id,
            'employees' => collect($people)->map(fn ($e) => ['id' => $e->id, 'employer_id' => $employerId ?? $this->employer->id])->all(),
        ])->assertOk();
    }

    private function pendingIds(WorkType $tab): array
    {
        return WorkflowHandoff::where('target_work_type_id', $tab->id)->where('status', 'pending')->pluck('id')->all();
    }

    public function test_send_waits_in_the_target_tab_and_leaves_the_source_card_alone(): void
    {
        $res = $this->send($this->oneCard, $this->people);
        $this->assertSame(3, $res->json('sent'));
        $this->assertCount(3, $this->pendingIds($this->oneCard));
        $this->assertSame(3, $this->sourceCard->items()->count(), 'nothing moved out of the source card');

        // sending the same people again: already waiting
        $again = $this->send($this->oneCard, $this->people);
        $this->assertSame(0, $again->json('sent'));
        $this->assertCount(3, $again->json('skipped'));

        // someone with an open job in the target tab is not sent
        $existing = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => $this->multiCard->id, 'type' => 'employer', 'status' => 'active']);
        ProductionItem::create(['production_order_id' => $existing->id, 'employee_id' => $this->people[0]->id, 'status' => 'pending']);
        $res = $this->send($this->multiCard, $this->people);
        $this->assertSame(2, $res->json('sent'));
        $this->assertSame('WORKER 1', $res->json('skipped.0.name'));
    }

    public function test_a_made_up_employer_falls_back_to_the_employees_own(): void
    {
        $stranger = Employer::factory()->create();
        $this->send($this->oneCard, [$this->people[0]], $stranger->id);
        $this->assertSame($this->employer->id, WorkflowHandoff::first()->employer_id);
    }

    public function test_receive_into_a_one_card_tab_uses_or_creates_the_employers_card(): void
    {
        $this->send($this->oneCard, $this->people);
        $list = $this->getJson(route('workflow.handoffs.index', ['work_type_id' => $this->oneCard->id]))->assertOk();
        $this->assertCount(3, $list->json('handoffs'));
        $this->assertFalse($list->json('tab.multiple'));

        $ids = $this->pendingIds($this->oneCard);
        $res = $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->oneCard->id, 'handoff_ids' => [$ids[0], $ids[1]]])->assertOk();
        $this->assertSame(2, $res->json('added'));

        $card = ProductionOrder::where('work_type_id', $this->oneCard->id)->where('employer_id', $this->employer->id)->sole();
        $this->assertSame(2, $card->items()->count());
        $this->assertSame('received', WorkflowHandoff::find($ids[0])->status);
        $this->assertNotNull(WorkflowHandoff::find($ids[0])->production_item_id);

        // the third goes into the SAME card (one card per employer)
        $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->oneCard->id, 'handoff_ids' => [$ids[2]]])->assertOk();
        $this->assertSame(1, ProductionOrder::where('work_type_id', $this->oneCard->id)->count());
        $this->assertSame(3, $card->items()->count());

        // already handled → refused, nothing added twice
        $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->oneCard->id, 'handoff_ids' => $ids])->assertStatus(422);
        $this->assertSame(3, $card->items()->count());
    }

    public function test_receive_into_a_multi_card_tab_asks_which_card(): void
    {
        $cardA = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => $this->multiCard->id, 'type' => 'employer', 'status' => 'active', 'project_name' => 'A']);
        $this->send($this->multiCard, $this->people);
        $ids = $this->pendingIds($this->multiCard);

        $list = $this->getJson(route('workflow.handoffs.index', ['work_type_id' => $this->multiCard->id]))->assertOk();
        $this->assertTrue($list->json('tab.multiple'));
        $this->assertSame($cardA->id, $list->json('employers.0.cards.0.id'));

        // no card chosen → refused, nothing added
        $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->multiCard->id, 'handoff_ids' => $ids])->assertStatus(422);
        $this->assertSame(0, $cardA->items()->count());

        // a card of another employer / another tab → refused, rolled back
        $other = ProductionOrder::create(['employer_id' => Employer::factory()->create()->id, 'work_type_id' => $this->multiCard->id, 'type' => 'employer', 'status' => 'active']);
        $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->multiCard->id, 'handoff_ids' => $ids, 'cards' => [$this->employer->id => (string) $other->id]])->assertStatus(422);
        $this->assertSame(0, ProductionItem::where('production_order_id', $other->id)->count());
        $this->assertCount(3, $this->pendingIds($this->multiCard));

        // existing card
        $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->multiCard->id, 'handoff_ids' => [$ids[0]], 'cards' => [$this->employer->id => (string) $cardA->id]])->assertOk();
        $this->assertSame(1, $cardA->items()->count());

        // new card
        $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->multiCard->id, 'handoff_ids' => [$ids[1], $ids[2]], 'cards' => [$this->employer->id => 'new']])->assertOk();
        $new = ProductionOrder::where('work_type_id', $this->multiCard->id)->where('employer_id', $this->employer->id)->whereKeyNot($cardA->id)->sole();
        $this->assertSame(2, $new->items()->count());
    }

    public function test_someone_added_by_hand_meanwhile_stays_waiting_and_can_be_dismissed(): void
    {
        $this->send($this->oneCard, [$this->people[0], $this->people[1]]);
        $ids = $this->pendingIds($this->oneCard);
        $byHand = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => $this->oneCard->id, 'type' => 'employer', 'status' => 'active']);
        ProductionItem::create(['production_order_id' => $byHand->id, 'employee_id' => $this->people[0]->id, 'status' => 'pending']);

        $list = $this->getJson(route('workflow.handoffs.index', ['work_type_id' => $this->oneCard->id]))->json('handoffs');
        $this->assertNotNull(collect($list)->firstWhere('employee_id', $this->people[0]->id)['blocked']);

        $res = $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->oneCard->id, 'handoff_ids' => $ids])->assertOk();
        $this->assertSame(1, $res->json('added'));
        $this->assertCount(1, $res->json('skipped'));
        $this->assertSame(2, $byHand->items()->count(), 'no second job for the same person');

        $this->postJson(route('workflow.handoffs.dismiss'), ['handoff_ids' => $this->pendingIds($this->oneCard)])->assertOk();
        $this->assertCount(0, $this->pendingIds($this->oneCard));
        $this->assertSame('dismissed', WorkflowHandoff::where('employee_id', $this->people[0]->id)->value('status'));
    }

    public function test_sent_to_notify_in_a_notified_out_employee_rejoins_like_adding_by_hand(): void
    {
        $notifyIn = WorkType::create(['name' => 'แจ้งเข้า/เปลี่ยนนายจ้าง', 'slug' => 'notify_in', 'order' => 4]);
        $newEmployer = Employer::factory()->create();
        $person = $this->people[0];
        $person->update(['terminated_at' => now()->subDay(), 'termination_reason' => 'resigned']);
        ProductionItem::create(['production_order_id' => ProductionOrder::create(['employer_id' => $newEmployer->id, 'work_type_id' => $this->source->id, 'type' => 'employer', 'status' => 'active'])->id, 'employee_id' => $person->id, 'status' => 'pending']);

        $this->send($notifyIn, [$person], $newEmployer->id);
        $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $notifyIn->id, 'handoff_ids' => $this->pendingIds($notifyIn)])->assertOk();

        $person->refresh();
        $this->assertSame($newEmployer->id, $person->employer_id);
        $this->assertNull($person->terminated_at);
    }

    public function test_only_people_who_manage_workflow(): void
    {
        $viewer = User::factory()->create();
        $this->actingAs($viewer);
        $this->postJson(route('workflow.handoffs.send'), ['target_work_type_id' => $this->oneCard->id, 'employees' => [['id' => $this->people[0]->id]]])->assertForbidden();
        $this->getJson(route('workflow.handoffs.index', ['work_type_id' => $this->oneCard->id]))->assertForbidden();
        $this->postJson(route('workflow.handoffs.receive'), ['work_type_id' => $this->oneCard->id, 'handoff_ids' => [1]])->assertForbidden();
        $this->postJson(route('workflow.handoffs.dismiss'), ['handoff_ids' => [1]])->assertForbidden();
    }

    public function test_workflow_page_shows_the_menu_badge_and_banner(): void
    {
        $this->send($this->oneCard, $this->people);
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $html = $this->get(route('workflow.index', ['tab' => $this->oneCard->slug]))->assertOk()->getContent();
        $this->assertStringContainsString('openHandoffSendModal', $html);
        $this->assertStringContainsString('id="handoffInboxModal"', $html);
        $this->assertStringContainsString(__(':count employees were sent to this tab', ['count' => 3]), $html);
        $this->assertStringContainsString('bi-inbox-fill me-1"></i>3', $html, 'tab badge');

        $dash = $this->get(route('workflow.index'))->assertOk()->getContent();
        $this->assertStringContainsString(__('Employees sent to tabs, waiting:'), $dash);
    }
}

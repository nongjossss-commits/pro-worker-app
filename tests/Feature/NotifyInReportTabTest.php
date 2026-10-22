<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Workflow tab 5 "แจ้งเข้า บต.52 / บต.55 / บต.56" (slug notify_in_report):
 *  - "Add Employees" offers employees finished in the change-employer tab
 *    (notify_in) after tab 5 existed, grouped by the new employer, each only once;
 *  - creating makes one NEW card per employer per round;
 *  - steps are ticked on the card for everyone (per-employee tick refused);
 *  - finishing the card only closes the job (employee data untouched).
 */
class NotifyInReportTabTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private WorkType $notifyIn;
    private WorkType $report;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->actingAs($this->admin);

        $this->notifyIn = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'is_system' => true, 'order' => 1]);
        $this->report = WorkType::where('slug', WorkType::SLUG_NOTIFY_IN_REPORT)->firstOrFail(); // created by the migration
    }

    /** A change-employer card for $employer with one item per employee in $state. */
    private function changeEmployerJob(Employer $employer, array $employees, string $state = 'completed', $completedAt = null): array
    {
        $order = ProductionOrder::create(['employer_id' => $employer->id, 'work_type_id' => $this->notifyIn->id, 'status' => 'active']);
        return collect($employees)->map(fn ($e) => ProductionItem::create([
            'production_order_id' => $order->id, 'employee_id' => $e->id, 'status' => $state,
            'completed_at' => $state === 'completed' ? ($completedAt ?? now()) : null,
        ]))->all();
    }

    public function test_tab_is_a_fixed_system_tab_with_multiple_cards(): void
    {
        $this->assertTrue($this->report->is_system);
        $this->assertTrue($this->report->allow_multiple_orders);
        $this->assertTrue($this->report->isNotifyInReport());
    }

    public function test_pick_finished_change_employer_employees_one_new_card_per_employer_per_round(): void
    {
        $a = Employer::factory()->create(['employerNameTh' => 'นายจ้าง A']);
        $b = Employer::factory()->create(['employerNameTh' => 'นายจ้าง B']);
        $aEmps = Employee::factory()->count(3)->create(['employer_id' => $a->id]);
        $b1 = Employee::factory()->create(['employer_id' => $b->id]);
        $this->changeEmployerJob($a, $aEmps->all());
        $this->changeEmployerJob($b, [$b1]);
        // not offered: still in progress / finished before tab 5 existed
        $this->changeEmployerJob($a, [Employee::factory()->create(['employer_id' => $a->id])], 'pending');
        $this->changeEmployerJob($b, [Employee::factory()->create(['employer_id' => $b->id])], 'completed', $this->report->created_at->copy()->subDay());

        $groups = collect($this->getJson(route('workflow.notify_in_report.candidates'))->assertOk()->json('groups'));
        $this->assertSame(['นายจ้าง A' => 3, 'นายจ้าง B' => 1], $groups->mapWithKeys(fn ($g) => [$g['employer_name'] => count($g['employees'])])->all());
        $this->assertTrue($groups->first()['employees'][0]['transfer_pending'], 'still inside the 24h countdown');

        $ids = $groups->flatMap(fn ($g) => collect($g['employees'])->pluck('source_item_id'))->all();
        $res = $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => $ids])->assertOk()->json();
        $this->assertSame([2, 4], [$res['cards'], $res['employees']]);

        $cards = ProductionOrder::where('work_type_id', $this->report->id)->get();
        $this->assertSame(2, $cards->count());
        $this->assertSame(3, $cards->firstWhere('employer_id', $a->id)->items()->count());
        $this->assertSame(4, ProductionItem::whereNotNull('source_item_id')->count());
        // nobody is offered twice
        $this->assertSame([], $this->getJson(route('workflow.notify_in_report.candidates'))->json('groups'));

        // next round for employer A → a NEW card (the first one is left alone)
        $this->changeEmployerJob($a, [Employee::factory()->create(['employer_id' => $a->id])]);
        $next = collect($this->getJson(route('workflow.notify_in_report.candidates'))->json('groups'));
        $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => collect($next[0]['employees'])->pluck('source_item_id')->all()])->assertOk();
        $this->assertSame(2, ProductionOrder::where('work_type_id', $this->report->id)->where('employer_id', $a->id)->count());
    }

    public function test_steps_ticked_on_the_card_for_everyone_and_finishing_leaves_employees_alone(): void
    {
        $a = Employer::factory()->create();
        $emps = Employee::factory()->count(2)->create(['employer_id' => $a->id]);
        $src = $this->changeEmployerJob($a, $emps->all());
        $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => collect($src)->pluck('id')->all()])->assertOk();
        $card = ProductionOrder::where('work_type_id', $this->report->id)->firstOrFail();
        $step = WorkTypeStep::create(['work_type_id' => $this->report->id, 'name' => 'ยื่น บต.52', 'order' => 1]);

        $this->postJson(route('workflow.order.card_step_toggle', $card), ['step_id' => $step->id, 'completed' => true])->assertOk();
        foreach ($card->items as $item) {
            $this->assertTrue($item->completedWorkTypeSteps()->where('work_type_steps.id', $step->id)->exists(), 'every employee got the step');
        }
        // per-employee tick is refused in this tab
        $this->postJson(route('workflow.step.toggle', $card->items->first()->id), ['step_id' => $step->id, 'completed' => false])->assertStatus(422);
        // untick on the card removes it for everyone
        $this->postJson(route('workflow.order.card_step_toggle', $card), ['step_id' => $step->id, 'completed' => false])->assertOk();
        $this->assertSame(0, \DB::table('production_item_step')->where('work_type_step_id', $step->id)->count());

        $before = $emps->map(fn ($e) => $e->fresh()->only(['employer_id', 'status', 'terminated_at']))->all();
        $this->postJson(route('workflow.order.finalize', $card))->assertOk();
        $this->assertSame('completed', $card->fresh()->status);
        $this->assertSame(['completed', 'completed'], $card->items()->pluck('status')->all());
        $this->assertSame($before, $emps->map(fn ($e) => $e->fresh()->only(['employer_id', 'status', 'terminated_at']))->all(), 'employee data untouched');
    }

    public function test_tab_page_shows_add_employees_and_card_steps(): void
    {
        $a = Employer::factory()->create();
        $src = $this->changeEmployerJob($a, [Employee::factory()->create(['employer_id' => $a->id])]);
        $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => [$src[0]->id]])->assertOk();
        WorkTypeStep::create(['work_type_id' => $this->report->id, 'name' => 'ยื่น บต.55', 'order' => 1]);

        $html = $this->get(route('workflow.index', ['tab' => WorkType::SLUG_NOTIFY_IN_REPORT]))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'openNotifyInReportModal()'), 'add employees button');
        $this->assertTrue(str_contains($html, 'id="notifyInReportModal"'), 'picker window');
        $this->assertTrue(str_contains($html, 'window.toggleCardStep('), 'card-level step buttons');
        $this->assertFalse(str_contains($html, 'data-bs-target="#createJobModal"'), 'no plain Create Job here');
    }
}

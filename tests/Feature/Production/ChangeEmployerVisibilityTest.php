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
 * 2026-11-19 — where a changing-employer employee shows up, step by step:
 *  - notified out, not yet on a change-employer card → NOT in the Employees
 *    list (only in "ลูกจ้างที่ถูกแจ้งออก")
 *  - put on the new employer's change-employer card → in the Employees list
 *    under the new employer, and gone from "ลูกจ้างที่ถูกแจ้งออก"
 *  - the "แจ้งออก" ⇄ "แจ้งเข้า" notes show only while BOTH jobs are open
 */
class ChangeEmployerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Employer $old;
    private Employer $new;
    private WorkType $in;
    private WorkType $out;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $u = User::factory()->create();
        $u->assignRole('super-admin');
        $this->actingAs($u);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);
        $this->old = Employer::factory()->create(['employerNameTh' => 'นายจ้างเก่า จำกัด']);
        $this->new = Employer::factory()->create(['employerNameTh' => 'นายจ้างใหม่ จำกัด']);
        $this->in = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'order' => 1]);
        $this->out = WorkType::firstOrCreate(['slug' => 'notify_out'], ['name' => 'แจ้งออก', 'order' => 2]);
    }

    private function employeesList(string $q): int
    {
        return substr_count($this->get(route('employees.index', ['employer_filter_name' => $q]))->assertOk()->getContent(), 'class="emp-card-no"');
    }

    // a card on "ลูกจ้างที่ถูกแจ้งออก" (not just the name echoed in the search box)
    private function inNotifiedOutPage(Employee $e): bool
    {
        return str_contains($this->get(route('employees.history'))->assertOk()->getContent(),
            'history-employee-checkbox" type="checkbox" value="' . $e->id . '"');
    }

    public function test_notified_out_employee_is_only_on_the_notified_out_page_until_put_on_a_change_employer_card(): void
    {
        $e = Employee::factory()->create(['employer_id' => $this->old->id, 'employeeNameEn' => 'WAITING WORKER', 'terminated_at' => now()->subDays(2), 'status' => 'resigned']);

        $this->assertSame(0, $this->employeesList('WAITING WORKER'), 'not in the Employees list while notified out');
        $this->assertTrue($this->inNotifiedOutPage($e), 'found on the notified-out page');

        $this->post(route('workflow.store'), ['employer_id' => $this->new->id, 'work_type_id' => $this->in->id, 'employee_ids' => [$e->id]]);

        $this->assertSame(1, $this->employeesList('WAITING WORKER'), 'in the Employees list right away');
        $this->assertSame($this->new->id, $e->fresh()->employer_id, 'under the new employer');
        $this->assertFalse($this->inNotifiedOutPage($e), 'gone from the notified-out page');
    }

    public function test_notes_show_only_while_both_jobs_are_open(): void
    {
        $noteOnOut = __('Changing employer — being notified in at: :name', ['name' => 'นายจ้างใหม่ จำกัด']);
        $noteOnIn = __('Notify-out job still open at the old employer: :name', ['name' => 'นายจ้างเก่า จำกัด']);

        // case 1: the notify-out job is finished first
        $a = Employee::factory()->create(['employer_id' => $this->old->id, 'employeeNameEn' => 'CASE ONE', 'status' => 'active']);
        $outCard = ProductionOrder::create(['employer_id' => $this->old->id, 'work_type_id' => $this->out->id, 'type' => 'employer', 'status' => 'active']);
        $outJob = ProductionItem::create(['production_order_id' => $outCard->id, 'employee_id' => $a->id, 'status' => 'pending', 'notify_out_date' => now()->toDateString()]);
        $this->post(route('workflow.store'), ['employer_id' => $this->new->id, 'work_type_id' => $this->in->id, 'employee_ids' => [$a->id]]);
        $inCard = ProductionOrder::where('employer_id', $this->new->id)->where('work_type_id', $this->in->id)->firstOrFail();
        $inJob = ProductionItem::where('production_order_id', $inCard->id)->where('employee_id', $a->id)->firstOrFail();

        $this->assertStringContainsString($noteOnOut, $this->get(route('workflow.items', $outCard->id))->getContent());
        $this->assertStringContainsString($noteOnIn, $this->get(route('workflow.items', $inCard->id))->getContent());

        $this->postJson(route('workflow.item.finalize', $outJob->id))->assertOk();
        $this->assertStringNotContainsString($noteOnIn, $this->get(route('workflow.items', $inCard->id))->getContent(), 'notify-out done → no note on the change-employer card');
        $this->assertStringNotContainsString($noteOnOut, $this->get(route('workflow.items', $outCard->id))->getContent(), 'the finished notify-out card shows no note either');

        // case 2: the change-employer job is finished first
        $b = Employee::factory()->create(['employer_id' => $this->old->id, 'employeeNameEn' => 'CASE TWO', 'status' => 'active']);
        $outJob2 = ProductionItem::create(['production_order_id' => $outCard->id, 'employee_id' => $b->id, 'status' => 'pending', 'notify_out_date' => now()->toDateString()]);
        $this->post(route('workflow.store'), ['employer_id' => $this->new->id, 'work_type_id' => $this->in->id, 'employee_ids' => [$b->id]]);
        $inJob2 = ProductionItem::where('production_order_id', $inCard->id)->where('employee_id', $b->id)->firstOrFail();
        $this->assertStringContainsString($noteOnOut, $this->get(route('workflow.items', $outCard->id))->getContent());

        $this->postJson(route('workflow.item.finalize', $inJob2->id))->assertOk();
        $this->assertStringNotContainsString($noteOnOut, $this->get(route('workflow.items', $outCard->id))->getContent(), 'change-employer done → no note on the notify-out card');
        $this->assertSame('pending', $outJob2->fresh()->status, 'the notify-out job itself is still there to finish');

        // case 3: a cancelled job on either side → no note
        $c = Employee::factory()->create(['employer_id' => $this->old->id, 'employeeNameEn' => 'CASE THREE', 'status' => 'active']);
        $outJob3 = ProductionItem::create(['production_order_id' => $outCard->id, 'employee_id' => $c->id, 'status' => 'pending', 'notify_out_date' => now()->toDateString()]);
        $this->post(route('workflow.store'), ['employer_id' => $this->new->id, 'work_type_id' => $this->in->id, 'employee_ids' => [$c->id]]);
        ProductionItem::where('production_order_id', $inCard->id)->where('employee_id', $c->id)->update(['status' => 'cancelled']);
        $this->assertStringNotContainsString($noteOnOut, $this->get(route('workflow.items', $outCard->id))->getContent());
    }
}

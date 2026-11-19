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
 * 2026-11-19 — an employee changing employer moves to the new employer as soon
 * as both are true: they are notified out / being notified out at the old one,
 * and they sit on an open "แจ้งเข้า / เปลี่ยนนายจ้าง" card of the new one — in
 * whichever order those happen. The old employer's "แจ้งออก" job is never
 * removed: it stays on its card until finished, with a note pointing at the
 * new employer (and the change-employer card points back at it).
 */
class ChangeEmployerMoveTest extends TestCase
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

    private function addToChangeEmployer(Employee $e, bool $pre = false): ProductionOrder
    {
        $this->post(route('workflow.store'), array_filter([
            'employer_id' => $this->new->id,
            'work_type_id' => $this->in->id,
            'employee_ids' => [$e->id],
            'is_pre_production' => $pre ? 1 : null,
        ]))->assertSessionHasNoErrors();

        return ProductionOrder::where('employer_id', $this->new->id)->where('work_type_id', $this->in->id)->latest('id')->firstOrFail();
    }

    private function notifyOutJob(Employee $e): ProductionItem
    {
        $card = ProductionOrder::firstOrCreate(
            ['employer_id' => $this->old->id, 'work_type_id' => $this->out->id, 'status' => 'active'],
            ['type' => 'employer', 'project_name' => 'แจ้งออก - นายจ้างเก่า']
        );

        return ProductionItem::create(['production_order_id' => $card->id, 'employee_id' => $e->id, 'status' => 'pending', 'notify_out_date' => now()->toDateString()]);
    }

    private function assertMovedToNew(Employee $e): void
    {
        $e = $e->fresh();
        $this->assertSame($this->new->id, $e->employer_id, 'moved to the new employer');
        $this->assertNull($e->terminated_at, 'no longer listed as notified out');
        $this->assertNotSame('resigned', $e->status);
    }

    public function test_notified_out_first_then_added_moves_at_once_workflow_and_pre_production(): void
    {
        foreach ([false, true] as $pre) {
            $e = Employee::factory()->create(['employer_id' => $this->old->id, 'terminated_at' => now()->subDays(3), 'status' => 'resigned']);
            $this->addToChangeEmployer($e, $pre);
            $this->assertMovedToNew($e);
        }
    }

    public function test_being_notified_out_in_workflow_then_added_moves_at_once_and_the_notify_out_job_stays(): void
    {
        $e = Employee::factory()->create(['employer_id' => $this->old->id, 'status' => 'active', 'employeeNameEn' => 'MOVER ONE']);
        $outJob = $this->notifyOutJob($e);

        $inCard = $this->addToChangeEmployer($e);
        $this->assertMovedToNew($e);

        // the notify-out job is still open, on the old employer's card, with a note
        $this->assertSame('pending', $outJob->fresh()->status);
        $html = $this->get(route('workflow.items', $outJob->production_order_id))->assertOk()->getContent();
        $this->assertStringContainsString('MOVER ONE', $html);
        $this->assertStringContainsString(__('Changing employer — being notified in at: :name', ['name' => 'นายจ้างใหม่ จำกัด']), $html);

        // …and the change-employer card points back at it
        $html = $this->get(route('workflow.items', $inCard->id))->assertOk()->getContent();
        $this->assertStringContainsString(__('Notify-out job still open at the old employer: :name', ['name' => 'นายจ้างเก่า จำกัด']), $html);

        // the Employees menu finds them under the new employer
        $page = $this->get(route('employees.index', ['employer_filter_name' => 'MOVER ONE']))->assertOk()->getContent();
        $this->assertStringContainsString('MOVER ONE', $page);

        // finishing the notify-out job later does not mark them resigned again
        $this->postJson(route('workflow.item.finalize', $outJob->id))->assertOk();
        $this->assertSame('completed', $outJob->fresh()->status);
        $this->assertMovedToNew($e);
    }

    public function test_added_first_then_notified_out_by_hand_moves_at_once(): void
    {
        $e = Employee::factory()->create(['employer_id' => $this->old->id, 'status' => 'active']);
        $this->addToChangeEmployer($e);
        $this->assertSame($this->old->id, $e->fresh()->employer_id, 'still employed at the old one — unchanged until notified out');

        $this->post(route('employees.terminate', $e), ['termination_date' => now()->toDateString(), 'termination_reason' => 'เปลี่ยนนายจ้าง']);
        $this->assertMovedToNew($e);
    }

    public function test_added_first_then_notify_out_job_finished_moves_at_once(): void
    {
        // on the change-employer card first, the notify-out job opened afterwards
        $f = Employee::factory()->create(['employer_id' => $this->old->id, 'status' => 'active']);
        $this->addToChangeEmployer($f);
        $fJob = $this->notifyOutJob($f);
        $this->assertSame($this->old->id, $f->fresh()->employer_id);

        $this->postJson(route('workflow.item.finalize', $fJob->id))->assertOk();
        $this->assertMovedToNew($f);
        $this->assertSame('completed', $fJob->fresh()->status);
    }

    public function test_no_open_change_employer_card_means_a_plain_notify_out(): void
    {
        $e = Employee::factory()->create(['employer_id' => $this->old->id, 'status' => 'active']);
        // a cancelled change-employer card does not count
        $card = ProductionOrder::create(['employer_id' => $this->new->id, 'work_type_id' => $this->in->id, 'type' => 'employer', 'status' => 'cancelled']);
        ProductionItem::create(['production_order_id' => $card->id, 'employee_id' => $e->id, 'status' => 'pending']);

        $this->post(route('employees.terminate', $e), ['termination_date' => now()->toDateString()]);
        $fresh = $e->fresh();
        $this->assertSame($this->old->id, $fresh->employer_id);
        $this->assertNotNull($fresh->terminated_at);
    }

    public function test_repair_moves_employees_left_notified_out_with_an_open_change_employer_job(): void
    {
        $e = Employee::factory()->create(['employer_id' => $this->old->id, 'status' => 'active']);
        $card = ProductionOrder::create(['employer_id' => $this->new->id, 'work_type_id' => $this->in->id, 'type' => 'employer', 'status' => 'active']);
        $item = ProductionItem::create(['production_order_id' => $card->id, 'employee_id' => $e->id, 'status' => 'pending']);
        // left over from before the rule: notified out without the observer running
        Employee::withoutEvents(fn () => $e->update(['terminated_at' => now()->subWeek(), 'status' => 'resigned']));

        $this->artisan('app:repair-missed-notify-in-transfers')->assertSuccessful();
        $this->assertNotNull($e->fresh()->terminated_at, 'dry run changes nothing');

        $this->artisan('app:repair-missed-notify-in-transfers', ['--apply' => true])->assertSuccessful();
        $this->assertMovedToNew($e);
        $this->assertSame('pending', $item->fresh()->status, 'the job itself is left as it is');
    }
}

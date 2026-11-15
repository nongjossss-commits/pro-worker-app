<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-15: notifying an employee out by hand ("Terminate") and adding them
 * to "แจ้งเข้า / เปลี่ยนนายจ้าง" under a new employer must NOT make their
 * unfinished Workflow jobs at the old employer disappear — notifying out can
 * take days. The jobs stay until someone finishes them; finishing them after
 * the move does not pull the employee back or mark them resigned again.
 */
class NotifyOutKeepsOpenJobsTest extends TestCase
{
    use RefreshDatabase;

    private Employer $oldEmployer;
    private Employer $newEmployer;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $admin->givePermissionTo(Permission::findOrCreate('terminate-employees'));
        $this->actingAs($admin);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);

        $this->oldEmployer = Employer::factory()->create();
        $this->newEmployer = Employer::factory()->create();
        $this->employee = Employee::factory()->create(['employer_id' => $this->oldEmployer->id, 'status' => 'active']);
    }

    private function openJob(string $slug, ?Employee $employee = null, ?Employer $employer = null): ProductionItem
    {
        $type = WorkType::firstOrCreate(['slug' => $slug], ['name' => $slug, 'order' => 1]);
        $order = ProductionOrder::firstOrCreate(
            ['employer_id' => ($employer ?? $this->oldEmployer)->id, 'work_type_id' => $type->id, 'status' => 'active'],
            ['type' => 'employer']
        );

        return ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => ($employee ?? $this->employee)->id, 'status' => 'pending']);
    }

    private function terminateAndMoveToNewEmployer(): void
    {
        $this->post(route('employees.terminate', $this->employee), ['termination_date' => now()->toDateString(), 'termination_reason' => 'ย้ายนายจ้าง'])->assertRedirect();
        $this->assertNotNull($this->employee->fresh()->terminated_at);

        $notifyIn = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'order' => 1]);
        $this->postJson(route('workflow.store'), [
            'work_type_id' => $notifyIn->id, 'employer_id' => $this->newEmployer->id, 'employee_ids' => [$this->employee->id],
        ])->assertOk()->assertJson(['success' => true]);

        $fresh = $this->employee->fresh();
        $this->assertSame($this->newEmployer->id, $fresh->employer_id, 'moved to the new employer right away (unchanged rule)');
        $this->assertNull($fresh->terminated_at);
    }

    public function test_open_jobs_at_the_old_employer_stay_open_after_notify_out_and_move(): void
    {
        $notifyOut = $this->openJob('notify_out');
        $other = $this->openJob('social_security');

        $this->terminateAndMoveToNewEmployer();

        $this->assertSame('pending', $notifyOut->fresh()->status, 'notify_out job is no longer auto-cancelled');
        $this->assertSame('pending', $other->fresh()->status);
        $this->assertStringNotContainsString('Auto-cancelled', (string) $notifyOut->fresh()->remarks);
    }

    public function test_finishing_the_kept_notify_out_job_does_not_terminate_the_employee_again(): void
    {
        $notifyOut = $this->openJob('notify_out');
        $this->terminateAndMoveToNewEmployer();

        $notifyOut->update(['notify_out_date' => now()->subDay()->toDateString()]);
        $this->postJson(route('workflow.item.finalize', $notifyOut->id))->assertOk();

        $this->assertSame('completed', $notifyOut->fresh()->status);
        $fresh = $this->employee->fresh();
        $this->assertNull($fresh->terminated_at, 'still working at the new employer');
        $this->assertNotSame('resigned', $fresh->status);
        $this->assertSame($this->newEmployer->id, $fresh->employer_id);
    }

    public function test_notify_out_for_an_employee_still_at_that_employer_works_as_before(): void
    {
        $stays = Employee::factory()->create(['employer_id' => $this->oldEmployer->id, 'status' => 'active']);
        $item = $this->openJob('notify_out', $stays);
        $item->update(['notify_out_date' => now()->toDateString(), 'notify_out_reason' => 'ลาออก']);

        $this->postJson(route('workflow.item.finalize', $item->id))->assertOk();

        $this->assertNotNull($stays->fresh()->terminated_at);
        $this->assertSame('resigned', $stays->fresh()->status);
    }

    public function test_finishing_a_kept_mou_renewal_job_does_not_pull_the_employee_back(): void
    {
        $renewal = $this->openJob('mou_renewal');
        $this->terminateAndMoveToNewEmployer();

        $this->postJson(route('workflow.item.finalize', $renewal->id))->assertOk();

        $this->assertSame('completed', $renewal->fresh()->status);
        $this->assertSame($this->newEmployer->id, $this->employee->fresh()->employer_id);
    }

    public function test_the_card_says_the_employee_moved(): void
    {
        $other = $this->openJob('social_security');
        $this->terminateAndMoveToNewEmployer();

        $html = $this->get(route('workflow.item.card', $other->id))->assertOk()->getContent();
        $this->assertStringContainsString(__('Now with employer: :name', ['name' => $this->newEmployer->employerNameTh]), $html);
    }
}

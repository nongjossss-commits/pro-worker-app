<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\EmployeeRenewalLink;
use App\Models\Employer;
use App\Models\ResolutionTab;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-15: Registration / Renewal Resolution after a manual "แจ้งออก":
 * the employee's card stays under the old employer (it does not vanish the
 * moment they are notified out); once they are added to "แจ้งเข้า / เปลี่ยน
 * นายจ้าง" under a new employer, the card moves to the new employer.
 */
class NotifyOutResolutionCardsTest extends TestCase
{
    use RefreshDatabase;

    private Employer $a;
    private Employer $b;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $admin->givePermissionTo(Permission::findOrCreate('terminate-employees'));
        $this->actingAs($admin);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);
        $this->a = Employer::factory()->create();
        $this->b = Employer::factory()->create();
    }

    private function cardShown(string $type, ResolutionTab $tab, Employer $employer, Employee $employee): bool
    {
        $html = $this->get(route("production.{$type}.employer.employees", ['resolutionTab' => $tab->id, 'employer' => $employer->id]))
            ->assertOk()->getContent();

        return str_contains($html, 'id="employee-card-' . $employee->id . '"');
    }

    private function notifyOutThenJoinB(Employee $employee): void
    {
        $this->post(route('employees.terminate', $employee), ['termination_date' => now()->toDateString()])->assertRedirect(); // no reason given — must not crash
        $this->assertNotNull($employee->fresh()->terminated_at);
    }

    private function joinB(Employee $employee): void
    {
        $notifyIn = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'order' => 1]);
        $this->postJson(route('workflow.store'), ['work_type_id' => $notifyIn->id, 'employer_id' => $this->b->id, 'employee_ids' => [$employee->id]])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertSame($this->b->id, $employee->fresh()->employer_id);
    }

    public function test_registration_card(): void
    {
        $tab = ResolutionTab::create(['name' => 'มติลงทะเบียน', 'type' => 'registration', 'slug' => 'reg-no', 'sort_order' => 1]);
        $e = Employee::factory()->create(['employer_id' => $this->a->id, 'resolution_tab_id' => $tab->id, 'status' => 'registration_pending']);

        $this->notifyOutThenJoinB($e);
        $this->assertTrue($this->cardShown('registration', $tab, $this->a, $e), 'stays under the old employer after notify-out');

        $this->joinB($e);
        $this->assertTrue($this->cardShown('registration', $tab, $this->b, $e), 'moves to the new employer');
        $this->assertFalse($this->cardShown('registration', $tab, $this->a, $e), 'no longer under the old employer');
    }

    public function test_renewal_card(): void
    {
        $tab = ResolutionTab::create(['name' => 'มติต่ออายุ', 'type' => 'renewal', 'slug' => 'ren-no', 'sort_order' => 1]);
        $e = Employee::factory()->create(['employer_id' => $this->a->id, 'resolution_tab_id' => $tab->id, 'status' => 'renewal_pending']);

        $this->notifyOutThenJoinB($e);
        $this->assertTrue($this->cardShown('renewal', $tab, $this->a, $e), 'stays under the old employer after notify-out');

        $this->joinB($e);
        $this->assertTrue($this->cardShown('renewal', $tab, $this->b, $e));
        $this->assertFalse($this->cardShown('renewal', $tab, $this->a, $e));
    }

    public function test_registration_employee_also_listed_in_renewal(): void
    {
        $reg = ResolutionTab::create(['name' => 'มติลงทะเบียน', 'type' => 'registration', 'slug' => 'reg-dl', 'sort_order' => 1]);
        $ren = ResolutionTab::create(['name' => 'มติต่ออายุ', 'type' => 'renewal', 'slug' => 'ren-dl', 'sort_order' => 2]);
        $e = Employee::factory()->create(['employer_id' => $this->a->id, 'resolution_tab_id' => $reg->id, 'status' => 'registration_pending']);
        EmployeeRenewalLink::create(['employee_id' => $e->id, 'resolution_tab_id' => $ren->id, 'status' => 'renewal_pending']);

        $this->assertTrue($this->cardShown('renewal', $ren, $this->a, $e), 'listed in renewal before');
        $this->notifyOutThenJoinB($e);
        $this->assertTrue($this->cardShown('renewal', $ren, $this->a, $e), 'stays under the old employer after notify-out');

        $this->joinB($e);
        $this->assertTrue($this->cardShown('renewal', $ren, $this->b, $e));
        $this->assertFalse($this->cardShown('renewal', $ren, $this->a, $e));
    }

    public function test_the_24h_notify_in_transfer_keeps_the_resolution_status(): void
    {
        $tab = ResolutionTab::create(['name' => 'มติต่ออายุ', 'type' => 'renewal', 'slug' => 'ren-24', 'sort_order' => 1]);
        $e = Employee::factory()->create(['employer_id' => $this->a->id, 'resolution_tab_id' => $tab->id, 'status' => 'renewal_pending', 'terminated_at' => now()->subDays(2)]);
        $notifyIn = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'order' => 1]);
        $order = \App\Models\ProductionOrder::create(['employer_id' => $this->b->id, 'work_type_id' => $notifyIn->id, 'type' => 'employer', 'status' => 'active']);
        \App\Models\ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $e->id, 'status' => 'completed', 'completed_at' => now()->subHours(30), 'is_transfer_processed' => false]);

        $this->artisan('app:process-employee-transfers')->assertSuccessful();

        $fresh = $e->fresh();
        $this->assertSame($this->b->id, $fresh->employer_id);
        $this->assertSame('renewal_pending', $fresh->status, 'still in Renewal Resolution');
        $this->assertTrue($this->cardShown('renewal', $tab, $this->b, $e));
    }
}

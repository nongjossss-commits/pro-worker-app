<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAppointment;
use App\Models\Employer;
use App\Models\FinancialPayment;
use App\Models\FinancialTransaction;
use App\Models\ProductionFinancialGroup;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Executive dashboard: renders, counts the workforce the stated way, and
 * never shows money figures (any account with the dashboard menu sees it —
 * even a super admin with the Finance menu open gets no cash / receivable
 * numbers). Shows appointments, 90-day reports and hires vs notified out.
 */
class ExecutiveDashboardTest extends TestCase
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

    public function test_dashboard_counts_and_has_no_money_figures(): void
    {
        $er = Employer::factory()->create();
        $active = Employee::factory()->count(3)->create(['employer_id' => $er->id, 'status' => 'active']);
        Employee::factory()->create(['employer_id' => $er->id, 'status' => 'registration_cancelled']); // not workforce
        Employee::factory()->create(['employer_id' => $er->id, 'status' => 'active', 'terminated_at' => now()]); // notified out

        // a paid bill — its amount must not appear anywhere on the dashboard
        $order = ProductionOrder::create(['employer_id' => $er->id, 'type' => 'employer']);
        $group = ProductionFinancialGroup::create(['production_order_id' => $order->id, 'name' => 'General']);
        $t = FinancialTransaction::create(['production_order_id' => $order->id, 'production_financial_group_id' => $group->id,
            'type' => 'installment', 'amount' => 987654, 'paid_amount' => 987654, 'status' => 'paid']);
        FinancialPayment::create(['financial_transaction_id' => $t->id, 'amount' => 987654, 'paid_at' => now()]);

        // appointments: today + in 3 days (counted), done one and one in 10 days (not)
        $tab = ResolutionTab::create(['name' => 'มติทดสอบ', 'type' => 'registration', 'slug' => 'reg-' . uniqid()]);
        EmployeeAppointment::create(['employee_id' => $active[0]->id, 'resolution_tab_id' => $tab->id, 'appointment_date' => now()->setTime(15, 0)]);
        EmployeeAppointment::create(['employee_id' => $active[1]->id, 'resolution_tab_id' => $tab->id, 'appointment_date' => now()->addDays(3)]);
        EmployeeAppointment::create(['employee_id' => $active[2]->id, 'resolution_tab_id' => $tab->id, 'appointment_date' => now()->addDay(), 'appointment_completed_at' => now()]);
        $tab2 = ResolutionTab::create(['name' => 'มติทดสอบ 2', 'type' => 'renewal', 'slug' => 'ren-' . uniqid()]);
        EmployeeAppointment::create(['employee_id' => $active[2]->id, 'resolution_tab_id' => $tab2->id, 'appointment_date' => now()->addDays(10)]);

        // 90-day report in 5 days
        $active[0]->update(['ninetyDayReportDate' => now()->addDays(5)->toDateString()]);

        $res = $this->get(route('dashboard'))->assertOk();
        $this->assertSame(3, $res->viewData('workforceCount'));
        $this->assertSame(2, $res->viewData('appointments')['week']);
        $this->assertSame(1, $res->viewData('appointments')['today']);
        $this->assertSame(1, $res->viewData('ninetyDay')['d7']);
        $this->assertSame(4, array_sum($res->viewData('movement')['hired'])); // 3 active + the notified-out one
        $this->assertSame(1, array_sum($res->viewData('movement')['left']));

        $html = $res->getContent();
        $this->assertFalse(str_contains($html, '987,654') || str_contains($html, '987654'), 'no bill amount on the dashboard');
        $this->assertFalse(str_contains($html, 'id="edIncome"'));
        $this->assertTrue(str_contains($html, 'id="edMovement"') && str_contains($html, 'kpi-detail-appointments') && str_contains($html, 'kpi-detail-ninety'));
    }
}

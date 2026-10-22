<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\FinancialPayment;
use App\Models\FinancialTransaction;
use App\Models\ProductionFinancialGroup;
use App\Models\ProductionOrder;
use App\Models\SuperAdminSetting;
use App\Models\User;
use App\Services\SuperAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Executive dashboard: renders, counts the workforce the stated way, shows
 * the same "cash received this month" as Finance → Overview, and hides the
 * finance figures while the Finance menu is password-locked.
 */
class ExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->actingAs($this->admin);
    }

    private function bill(Employer $er, float $amount, float $paid, ?string $billType = null): FinancialTransaction
    {
        $order = ProductionOrder::create(['employer_id' => $er->id, 'type' => 'employer', 'manual_bill_type' => $billType]);
        $group = ProductionFinancialGroup::create(['production_order_id' => $order->id, 'name' => 'General']);
        $t = FinancialTransaction::create(['production_order_id' => $order->id, 'production_financial_group_id' => $group->id,
            'type' => 'installment', 'amount' => $amount, 'paid_amount' => $paid, 'status' => $paid >= $amount ? 'paid' : ($paid > 0 ? 'partial' : 'pending')]);
        if ($paid > 0) {
            FinancialPayment::create(['financial_transaction_id' => $t->id, 'amount' => $paid, 'paid_at' => now()]);
        }
        return $t;
    }

    public function test_dashboard_numbers_match_their_menus(): void
    {
        $er = Employer::factory()->create();
        Employee::factory()->count(3)->create(['employer_id' => $er->id, 'status' => 'active']);
        Employee::factory()->create(['employer_id' => $er->id, 'status' => 'registration_cancelled']); // not workforce
        Employee::factory()->create(['employer_id' => $er->id, 'status' => 'active', 'terminated_at' => now()]); // notified out

        $this->bill($er, 1000, 1000);
        $this->bill($er, 500, 200);
        $this->bill($er, 9999, 9999, 'quotation'); // quotations never count

        $res = $this->get(route('dashboard'))->assertOk();
        $this->assertSame(3, $res->viewData('workforceCount'));

        $finance = $res->viewData('finance');
        $this->assertNotNull($finance);
        $hub = $this->get(route('finance.index', ['tab' => 'overview']))->assertOk()->viewData('stats');
        $this->assertEquals($hub['income_month'], $finance['income_month']);
        $this->assertEquals(1200.0, $finance['income_month']);
        $this->assertEquals($hub['pending_amount'], $finance['outstanding']);

        $html = $res->getContent();
        $this->assertTrue(str_contains($html, 'id="edIncome"') && str_contains($html, 'id="edPipeline"') && str_contains($html, 'kpi-detail-workforce'));
    }

    public function test_finance_figures_hidden_while_finance_menu_is_locked(): void
    {
        SuperAdminSetting::create(['key' => 'finance', 'is_visible' => true, 'access_password' => Hash::make('secret')]);
        Cache::forget(SuperAdminService::CACHE_KEY);

        $res = $this->get(route('dashboard'))->assertOk();
        $this->assertNull($res->viewData('finance'));
        $this->assertTrue($res->viewData('financeLocked'));
        $this->assertFalse(str_contains($res->getContent(), 'id="edIncome"'));

        // unlocked in this session → shown
        $res = $this->withSession(['menu_unlocked_finance' => now()->addMinutes(10)->timestamp])->get(route('dashboard'))->assertOk();
        $this->assertNotNull($res->viewData('finance'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\FinancialTransaction;
use App\Models\ProductionFinancialGroup;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Finance popup "Installments & Payments": a fully paid installment stays in
 * the list for 24 hours after it became paid (fully_paid_at), then moves
 * under the "Fully paid" tab. Display only — amounts / statuses unchanged.
 */
class FinancePaidTabTest extends TestCase
{
    use RefreshDatabase;

    private FinancialTransaction $txn;
    private ProductionOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $this->order = ProductionOrder::create(['employer_id' => Employer::factory()->create()->id, 'type' => 'employer']);
        $group = ProductionFinancialGroup::create(['production_order_id' => $this->order->id, 'name' => 'General']);
        $this->txn = FinancialTransaction::create([
            'production_order_id' => $this->order->id, 'production_financial_group_id' => $group->id,
            'type' => 'installment', 'amount' => 1000, 'paid_amount' => 0, 'status' => 'pending',
        ]);
    }

    public function test_fully_paid_at_is_stamped_when_paid_in_full_and_cleared_when_not(): void
    {
        $this->assertNull($this->txn->fully_paid_at);

        $this->postJson("/production/transactions/{$this->txn->id}/payments", ['amount' => 400, 'paid_at' => now()->toDateString(), 'notes' => 'test'])->assertOk();
        $this->assertNull($this->txn->fresh()->fully_paid_at, 'partial payment');

        $this->postJson("/production/transactions/{$this->txn->id}/payments", ['amount' => 600, 'paid_at' => now()->toDateString(), 'notes' => 'test'])->assertOk();
        $paidAt = $this->txn->fresh()->fully_paid_at;
        $this->assertSame('paid', $this->txn->fresh()->status);
        $this->assertNotNull($paidAt);

        // later edits of a paid installment don't restart the 24h countdown
        $this->travel(2)->days();
        $t = $this->txn->fresh();
        $t->update(['notes' => 'แก้หมายเหตุ']);
        $this->assertTrue($t->fresh()->fully_paid_at->equalTo($paidAt));

        // no longer fully paid → back in the current list
        $t->update(['status' => 'partial']);
        $this->assertNull($t->fresh()->fully_paid_at);
    }

    public function test_finance_tab_has_current_and_fully_paid_tabs(): void
    {
        $html = $this->get(route('production.finance_tab', $this->order->id))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'shownIncomeTransactions') && str_contains($html, 'shownAdvanceTransactions'));
        $this->assertTrue(str_contains($html, __('Fully paid')) && str_contains($html, __('Current items')));
        $this->assertTrue(str_contains($html, 'fully_paid_at'), 'transactions JSON carries fully_paid_at for the JS');
        // fully paid tab: month picker + per-month summary
        $this->assertTrue(str_contains($html, 'paidMonths') && str_contains($html, __('Month fully paid')) && str_contains($html, __('Monthly summary')));
    }
}

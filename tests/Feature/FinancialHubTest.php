<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\ProductionFinancialGroup;
use App\Models\FinancialTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\SuperAdminSetting;

class FinancialHubTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $employer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles and Permissions required
        $role = Role::create(['name' => 'super-admin']);
        $permission = Permission::create(['name' => 'manage-finance']);
        $role->givePermissionTo($permission);

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->employer = Employer::factory()->create();

        // Ensure menu is visible
        SuperAdminSetting::create(['key' => 'finance', 'is_visible' => true]);
    }

    /** @test */
    public function financial_dashboard_is_accessible()
    {
        $response = $this->actingAs($this->user)->get(route('finance.index'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.index');
        $response->assertSee('Financial Hub');
    }

    /** @test */
    public function financial_dashboard_stats_are_correct()
    {
        // Setup Transactions
        $order = ProductionOrder::create(['employer_id' => $this->employer->id, 'type' => 'employer']);
        $group = ProductionFinancialGroup::create(['production_order_id' => $order->id, 'name' => 'General']);

        // 1. Paid Today (Income Today & Month)
        FinancialTransaction::create([
            'production_order_id' => $order->id,
            'production_financial_group_id' => $group->id,
            'type' => 'installment',
            'amount' => 1000,
            'paid_amount' => 1000,
            'paid_at' => now(),
            'status' => 'paid',
        ]);

        // 2. Pending (Pending Amount)
        FinancialTransaction::create([
            'production_order_id' => $order->id,
            'production_financial_group_id' => $group->id,
            'type' => 'installment',
            'amount' => 500,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        // 3. Overdue (Overdue Amount)
        FinancialTransaction::create([
            'production_order_id' => $order->id,
            'production_financial_group_id' => $group->id,
            'type' => 'installment',
            'amount' => 200,
            'paid_amount' => 0,
            'status' => 'overdue',
        ]);

        $response = $this->actingAs($this->user)->get(route('finance.index'));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['income_today'] == 1000 &&
                   $stats['income_month'] == 1000 &&
                   $stats['pending_amount'] == 500 &&
                   $stats['overdue_amount'] == 200;
        });
    }

    /** @test */
    public function can_create_manual_bill_and_redirects()
    {
        $response = $this->actingAs($this->user)->post(route('finance.store'), [
            'employer_id' => $this->employer->id,
            'description' => 'Test Manual Bill',
            'amount' => 1500,
            'bill_date' => now()->format('Y-m-d'),
        ]);

        $newOrder = ProductionOrder::where('employer_id', $this->employer->id)->latest()->first();

        $response->assertRedirect(route('production.edit', ['production' => $newOrder->id, 'tab' => 'financial']));

        $this->assertDatabaseHas('production_orders', [
            'id' => $newOrder->id,
            'employer_id' => $this->employer->id,
            'work_type_id' => null, // Manual bill has no work type
            'project_name' => 'Test Manual Bill',
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'production_order_id' => $newOrder->id,
            'amount' => 1500,
            'status' => 'pending',
        ]);
    }
}

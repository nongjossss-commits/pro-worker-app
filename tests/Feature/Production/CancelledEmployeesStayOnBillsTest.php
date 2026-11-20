<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\FinancialTransaction;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-20 — an employee cancelled AFTER being priced / put on a bill stays
 * on that bill (amount unchanged) and the finance panel knows they are
 * cancelled: job item cancelled, whole card cancelled, or — Registration /
 * Renewal bills, whose items stay "pending" — cancelled in that menu.
 */
class CancelledEmployeesStayOnBillsTest extends TestCase
{
    use RefreshDatabase;

    private Employer $employer;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $u = User::factory()->create();
        $u->assignRole('super-admin');
        $this->actingAs($u);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);
        $this->employer = Employer::factory()->create();
    }

    /** productionItems handed to the finance panel, keyed by item id. */
    private function panelItems(string $html): array
    {
        $this->assertSame(1, preg_match('/productionItems: (\[.*?\]),\s*employees:/s', html_entity_decode($html), $m));

        return collect(json_decode($m[1], true))->keyBy('id')->all();
    }

    private function billWith(ProductionOrder $order, array $items): FinancialTransaction
    {
        $group = $order->financialGroups()->create(['name' => 'G', 'financial_data' => [
            'pricing_mode' => 'per_head',
            'pricing_tiers' => [['price' => 5000, 'count' => count($items), 'item_ids' => collect($items)->pluck('id')->all()]],
        ]]);
        $bill = FinancialTransaction::create([
            'production_order_id' => $order->id, 'production_financial_group_id' => $group->id,
            'type' => 'installment', 'amount' => 5000 * count($items), 'status' => 'pending',
        ]);
        $bill->items()->attach(collect($items)->mapWithKeys(fn ($i) => [$i->id => ['unit_price' => 5000]])->all());

        return $bill;
    }

    public function test_workflow_item_cancelled_after_billing_stays_on_the_bill_marked_cancelled(): void
    {
        $order = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => WorkType::create(['name' => 'J', 'slug' => 'j-' . uniqid()])->id, 'status' => 'active', 'type' => 'employer']);
        $a = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => Employee::factory()->create(['employer_id' => $this->employer->id])->id, 'status' => 'pending']);
        $b = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => Employee::factory()->create(['employer_id' => $this->employer->id])->id, 'status' => 'pending']);
        $bill = $this->billWith($order, [$a, $b]);

        $this->postJson(route('workflow.item.cancel', $a->id))->assertOk();

        $this->assertCount(2, $bill->fresh()->items, 'still on the bill');
        $this->assertSame(10000.0, (float) $bill->fresh()->amount);
        $items = $this->panelItems($this->get(route('production.finance_tab', $order->id))->assertOk()->getContent());
        $this->assertTrue($items[$a->id]['is_cancelled']);
        $this->assertFalse($items[$b->id]['is_cancelled']);

        // the whole card cancelled → everyone on it counts as cancelled
        $order->update(['status' => 'cancelled']);
        $items = $this->panelItems($this->get(route('production.finance_tab', $order->id))->getContent());
        $this->assertTrue($items[$b->id]['is_cancelled']);
    }

    public function test_deleted_after_billing_stays_on_the_bill_marked_deleted(): void
    {
        $order = ProductionOrder::create(['employer_id' => $this->employer->id, 'work_type_id' => WorkType::create(['name' => 'J', 'slug' => 'j-' . uniqid()])->id, 'status' => 'active', 'type' => 'employer']);
        $a = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => 'GONE ITEM'])->id, 'status' => 'pending']);
        $empB = Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => 'GONE EMPLOYEE']);
        $b = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $empB->id, 'status' => 'pending']);
        $c = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => Employee::factory()->create(['employer_id' => $this->employer->id, 'employeeNameEn' => 'STILL HERE'])->id, 'status' => 'pending']);
        $bill = $this->billWith($order, [$a, $b, $c]);

        $this->deleteJson(route('workflow.item.destroy', $a->id))->assertOk(); // job item to the trash
        $empB->delete();                                                       // employee to the trash

        $this->assertCount(3, $bill->fresh()->items, 'everyone still on the bill');
        $items = $this->panelItems($this->get(route('production.finance_tab', $order->id))->assertOk()->getContent());
        $this->assertTrue($items[$a->id]['is_deleted']);
        $this->assertSame('GONE ITEM', $items[$a->id]['name_en']);
        $this->assertTrue($items[$b->id]['is_deleted']);
        $this->assertSame('GONE EMPLOYEE', $items[$b->id]['name_en'], 'name taken from the trashed employee');
        $this->assertFalse($items[$c->id]['is_deleted']);

        // saving the bill (as the UI sends it, everyone ticked) keeps them
        $this->post("/production/transactions/{$bill->id}", ['_method' => 'PUT', 'sync_items' => 1, 'item_ids' => [$a->id, $b->id, $c->id], 'amount' => 15000], ['Accept' => 'application/json'])->assertOk();
        $this->assertCount(3, $bill->fresh()->items);

        // the employee list printed with the bill names them, marked deleted
        $html = $this->get("/production/{$order->id}/documents/invoice?transaction_ids={$bill->id}&include_employee_list=1")->assertOk()->getContent();
        $this->assertStringContainsString('GONE ITEM (ถูกลบ / Deleted)', $html);
        $this->assertStringContainsString('GONE EMPLOYEE (ถูกลบ / Deleted)', $html);
        $this->assertStringContainsString('STILL HERE', $html);
    }

    public function test_registration_employee_cancelled_after_billing_is_marked_cancelled(): void
    {
        $tab = ResolutionTab::create(['name' => 'Reg', 'type' => 'registration', 'slug' => 'reg-x', 'sort_order' => 1]);
        $emp = Employee::factory()->create(['employer_id' => $this->employer->id, 'status' => 'registration_pending', 'resolution_tab_id' => $tab->id]);
        $other = Employee::factory()->create(['employer_id' => $this->employer->id, 'status' => 'registration_pending', 'resolution_tab_id' => $tab->id]);
        $order = ProductionOrder::create(['employer_id' => $this->employer->id, 'status' => 'registration_resolution', 'resolution_tab_id' => $tab->id, 'financial_data' => []]);
        $ia = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp->id, 'status' => 'pending']);
        $ib = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $other->id, 'status' => 'pending']);
        $bill = $this->billWith($order, [$ia, $ib]);

        $emp->update(['status' => 'registration_cancelled']); // cancelled in the Registration menu

        $html = $this->get(route('production.registration.finance.tab', ['resolutionTab' => $tab->id, 'employer' => $this->employer->id]))->assertOk()->getContent();
        $items = $this->panelItems($html);
        $this->assertTrue($items[$ia->id]['is_cancelled']);
        $this->assertFalse($items[$ib->id]['is_cancelled']);
        $this->assertCount(2, $bill->fresh()->items);
    }
}

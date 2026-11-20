<?php

namespace Tests\Feature;

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
 * 2026-11-21 — an employer card whose people were all deleted (employee in the
 * trash, job items left behind) showed "9 TOTAL" and sat among the active
 * cards, but opened empty. It now counts only people the card can list, so it
 * is greyed out and goes to the bottom like any empty card — Pre-Production
 * and Workflow. A Workflow draft person (no employee record yet) still counts.
 */
class EmptyEmployerCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $u = User::factory()->create();
        $u->assignRole('super-admin');
        $this->actingAs($u);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);
    }

    /** [order with only deleted employees (employer A001), order with a live one (A002)] */
    private function cards(WorkType $wt, string $status): array
    {
        $gone = Employer::factory()->create(['employerId' => 'A001']);
        $live = Employer::factory()->create(['employerId' => 'A002']);
        $empty = ProductionOrder::create(['employer_id' => $gone->id, 'work_type_id' => $wt->id, 'status' => $status, 'type' => 'employer']);
        $full = ProductionOrder::create(['employer_id' => $live->id, 'work_type_id' => $wt->id, 'status' => $status, 'type' => 'employer']);
        foreach (range(1, 3) as $i) {
            $e = Employee::factory()->create(['employer_id' => $gone->id]);
            ProductionItem::create(['production_order_id' => $empty->id, 'employee_id' => $e->id, 'status' => 'pending']);
            $e->delete(); // deleted from the Employees menu — the job item stays
        }
        ProductionItem::create(['production_order_id' => $full->id, 'employee_id' => Employee::factory()->create(['employer_id' => $live->id])->id, 'status' => 'pending']);

        return [$empty, $full];
    }

    public function test_pre_production_card_with_only_deleted_employees_is_grey_and_last(): void
    {
        $wt = WorkType::create(['name' => 'แจ้งเข้า', 'slug' => 'pp-' . uniqid()]);
        [$empty, $full] = $this->cards($wt, 'pre_production');

        $res = $this->get(route('production.index', ['tab' => $wt->slug]))->assertOk();
        $orders = collect($res->viewData('orders')->items());
        $this->assertSame([$full->id, $empty->id], $orders->pluck('id')->all(), 'the empty card goes to the bottom');
        $this->assertSame(0, $orders->firstWhere('id', $empty->id)->active_items_count);
        $this->assertSame(1, $res->viewData('stats')['total_employees'], 'deleted people are not counted');

        $html = $res->getContent();
        $this->assertMatchesRegularExpression('/production-order-card position-relative grayscale-mode"[\s\S]*?heading-' . $empty->id . '/', $html);

        $stats = $this->postJson(route('production.stats.batch'), ['order_ids' => [$empty->id, $full->id]])->assertOk()->json();
        $this->assertSame(0, $stats[$empty->id]['activeCount']);
        $this->assertSame(1, $stats[$full->id]['activeCount']);
    }

    public function test_workflow_counts_drafts_but_not_deleted_employees(): void
    {
        $wt = WorkType::create(['name' => 'งาน', 'slug' => 'wf-' . uniqid()]);
        [$empty, $full] = $this->cards($wt, 'active');

        $orders = collect($this->get(route('workflow.index', ['tab' => $wt->slug]))->assertOk()->viewData('orders')->items());
        $this->assertSame([$full->id, $empty->id], $orders->pluck('id')->all());
        $this->assertSame(0, $orders->firstWhere('id', $empty->id)->active_items_count);

        // a draft person (no employee record yet) is a real entry on the card
        ProductionItem::create(['production_order_id' => $empty->id, 'employee_id' => null, 'status' => 'pending', 'new_employee_data' => ['passport' => 'DRAFT1']]);
        $orders = collect($this->get(route('workflow.index', ['tab' => $wt->slug]))->viewData('orders')->items());
        $this->assertSame(1, $orders->firstWhere('id', $empty->id)->active_items_count);
    }
}

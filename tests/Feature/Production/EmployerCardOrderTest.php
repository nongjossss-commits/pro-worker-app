<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-19: employer cards in Pre-Production / Workflow / Registration /
 * Renewal Resolution keep a FIXED order — by employer code (รหัสนายจ้าง)
 * A→Z — instead of jumping to the top whenever an employee is edited.
 * Cards with no active employees still go last.
 */
class EmployerCardOrderTest extends TestCase
{
    use RefreshDatabase;

    /** @var Employer[] code => employer */
    private array $emp = [];

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);

        // created in a different order than their codes
        foreach (['EMP-00300', 'EMP-00100', 'EMP-00200'] as $code) {
            $this->emp[$code] = Employer::factory()->create(['employerId' => $code]);
        }
    }

    /** ids in the order their markers appear in the page */
    private function orderOf(string $html, array $markers): array
    {
        $pos = [];
        foreach ($markers as $key => $marker) {
            $p = strpos($html, $marker);
            $this->assertNotFalse($p, "missing $marker");
            $pos[$key] = $p;
        }
        asort($pos);

        return array_keys($pos);
    }

    private function workflowCards(string $status, string $routeName): void
    {
        $type = WorkType::create(['name' => 'แท็บ', 'slug' => 'ord-' . $status, 'order' => 1]);
        $orders = [];
        foreach ($this->emp as $code => $employer) {
            $order = ProductionOrder::create(['employer_id' => $employer->id, 'work_type_id' => $type->id, 'type' => 'employer', 'status' => $status]);
            $item = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => Employee::factory()->create(['employer_id' => $employer->id])->id, 'status' => 'pending']);
            $orders[$code] = [$order, $item];
        }
        $markers = array_map(fn ($o) => 'id="heading-' . $o[0]->id . '"', $orders);

        $html = $this->get(route($routeName, ['tab' => $type->slug]))->assertOk()->getContent();
        $this->assertSame(['EMP-00100', 'EMP-00200', 'EMP-00300'], $this->orderOf($html, $markers));

        // editing an employee of the LAST card does not move it up
        $this->travel(5)->minutes();
        $orders['EMP-00300'][1]->employee->update(['employeeNameEn' => 'EDITED']);
        $orders['EMP-00300'][1]->touch();
        $html = $this->get(route($routeName, ['tab' => $type->slug]))->getContent();
        $this->assertSame(['EMP-00100', 'EMP-00200', 'EMP-00300'], $this->orderOf($html, $markers));

        // a card with no active employees goes last; back in place once active again
        $orders['EMP-00100'][1]->update(['status' => 'completed']);
        $html = $this->get(route($routeName, ['tab' => $type->slug]))->getContent();
        $this->assertSame(['EMP-00200', 'EMP-00300', 'EMP-00100'], $this->orderOf($html, $markers));
        $orders['EMP-00100'][1]->update(['status' => 'pending']);
        $html = $this->get(route($routeName, ['tab' => $type->slug]))->getContent();
        $this->assertSame(['EMP-00100', 'EMP-00200', 'EMP-00300'], $this->orderOf($html, $markers));
    }

    public function test_workflow_cards_keep_employer_code_order(): void
    {
        $this->workflowCards('active', 'workflow.index');
    }

    public function test_pre_production_cards_keep_employer_code_order(): void
    {
        $this->workflowCards('pre_production', 'production.index');
    }

    private function resolutionCards(string $type): void
    {
        $tab = ResolutionTab::create(['name' => 'มติ', 'type' => $type, 'slug' => 'ord-' . $type, 'sort_order' => 1]);
        $people = [];
        foreach ($this->emp as $code => $employer) {
            $people[$code] = Employee::factory()->create(['employer_id' => $employer->id, 'resolution_tab_id' => $tab->id, 'status' => $type . '_pending']);
        }
        $markers = array_map(fn ($e) => 'id="employer-card-' . $e->id . '"', $this->emp);
        $url = route("production.{$type}.operations", $tab);

        $this->assertSame(['EMP-00100', 'EMP-00200', 'EMP-00300'], $this->orderOf($this->get($url)->assertOk()->getContent(), $markers));

        // editing the last employer's employee does not move its card
        $this->travel(5)->minutes();
        $people['EMP-00300']->update(['employeeNameEn' => 'EDITED']);
        $this->assertSame(['EMP-00100', 'EMP-00200', 'EMP-00300'], $this->orderOf($this->get($url)->getContent(), $markers));

        // no active employee left → last
        $people['EMP-00100']->update(['status' => $type . '_cancelled']);
        $this->assertSame(['EMP-00200', 'EMP-00300', 'EMP-00100'], $this->orderOf($this->get($url)->getContent(), $markers));
    }

    public function test_registration_cards_keep_employer_code_order(): void
    {
        $this->resolutionCards('registration');
    }

    public function test_renewal_cards_keep_employer_code_order(): void
    {
        $this->resolutionCards('renewal');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeRenewalLink;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Operator button (bottom-right of employee / job cards): one shared picker
 * (partials/_operator_picker) for Registration, Renewal, Pre-Production and
 * Workflow — a user or a typed outside name.
 */
class OperatorAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        return $user;
    }

    public function test_dual_listed_renewal_card_keeps_its_status_after_choosing_an_operator(): void
    {
        $admin = $this->admin();
        $tab = ResolutionTab::create(['name' => 'มติต่ออายุใหม่', 'type' => 'renewal', 'slug' => 'renewal-new-' . uniqid()]);
        // A Registration employee shown in this Renewal tab through a link:
        // its own status says "registration_completed", the link says pending.
        $employee = Employee::factory()->create(['status' => 'registration_completed']);
        EmployeeRenewalLink::create(['employee_id' => $employee->id, 'resolution_tab_id' => $tab->id, 'status' => 'renewal_pending']);

        $res = $this->actingAs($admin)->postJson(
            route('production.renewal.toggle_operator', ['resolutionTab' => $tab->id, 'employee' => $employee->id]),
            ['operator_id' => null, 'custom_operator_name' => 'คุณสมชาย (ตัวแทน)']
        )->assertOk()->json();

        $this->assertTrue($res['success']);
        $this->assertSame('คุณสมชาย (ตัวแทน)', $res['operator']['custom']);
        $this->assertStringContainsString('data-status="renewal_pending"', $res['html']);
        $this->assertStringNotContainsString('data-status="registration_completed"', $res['html']);
        $this->assertStringContainsString('คุณสมชาย (ตัวแทน) (ภายนอก)', $res['html']);
        $this->assertSame('คุณสมชาย (ตัวแทน)', $employee->fresh()->custom_operator_name);
    }

    public function test_workflow_job_accepts_a_typed_operator_name_and_can_clear_it(): void
    {
        $admin = $this->admin();
        $order = ProductionOrder::create(['employer_id' => Employer::factory()->create()->id, 'status' => 'active', 'created_by' => $admin->id]);
        $item = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => Employee::factory()->create()->id, 'status' => 'pending']);
        $url = route('workflow.item.toggle_operator', $item->id);

        $res = $this->actingAs($admin)->postJson($url, ['operator_id' => null, 'custom_operator_name' => 'ช่างภายนอก'])->assertOk()->json();
        $this->assertSame('ช่างภายนอก', $res['operator']['custom']);
        $this->assertNull($item->fresh()->operator_id);
        $this->assertSame('ช่างภายนอก', $item->fresh()->custom_operator_name);

        $res = $this->actingAs($admin)->postJson($url, ['operator_id' => $admin->id, 'custom_operator_name' => null])->assertOk()->json();
        $this->assertSame($admin->id, $res['operator']['id']);
        $this->assertTrue($res['operator']['is_me']);
        $this->assertNull($item->fresh()->custom_operator_name);

        $this->actingAs($admin)->postJson($url, ['operator_id' => '', 'custom_operator_name' => null])->assertOk();
        $this->assertNull($item->fresh()->operator_id);
        $this->assertNull($item->fresh()->custom_operator_name);
    }

    public function test_workflow_card_shows_a_typed_operator(): void
    {
        $admin = $this->admin();
        $order = ProductionOrder::create(['employer_id' => Employer::factory()->create()->id, 'status' => 'active', 'created_by' => $admin->id]);
        $item = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => Employee::factory()->create()->id, 'status' => 'pending']);
        $item->forceFill(['custom_operator_name' => 'ช่างภายนอก'])->save();

        $html = $this->actingAs($admin)->getJson(route('workflow.item.card', $item->id))->assertOk()->json('html');

        $this->assertStringContainsString('ช่างภายนอก (ภายนอก)', $html);
        // The typed name is passed to the picker (JS-escaped) so it opens pre-filled.
        $this->assertStringContainsString("toggleOperator({$item->id}, this, '', '" . trim(json_encode('ช่างภายนอก'), '"') . "')", $html);
    }

    public function test_pre_production_card_saves_the_operator_on_its_job(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $order = ProductionOrder::create(['employer_id' => Employer::factory()->create()->id, 'status' => 'active', 'created_by' => $admin->id]);
        $employee = Employee::factory()->create();
        $item = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $employee->id, 'status' => 'pending']);
        $item->forceFill(['custom_operator_name' => 'ผู้ยื่นงาน'])->save();
        $employee->production_item = $item;

        $html = view('production.registration._employee_card', ['employee' => $employee, 'steps' => collect(), 'currentTab' => null])->render();

        $this->assertStringContainsString(route('workflow.item.toggle_operator', $item->id), $html);
        $this->assertStringContainsString('ผู้ยื่นงาน (ภายนอก)', $html);
    }
}

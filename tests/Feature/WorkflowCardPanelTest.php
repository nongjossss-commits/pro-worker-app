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
 * Workflow item card: appointment, outsource login, RA / request / reference
 * numbers and the note sit together in one info panel (.wf-panel) — every
 * field and its edit / copy control is still there.
 */
class WorkflowCardPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_has_the_info_panel_with_every_field(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $er = Employer::factory()->create();
        $wt = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'is_system' => true, 'order' => 1]);
        $order = ProductionOrder::create(['employer_id' => $er->id, 'work_type_id' => $wt->id, 'status' => 'active']);
        $emp = Employee::factory()->create(['employer_id' => $er->id, 'email' => 'ra-login@example.test', 'outsource_code' => 'OUT-77', 'name_list_number' => 'RA-555']);
        $item = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp->id, 'status' => 'pending',
            'request_number' => 'REQ-123', 'remarks' => 'REMARK-TEST']);

        $html = $this->getJson(route('workflow.item.card', $item->id))->assertOk()->json('html');

        $this->assertTrue(str_contains($html, 'class="wf-panel"'));
        foreach (['wf-appt', 'wf-cred', 'wf-more', 'wf-remark', 'wf-fields'] as $part) {
            $this->assertTrue(str_contains($html, $part), $part);
        }
        // values and edit controls are all still there
        foreach (['ra-login@example.test', 'OUT-77', 'RA-555', 'REQ-123', 'REMARK-TEST'] as $value) {
            $this->assertTrue(str_contains($html, $value), $value);
        }
        foreach (['Edit Credentials', 'Edit Fields', 'แก้ไขหมายเหตุ', 'appointment-complete'] as $control) {
            $this->assertTrue(str_contains($html, $control), $control);
        }
        $this->assertTrue(str_contains($html, __('Outsource login')));
    }
}

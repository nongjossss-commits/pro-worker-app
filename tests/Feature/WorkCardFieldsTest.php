<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\User;
use App\Models\WorkType;
use App\Services\EmployeeCardFieldsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The details Super Admin picks for the Employees menu cards (Employee Card
 * Details, up to 6) show the same way on the work cards — Workflow /
 * Pre-Production / Registration / Renewal resolution — each with a copy
 * button carrying the value.
 */
class WorkCardFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
        EmployeeCardFieldsService::save(['employeePassport', 'visaExpiryDate', 'bank_account_number']);
    }

    private function assertFields(string $html): void
    {
        $this->assertTrue(str_contains($html, 'work-card-fields'));
        $this->assertTrue(str_contains($html, 'data-copy="PP998877"'), 'passport with copy button');
        $this->assertTrue(str_contains($html, 'data-copy="15/03/2027"'), 'visa expiry formatted d/m/Y');
        $this->assertTrue(str_contains($html, __('Bank Account Number')));
        $this->assertFalse(str_contains($html, 'data-copy="-"'), 'empty values get no copy button');
    }

    public function test_registration_and_workflow_cards_show_the_super_admin_fields_with_copy_buttons(): void
    {
        $er = Employer::factory()->create();
        $tab = ResolutionTab::create(['name' => 'มติทดสอบ', 'type' => 'registration', 'slug' => 'reg-' . uniqid()]);
        $emp = Employee::factory()->create(['employer_id' => $er->id, 'resolution_tab_id' => $tab->id, 'status' => 'registration_pending',
            'employeePassport' => 'PP998877', 'visaExpiryDate' => '2027-03-15', 'bank_account_number' => null]);

        $this->assertFields($this->get(route('production.registration.employer.employees', [$tab->id, $er->id]))->assertOk()->getContent());

        $wt = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'is_system' => true, 'order' => 1]);
        $order = ProductionOrder::create(['employer_id' => $er->id, 'work_type_id' => $wt->id, 'status' => 'active']);
        $item = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp->id, 'status' => 'pending']);
        $this->assertFields($this->getJson(route('workflow.item.card', $item->id))->assertOk()->json('html'));
    }

    public function test_choosing_no_fields_hides_the_block(): void
    {
        EmployeeCardFieldsService::save([]);
        $emp = Employee::factory()->create(['employeePassport' => 'PP998877']);
        $html = view('partials._work_card_fields', ['employee' => $emp])->render();
        $this->assertSame('', trim($html));
    }
}

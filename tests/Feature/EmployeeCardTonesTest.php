<?php

namespace Tests\Feature;

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
 * Employee tiles take their colour from the work the employee is in; an
 * employee in two kinds of work at once (e.g. changing employer in Workflow
 * AND in Renewal resolution) gets both colours, split (hard stops), in a
 * fixed order: registration, renewal, workflow, pre-production.
 */
class EmployeeCardTonesTest extends TestCase
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

    private function cardHtml(string $html, Employee $e): string
    {
        $start = strpos($html, 'id="employee-card-' . $e->id . '"');
        $this->assertNotFalse($start, 'card rendered');
        return substr($html, max(0, $start - 300), 900);
    }

    public function test_single_and_split_colours(): void
    {
        $er = Employer::factory()->create();
        $renTab = ResolutionTab::create(['name' => 'มติต่ออายุ', 'type' => 'renewal', 'slug' => 'ren-' . uniqid(), 'badge_enabled' => true]);
        $wt = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'is_system' => true, 'order' => 1]);
        $order = ProductionOrder::create(['employer_id' => $er->id, 'work_type_id' => $wt->id, 'status' => 'active']);

        $wfOnly = Employee::factory()->create(['employer_id' => $er->id, 'status' => 'active']);
        ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $wfOnly->id, 'status' => 'pending']);

        $both = Employee::factory()->create(['employer_id' => $er->id]);
        $both->forceFill(['status' => 'renewal_pending', 'resolution_tab_id' => $renTab->id])->saveQuietly();
        ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $both->id, 'status' => 'pending']);

        $none = Employee::factory()->create(['employer_id' => $er->id, 'status' => 'active']);

        $html = $this->get(route('employees.index'))->assertOk()->getContent();

        $one = $this->cardHtml($html, $wfOnly);
        $this->assertTrue(str_contains($one, '--emp-tone: #ffc107;'));
        $this->assertFalse(str_contains($one, 'emp-multi-tone'));

        $two = $this->cardHtml($html, $both);
        $this->assertTrue(str_contains($two, 'emp-multi-tone') && str_contains($two, 'data-tone-count="2"'));
        // renewal (pink) first, then workflow (yellow), split half and half
        $this->assertTrue(str_contains($two, '--emp-tone-bar: linear-gradient(90deg, #EC4899 0% 50%, #ffc107 50% 100%);'));
        $this->assertTrue(str_contains($two, '--emp-tone: #EC4899; --emp-tone-2: #ffc107;'));

        $plain = $this->cardHtml($html, $none);
        $this->assertFalse(str_contains($plain, '--emp-tone'));
    }
}

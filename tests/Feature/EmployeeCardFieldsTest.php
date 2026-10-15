<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\SuperAdminSetting;
use App\Models\User;
use App\Services\EmployeeCardFieldsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Super Admin → Settings → "Employee Card Details" picks which employee
 * details the employee tiles show (EmployeeCardFieldsService); list pages
 * with tiles show a fixed 100 per page.
 */
class EmployeeCardFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        Role::findOrCreate('super-admin');
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); }
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        EmployeeCardFieldsService::forgetCache();

        return $user;
    }

    public function test_cards_show_the_default_details_until_a_choice_is_saved(): void
    {
        $this->actingAs($this->superAdmin());
        $employer = Employer::factory()->create();
        Employee::factory()->create(['employer_id' => $employer->id, 'employeePassport' => 'PX123456', 'employeePhone' => '0899999999']);

        $html = $this->get(route('employees.index'))->assertOk()->getContent();
        $this->assertStringContainsString('emp-card-field', $html);
        $this->assertStringContainsString('PX123456', $html);
        $this->assertStringNotContainsString('0899999999', $html);
        // 25 / 50 / 100 buttons are back (the pick is remembered — see EmployeePerPageTest).
        $this->assertTrue(str_contains($html, 'per_page=25'), 'per-page buttons missing');
        $this->assertTrue(str_contains($html, 'per_page=50'), 'per-page buttons missing');
    }

    public function test_saved_choice_changes_the_cards_in_order(): void
    {
        $this->actingAs($this->superAdmin());
        $employer = Employer::factory()->create();
        Employee::factory()->create(['employer_id' => $employer->id, 'employeePassport' => 'PX123456', 'employeePhone' => '0899999999']);

        $this->post(route('super-admin.settings.card-fields'), ['fields' => ['employeePhone', 'employeeDob']])
            ->assertRedirect(route('super-admin.settings.index', ['tab' => 'card-fields']));
        $this->assertSame(['employeePhone', 'employeeDob'], json_decode(SuperAdminSetting::where('key', EmployeeCardFieldsService::SETTING_KEY)->value('value'), true));

        EmployeeCardFieldsService::forgetCache();
        $html = $this->get(route('employees.index'))->assertOk()->getContent();
        $this->assertStringContainsString('0899999999', $html);
        $this->assertStringNotContainsString('PX123456', $html);

        // Employer page's Employees tab and the settings tab render too.
        $this->get(route('employers.edit', $employer))->assertOk()->assertSee('0899999999');
        $this->get(route('super-admin.settings.index', ['tab' => 'card-fields']))->assertOk()->assertSee('card-field-employeePhone', false);
    }

    public function test_more_than_six_or_unknown_fields_are_rejected(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('super-admin.settings.card-fields'), ['fields' => ['employeePassport', 'employeePhone', 'employeeDob', 'age', 'email', 'height', 'weight']])
            ->assertSessionHasErrors('fields');
        $this->post(route('super-admin.settings.card-fields'), ['fields' => ['password']])
            ->assertSessionHasErrors('fields.0');
        $this->assertNull(SuperAdminSetting::where('key', EmployeeCardFieldsService::SETTING_KEY)->first());
    }

    public function test_nationality_and_search_placeholders_follow_the_language(): void
    {
        $this->actingAs($this->superAdmin());
        $employer = Employer::factory()->create();
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNationality' => 'เวียดนาม']);

        app()->setLocale('en');
        session(['locale' => 'en']);
        $html = $this->get(route('employees.index'))->assertOk()->getContent();
        $this->assertStringContainsString('<span>Vietnamese</span>', $html);
        $this->assertTrue(str_contains($html, 'Search employee, employer or job owner (name, ID, passport...)'), 'box 1 placeholder (en) missing');

        session(['locale' => 'th']);
        $history = $this->get(route('employees.history'))->assertOk()->getContent();
        $this->assertTrue(str_contains($history, 'ค้นหาลูกจ้าง นายจ้าง หรือเจ้าของงาน (ชื่อ/เลข/พาสปอร์ต)...'), 'box 1 placeholder (th) missing');
    }

    public function test_only_super_admin_can_save(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('super-admin.settings.card-fields'), ['fields' => ['employeePhone']])->assertForbidden();
    }
}

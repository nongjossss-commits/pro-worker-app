<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Employee edit / create / preview are split into tabs (partials/_record_tabs)
 * under a profile header with colour-coded expiry badges. Every field must
 * still be in the form — the tabs only hide panes, they don't drop inputs.
 */
class EmployeeRecordTabsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        return $user;
    }

    private function employee(): Employee
    {
        return Employee::factory()->create([
            'employer_id' => Employer::factory()->create()->id,
            'passportExpiryDate' => now()->subDay(),          // expired → red
            'visaExpiryDate' => now()->addDays(10),           // within 30 days → yellow
            'workPermitExpiryDate' => now()->addYear(),       // valid → green
            'ninetyDayReportDate' => null,                    // none → grey
            'employee_doc_1' => 'employees/docs/a.pdf',
            'employee_doc_9' => 'employees/docs/b.pdf',
        ]);
    }

    public function test_edit_page_is_tabbed_and_keeps_every_field(): void
    {
        $employee = $this->employee();
        $html = $this->actingAs($this->admin())->get(route('employees.edit', $employee))->assertOk()->getContent();

        foreach (['personal', 'passport', 'employment', 'insurance', 'login', 'files'] as $tab) {
            $this->assertStringContainsString('data-rec-tab="' . $tab . '"', $html);
            $this->assertStringContainsString('data-rec-pane="' . $tab . '"', $html);
        }
        // Fields from every section are still inside the one form.
        foreach (['employeeNameEn', 'employeePassport', 'visaExpiryDate', 'employeeWorkPermit', 'insurance_type', 'employeeEmail', 'employee_doc_18'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html);
        }
        $this->assertStringContainsString('rec-savebar', $html);
        $this->assertStringContainsString('<span class="rec-tab-count">2</span>', $html);

        // Header badges: expired / soon / ok / none.
        $this->assertStringContainsString('rec-expiry is-expired', $html);
        $this->assertStringContainsString('rec-expiry is-soon', $html);
        $this->assertStringContainsString('rec-expiry is-ok', $html);
        $this->assertStringContainsString('rec-expiry is-none', $html);
    }

    public function test_validation_errors_are_handed_to_the_tabs(): void
    {
        $employee = $this->employee();
        $html = $this->actingAs($this->admin())
            ->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag(['employeePassport' => ['ซ้ำ']]))])
            ->get(route('employees.edit', $employee))->assertOk()->getContent();

        $this->assertStringContainsString('data-error-fields="[&quot;employeePassport&quot;]"', $html);
    }

    public function test_create_page_and_preview_are_tabbed(): void
    {
        $admin = $this->admin();
        $html = $this->actingAs($admin)->get(route('employees.create'))->assertOk()->getContent();
        $this->assertStringContainsString('data-rec-pane="files"', $html);
        $this->assertStringContainsString('name="employee_doc_18"', $html);
        $this->assertStringContainsString('rec-savebar', $html);

        $employee = $this->employee();
        $preview = $this->actingAs($admin)->get(route('global.preview', ['type' => 'employee', 'id' => $employee->id]))->assertOk()->getContent();
        foreach (['personal', 'permits', 'employment', 'insurance', 'files'] as $tab) {
            $this->assertStringContainsString('data-rec-pane="' . $tab . '"', $preview);
        }
        $this->assertStringContainsString('rec-profile', $preview);
        // Fixed frame: header + tab bar stay put, only the tab content scrolls.
        $this->assertStringContainsString('emp-preview content-section rec-fixed', $preview);
    }

    public function test_employer_preview_is_tabbed_in_a_fixed_frame(): void
    {
        $employer = Employer::factory()->create([
            'employer_doc_company' => 'employers/docs/cert.pdf',
            'employer_doc_company_expiry' => now()->addDays(5),
        ]);
        Employee::factory()->count(2)->create(['employer_id' => $employer->id]);

        $html = $this->actingAs($this->admin())
            ->get(route('global.preview', ['type' => 'employer', 'id' => $employer->id]))->assertOk()->getContent();

        $this->assertStringContainsString('empr-preview rec-fixed', $html);
        foreach (['info', 'contact', 'signers', 'docs', 'addresses', 'employees'] as $tab) {
            $this->assertStringContainsString('data-rec-pane="' . $tab . '"', $html);
        }
        $this->assertStringContainsString('<span class="rec-tab-count">1</span>', $html);
        $this->assertStringContainsString('rec-expiry is-soon', $html); // certificate expires in 5 days
    }
}

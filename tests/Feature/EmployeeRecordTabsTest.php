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

        foreach (['personal', 'passport', 'employment', 'files'] as $tab) {
            $this->assertStringContainsString('data-rec-tab="' . $tab . '"', $html);
            $this->assertStringContainsString('data-rec-pane="' . $tab . '"', $html);
        }
        // Fields from every section are still inside the one form.
        foreach (['employeeNameEn', 'employeePassport', 'visaExpiryDate', 'employeeWorkPermit', 'insurance_type', 'employeeEmail', 'employee_doc_18'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html);
        }
        $this->assertStringContainsString('rec-savebar', $html);
        $this->assertStringContainsString('<span class="rec-tab-count">2</span>', $html);

        // Attachment cards: 18 slots, same input ids/names, 2 marked as attached, per-group counters.
        // 18 attachment slots + 2 insurance slots (medical certificate, insurance document).
        $this->assertSame(20, substr_count($html, 'data-att-card>'));
        $this->assertSame(2, substr_count($html, 'att-card has-file'));
        $this->assertStringContainsString('id="edit_employee_doc_18"', $html);
        $this->assertStringContainsString('name="other_doc_10_desc"', $html);
        $this->assertStringContainsString(__('Attached :done/:total', ['done' => 1, 'total' => 8]), $html);
        $this->assertStringContainsString(__('Attached :done/:total', ['done' => 1, 'total' => 10]), $html);

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
        foreach (['personal', 'passport', 'employment', 'files'] as $tab) {
            $this->assertStringContainsString('data-rec-pane="' . $tab . '"', $preview);
        }
        $this->assertStringContainsString('rec-profile', $preview);
        // Fixed frame: header + tab bar stay put, only the tab content scrolls.
        $this->assertStringContainsString('emp-preview content-section rec-fixed', $preview);
        // Compact layout: fields flow in a grid, attachments are chips (one per slot).
        $this->assertStringContainsString('class="pv-grid"', $preview);
        $this->assertStringNotContainsString('col-md-3 preview-field', $preview);
        $this->assertSame(18, substr_count($preview, 'class="pv-file '));
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

    public function test_employer_edit_page_is_tabbed(): void
    {
        $admin = $this->admin();
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); }
        $employer = Employer::factory()->create();

        $html = $this->actingAs($admin)->get(route('employers.edit', $employer))->assertOk()->getContent();
        foreach (['info', 'access', 'signers', 'docs', 'addresses', 'employees', 'history'] as $tab) {
            $this->assertStringContainsString('data-rec-pane="' . $tab . '"', $html);
        }
        $this->assertStringContainsString('data-rec-initial="info"', $html);
        // Save bar belongs to the form tabs only.
        $this->assertStringContainsString('rec-savebar" data-rec-only="info access signers docs"', $html);
        // Fields still in the one form.
        foreach (['employerNameTh', 'outsource_password', 'signerNameTh', 'regCapital', 'minimum_wage', 'employer_doc_other_3'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html);
        }

        // Employee-list filters / pagination reload onto the employees tab.
        $this->actingAs($admin)->get(route('employers.edit', [$employer, 'search' => 'x']))
            ->assertSee('data-rec-initial="employees"', false);

        // Cancel only goes back to this site's URLs.
        $this->actingAs($admin)->get(route('employers.edit', [$employer, 'return_url' => 'javascript:alert(1)']))
            ->assertDontSee('href="javascript:alert(1)"', false);
        $this->actingAs($admin)->get(route('employers.edit', [$employer, 'return_url' => url('/employees?page=2')]))
            ->assertSee('href="' . e(url('/employees?page=2')) . '"', false);
    }

    public function test_previews_offer_an_edit_button_to_editors_only(): void
    {
        $employee = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('global.preview', ['type' => 'employee', 'id' => $employee->id]))
            ->assertSee('data-preview-edit data-type="employee" data-id="' . $employee->id . '"', false)
            ->assertSee(route('employees.edit', $employee->id), false);
        $this->actingAs($admin)->get(route('global.preview', ['type' => 'employer', 'id' => $employee->employer_id]))
            ->assertSee('data-preview-edit data-type="employer" data-id="' . $employee->employer_id . '"', false);

        // Someone who may view but not edit gets no button.
        $viewer = User::factory()->create();
        $this->assertFalse($viewer->can('edit-employees'));
        $view = view('employees.partials._profile_header', ['employee' => $employee, 'editUrl' => $viewer->can('edit-employees') ? route('employees.edit', $employee->id) : null])->render();
        $this->assertStringNotContainsString('data-preview-edit', $view);
    }

    public function test_employer_edit_and_create_use_attachment_cards(): void
    {
        $admin = $this->admin();
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); } // the employer edit page lists these accounts
        $employer = Employer::factory()->create(['employer_doc_company' => 'employers/docs/cert.pdf']);

        $edit = $this->actingAs($admin)->get(route('employers.edit', $employer))->assertOk()->getContent();
        $this->assertSame(6, substr_count($edit, 'data-att-card>'));
        $this->assertSame(1, substr_count($edit, 'att-card has-file'));
        $this->assertStringContainsString('name="employer_doc_company_expiry"', $edit);
        $this->assertStringContainsString('name="employer_doc_other_3_desc"', $edit);

        $create = $this->actingAs($admin)->get(route('employers.create'))->assertOk()->getContent();
        $this->assertSame(6, substr_count($create, 'data-att-card>'));
        $this->assertStringContainsString('name="employer_doc_company_expiry"', $create);
        // This page never restricted file types; the card keeps that.
        $this->assertStringNotContainsString('id="employer_doc_company"
           name="employer_doc_company"
           accept=', $create);
    }
}

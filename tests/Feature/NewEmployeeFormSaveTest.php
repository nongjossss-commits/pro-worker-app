<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-03 bug report: adding employees one after another from Workflow
 * → "Add Employee" → New saved the previous employee's dates into the next
 * one (hidden date fields survived form.reset() — fixed in
 * public/js/date-form-sync.js, checked in a browser). Also found while
 * checking: fields shown on that form that were never saved from Workflow,
 * and the social-security "hospital right" box that was lost on every new
 * employee (Employees menu too).
 */
class NewEmployeeFormSaveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->actingAs($this->admin);
    }

    public function test_both_layouts_load_the_date_sync_script(): void
    {
        $this->assertFileExists(public_path('js/date-form-sync.js'));
        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('js/date-form-sync.js', $html);
        $this->assertStringContainsString('js/date-form-sync.js', file_get_contents(resource_path('views/labor/layout.blade.php')));

        $js = file_get_contents(public_path('js/date-form-sync.js'));
        $this->assertStringContainsString("addEventListener('reset'", $js);
        $this->assertStringContainsString("addEventListener('formdata'", $js);
    }

    public function test_edit_popups_show_only_the_last_opened_employee(): void
    {
        foreach (['views/components/edit-employee-modal.blade.php', 'views/production/registration/partials/edit_modal_script.blade.php'] as $view) {
            $blade = file_get_contents(resource_path($view));
            $this->assertStringContainsString('__editEmployeeRequestNo', $blade, $view);
            $this->assertStringContainsString('if (!isCurrent()) return;', $blade, $view);
        }
    }

    public function test_workflow_new_employee_saves_every_field_on_the_form(): void
    {
        $employer = Employer::factory()->create();
        $tab = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'is_system' => true, 'order' => 1]);

        $this->postJson(route('workflow.store'), [
            'work_type_id' => $tab->id,
            'employer_id' => $employer->id,
            'employeeNameEn' => 'NEW ONE',
            'employeePassport' => 'PX1234567',
            'passportExpiryDate' => '2031-01-02',
            'name_suffix' => 'JR',
            'height' => '170',
            'weight' => '60',
            'department' => 'Packing',
            'passport_issue_place' => 'YANGON',
            'visaType' => 'NON L-A',
            'visa_issue_place' => 'Chonburi',
            'visaEndorsementDate' => '2026-05-01',
            'visaEndorsementNo' => 'VE1',
            'workPermitIssueDate' => '2026-05-02',
            'insurance_type' => 'ประกันสังคม',
            'social_security_number' => '1234',
            'insurance_detail_social' => 'รพ.ชลบุรี',
            'sso_issue_date' => '2026-06-01',
            'sso_expiry_date' => '2027-06-01',
        ])->assertOk();

        $e = Employee::where('employeeNameEn', 'NEW ONE')->firstOrFail();
        $this->assertSame('JR', $e->name_suffix);
        $this->assertSame('170', (string) $e->height);
        $this->assertSame('60', (string) $e->weight);
        $this->assertSame('Packing', $e->department);
        $this->assertSame('YANGON', $e->passport_issue_place);
        $this->assertSame('NON L-A', $e->visaType);
        $this->assertSame('Chonburi', $e->visa_issue_place);
        $this->assertSame('2026-05-01', substr((string) $e->visaEndorsementDate, 0, 10));
        $this->assertSame('VE1', $e->visaEndorsementNo);
        $this->assertSame('2026-05-02', substr((string) $e->workPermitIssueDate, 0, 10));
        $this->assertSame('รพ.ชลบุรี', $e->insurance_detail);
        $this->assertSame('2026-06-01', substr((string) $e->sso_issue_date, 0, 10));
        $this->assertSame('2031-01-02', substr((string) $e->passportExpiryDate, 0, 10));
        $this->assertSame(1, ProductionOrder::where('work_type_id', $tab->id)->count());

        // the social-security dates only belong to that insurance type
        $this->postJson(route('workflow.store'), [
            'work_type_id' => $tab->id, 'employer_id' => $employer->id, 'employeeNameEn' => 'SECOND',
            'insurance_type' => 'ประกันเอกชน', 'sso_issue_date' => '2026-06-01',
        ])->assertOk();
        $this->assertNull(Employee::where('employeeNameEn', 'SECOND')->value('sso_issue_date'));
    }

    public function test_check_command_lists_employees_with_dates_copied_from_the_one_before(): void
    {
        $employer = Employer::factory()->create();
        $dates = ['passportExpiryDate' => '2030-01-15', 'visaExpiryDate' => '2028-02-01', 'workPermitExpiryDate' => '2028-02-01', 'employeeDob' => '1990-05-05'];
        $first = Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'FIRST PERSON'] + $dates);
        $copied = Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'COPIED PERSON'] + $dates);
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'OTHER PERSON',
            'passportExpiryDate' => '2031-03-03', 'visaExpiryDate' => '2029-01-01', 'workPermitExpiryDate' => '2029-01-01', 'employeeDob' => '1991-01-01']);

        $this->artisan('app:check-copied-dates')
            ->expectsOutputToContain('พบ 1 ราย')
            ->assertSuccessful();
        $this->assertNotNull($first->fresh()); // nothing changed
        $this->assertSame('2030-01-15', substr((string) $copied->fresh()->passportExpiryDate, 0, 10));
    }

    public function test_social_security_and_hospital_document_slots_are_saved(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $employer = Employer::factory()->create();

        // Employees menu, social security
        $this->post(route('employees.store'), [
            'employer_id' => $employer->id, 'employeeNameEn' => 'DOC SS', 'insurance_type' => 'ประกันสังคม',
            'insurance_document_path_social' => \Illuminate\Http\UploadedFile::fake()->create('ss.pdf', 10, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $path = Employee::where('employeeNameEn', 'DOC SS')->value('insurance_document_path');
        $this->assertNotNull($path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($path);

        // Workflow, hospital insurance
        $tab = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'is_system' => true, 'order' => 1]);
        $this->post(route('workflow.store'), [
            'work_type_id' => $tab->id, 'employer_id' => $employer->id, 'employeeNameEn' => 'DOC HOSP', 'insurance_type' => 'ประกันโรงพยาบาล',
            'insurance_document_path_hospital' => \Illuminate\Http\UploadedFile::fake()->create('hosp.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertNotNull(Employee::where('employeeNameEn', 'DOC HOSP')->value('insurance_document_path'));
    }

    public function test_registration_and_renewal_new_employee_save_the_missing_fields(): void
    {
        $employer = Employer::factory()->create();
        foreach (['registration', 'renewal'] as $type) {
            $tab = \App\Models\ResolutionTab::create(['name' => 'มติ ' . $type, 'type' => $type, 'slug' => 'tab-' . $type, 'sort_order' => 1]);
            $name = 'PROD ' . strtoupper($type);
            $this->post(route('production.' . $type . '.store', ['resolutionTab' => $tab->id]), [
                'employer_id' => $employer->id, 'employeeNameEn' => $name,
                'name_suffix' => 'JR', 'visaEndorsementDate' => '2026-05-01', 'visaEndorsementNo' => 'VE9', 'workPermitIssueDate' => '2026-05-02',
                'insurance_type' => 'ประกันสังคม', 'insurance_detail_social' => 'รพ.บางนา',
            ]);
            $e = Employee::where('employeeNameEn', $name)->first();
            $this->assertNotNull($e, $type . ': employee created');
            $this->assertSame('JR', $e->name_suffix, $type);
            $this->assertSame('VE9', $e->visaEndorsementNo, $type);
            $this->assertSame('2026-05-01', substr((string) $e->visaEndorsementDate, 0, 10), $type);
            $this->assertSame('2026-05-02', substr((string) $e->workPermitIssueDate, 0, 10), $type);
            $this->assertSame('รพ.บางนา', $e->insurance_detail, $type);
        }
    }

    public function test_address_popups_forget_the_address_edited_before(): void
    {
        $shared = file_get_contents(resource_path('views/layouts/_app_scripts.blade.php'));
        $this->assertStringContainsString("fields.id.value = '';", substr($shared, strpos($shared, "addressModalEl.addEventListener('hidden.bs.modal'"), 400));
        $create = file_get_contents(resource_path('views/employers/create.blade.php'));
        $onClose = substr($create, strpos($create, "addressModalEl.addEventListener('hidden.bs.modal'"), 500);
        $this->assertStringContainsString('currentlyEditing = null;', $onClose);
        $this->assertStringContainsString('currentlyEditing = null; // "Add" always adds a new address', $create);
    }

    public function test_employees_menu_saves_the_social_security_hospital(): void
    {
        $employer = Employer::factory()->create();
        $this->post(route('employees.store'), [
            'employer_id' => $employer->id,
            'employeeNameEn' => 'MENU ONE',
            'insurance_type' => 'ประกันสังคม',
            'insurance_detail_social' => 'รพ.ศรีราชา',
        ])->assertSessionHasNoErrors();

        $this->assertSame('รพ.ศรีราชา', Employee::where('employeeNameEn', 'MENU ONE')->value('insurance_detail'));
    }
}

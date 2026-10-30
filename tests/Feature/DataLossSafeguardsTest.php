<?php

namespace Tests\Feature;

use App\Jobs\ProcessBulkAttachmentMove;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\ActivityLogPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "Data / files went missing" reports (2026-10-30):
 *  - work-card quick edits no longer blank the social-security dates
 *  - replaced / removed attachments are kept in the archive, and the
 *    history says so
 *  - moving an employer to the trash keeps its files
 *  - quiet writes (attachment-move tool, 24 h auto-apply) are now in the
 *    employee's history
 *  - "other document" default names no longer overwrite names typed per record
 *  - previews flag a file the record points to but the server doesn't have
 */
class DataLossSafeguardsTest extends TestCase
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
        Storage::fake('public');
        Storage::fake('private');
    }

    private function employee(array $attrs = []): Employee
    {
        return Employee::factory()->create(array_merge(['employer_id' => Employer::factory()->create()->id], $attrs));
    }

    public function test_quick_edit_keeps_the_social_security_dates(): void
    {
        $e = $this->employee(['insurance_type' => 'ประกันสังคม', 'sso_issue_date' => '2025-01-10', 'sso_expiry_date' => '2027-01-09']);

        // What the work card's RA-number quick edit sends
        $this->put(route('employees.update', $e), [
            'employer_id' => $e->employer_id, 'employeeNameEn' => $e->employeeNameEn,
            'name_list_number' => 'RA-1', 'employee_reference_id' => 'REF-1',
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $e->refresh();
        $this->assertSame('RA-1', $e->name_list_number);
        $this->assertSame('2025-01-10', optional($e->sso_issue_date)->format('Y-m-d') ?? substr((string) $e->getRawOriginal('sso_issue_date'), 0, 10));
        $this->assertNotNull($e->getRawOriginal('sso_expiry_date'));

        // Changing the insurance type away from social security still clears them (as before)
        $this->put(route('employees.update', $e), [
            'employer_id' => $e->employer_id, 'employeeNameEn' => $e->employeeNameEn, 'insurance_type' => 'ประกันเอกชน',
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertNull($e->fresh()->getRawOriginal('sso_issue_date'));
    }

    public function test_a_replaced_attachment_is_archived_and_the_history_says_so(): void
    {
        Storage::disk('public')->put('employee_files/1/old.pdf', 'old');
        $e = $this->employee(['employee_doc_1' => 'employee_files/1/old.pdf']);

        $this->put(route('employees.update', $e), [
            'employer_id' => $e->employer_id, 'employeeNameEn' => $e->employeeNameEn,
            'employee_doc_1' => UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'),
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $this->assertFalse(Storage::disk('public')->exists('employee_files/1/old.pdf'));
        $archived = Storage::disk('private')->allFiles('attachment_archive/employees/' . $e->id);
        $this->assertCount(1, $archived);
        $this->assertSame('old', Storage::disk('private')->get($archived[0]));

        $log = ActivityLog::where('subject_id', $e->id)->where('subject_type', Employee::class)->where('action', 'update')->latest('id')->first();
        $this->assertSame($archived[0], $log->properties['archived_files']['employee_doc_1']);
        $this->assertStringContainsString('ไฟล์เดิมเก็บสำรองไว้', implode(' ', ActivityLogPresenter::changeLines($log)));

        // The employee's history page (Activity Log → search the employee) shows it, with who and when
        $html = $this->get(route('admin.activity-logs.subject-history', ['subject_type' => Employee::class, 'subject_id' => $e->id]))->assertOk()->getContent();
        $this->assertStringContainsString('ไฟล์เดิมเก็บสำรองไว้', $html);
        $this->assertStringContainsString('เปลี่ยนไฟล์ในช่อง', $html);
    }

    public function test_trashing_an_employer_keeps_its_files(): void
    {
        Storage::disk('public')->put('employer_documents/cert.pdf', 'cert');
        $er = Employer::factory()->create(['employer_doc_company' => 'employer_documents/cert.pdf']);

        $this->delete(route('employers.destroy', $er));

        $this->assertSoftDeleted($er);
        $this->assertTrue(Storage::disk('public')->exists('employer_documents/cert.pdf'));
    }

    public function test_attachment_move_tool_archives_and_writes_history(): void
    {
        Storage::disk('public')->put('employee_files/1/a.pdf', 'A');
        Storage::disk('public')->put('employee_files/1/b.pdf', 'B');
        $e = $this->employee(['employee_doc_9' => 'employee_files/1/a.pdf', 'employee_doc_10' => 'employee_files/1/b.pdf']);

        (new ProcessBulkAttachmentMove([$e->id], 'employee_doc_9', 'employee_doc_10', 'move_delete'))->handle();

        $e->refresh();
        $this->assertSame('employee_files/1/a.pdf', $e->employee_doc_10);
        $this->assertNull($e->employee_doc_9);
        $this->assertCount(1, Storage::disk('private')->allFiles('attachment_archive/employees/' . $e->id)); // b.pdf kept

        $log = ActivityLog::where('subject_id', $e->id)->where('description', 'like', 'เครื่องมือย้ายไฟล์แนบ%')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertStringContainsString('ย้ายไฟล์จากช่อง', implode(' ', ActivityLogPresenter::changeLines($log)));
    }

    public function test_auto_apply_after_24_hours_is_in_the_history(): void
    {
        SystemSetting::create(['key' => 'registration_auto_visa_expiry', 'value' => '2028-12-31', 'group' => 'registration']);
        $e = $this->employee(['status' => 'registration_completed', 'visaExpiryDate' => '2026-01-01']);
        $e->forceFill(['resolution_completed_at' => now()->subDays(2), 'resolution_settings_applied' => false])->saveQuietly();

        $this->artisan('app:update-resolution-data')->assertSuccessful();

        $log = ActivityLog::where('subject_id', $e->id)->where('description', 'like', 'ระบบปรับข้อมูลอัตโนมัติ%')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertSame('2026-01-01', substr((string) $log->properties['old']['visaExpiryDate'], 0, 10));
        $this->assertSame('2028-12-31', substr((string) $log->properties['attributes']['visaExpiryDate'], 0, 10));
    }

    public function test_new_default_document_name_keeps_names_typed_per_employee(): void
    {
        $typed = $this->employee(['other_doc_1_desc' => 'สัญญาจ้างพิเศษ']);
        $blank = $this->employee(['other_doc_1_desc' => null]);

        $this->postJson(route('super-admin.attachments.descriptions.single'), ['entity' => 'employee', 'slot' => 1, 'value' => 'เอกสารใหม่'])
            ->assertOk()->assertJsonPath('affected', 1);

        $this->assertSame('สัญญาจ้างพิเศษ', $typed->fresh()->other_doc_1_desc);
        $this->assertSame('เอกสารใหม่', $blank->fresh()->other_doc_1_desc);
    }

    public function test_preview_flags_a_file_missing_from_the_server(): void
    {
        $e = $this->employee(['employee_doc_2' => 'employee_files/1/gone.pdf']); // recorded, not on disk

        $html = $this->get(route('global.preview', ['type' => 'employee', 'id' => $e->id]))->assertOk()->getContent();

        $this->assertStringContainsString('is-missing', $html);
        $this->assertStringContainsString(__('File missing from the server'), $html);
    }

    public function test_email_code_change_is_recorded_without_its_value(): void
    {
        $e = $this->employee(['password' => 'old-code-123']);
        $e->update(['password' => 'new-code-456']);

        $log = ActivityLog::where('subject_id', $e->id)->where('action', 'update')->latest('id')->first();
        $this->assertNotNull($log);
        $json = json_encode($log->properties, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('new-code-456', $json);
        $this->assertStringNotContainsString('old-code-123', $json);
        $this->assertArrayHasKey('password', $log->properties['attributes']);
    }
}

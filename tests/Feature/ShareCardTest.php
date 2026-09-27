<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Notification;
use App\Models\User;
use App\Services\ShareCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Drag / copy / share employee & employer cards outside the program —
 * ShareCardService, ShareCardController, components/share-handle.
 */
class ShareCardTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'super-admin'): User
    {
        Role::findOrCreate($role);
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function employee(): Employee
    {
        $employee = Employee::factory()->create();
        $employee->forceFill([
            'employeeTitleEn' => 'Miss', 'employeeNameEn' => 'MEE MEE HLAING',
            'employeeTitleTh' => 'นางสาว', 'employeeNameTh' => 'มี่ มี่ ไล',
            'employeeNationality' => 'เมียนมา', 'employeePassport' => 'MH123456',
            'passportExpiryDate' => '2028-05-12', 'employeeDob' => '1995-02-01',
            'employeePhone' => '0812345678',
        ])->save();
        return $employee->fresh();
    }

    public function test_employee_share_text_is_readable_and_uses_default_fields(): void
    {
        $employee = $this->employee();

        $data = $this->actingAs($this->user())->getJson(route('share-card.show', ['employee', $employee->id]))->assertOk()->json();

        $this->assertSame('Miss MEE MEE HLAING', $data['title']);
        $this->assertStringStartsWith('👤 Miss MEE MEE HLAING', $data['text']);
        $this->assertStringContainsString('MH123456', $data['text']);
        $this->assertStringContainsString('12/05/2028', $data['text']);
        $this->assertStringContainsString('01/02/1995', $data['text']);
        $this->assertStringNotContainsString('0812345678', $data['text'], 'phone is not shared by default');
        $this->assertStringNotContainsString('{', $data['text']);
        $this->assertSame('employee', $data['chat']['type']);
        $this->assertSame($employee->id, $data['chat']['id']);
    }

    public function test_only_fields_the_admin_allowed_leave_the_server(): void
    {
        $employee = $this->employee();
        ShareCardService::saveAllowedFields(['phone'], []);

        $data = $this->actingAs($this->user())->getJson(route('share-card.show', ['employee', $employee->id]))->assertOk()->json();

        $this->assertStringContainsString('0812345678', $data['text']);
        $this->assertStringNotContainsString('MH123456', $data['text']);
        $this->assertStringNotContainsString('1995', $data['text']);
        $this->assertStringContainsString('MEE MEE HLAING', $data['text'], 'name is always included');
        $this->assertSame(['phone'], $data['fields']);
    }

    public function test_notification_context_adds_the_reason_line(): void
    {
        $employee = $this->employee();
        $n = Notification::create([
            'employee_id' => $employee->id, 'type' => 'visa_expiry', 'message' => 'x',
            'due_date' => now()->subDays(10)->toDateString(), 'status' => 'pending', 'days_remaining' => -10,
        ]);

        $data = $this->actingAs($this->user())
            ->getJson(route('share-card.show', ['employee', $employee->id]) . '?notification=' . $n->id)->assertOk()->json();

        $this->assertStringContainsString('⚠️ วีซ่าหมดอายุ', $data['text']);
        $this->assertStringContainsString('10', $data['alert']['value']);
    }

    public function test_employer_card(): void
    {
        $employer = Employer::factory()->create();
        $employer->forceFill(['employerNameTh' => 'บริษัท ทดสอบ จำกัด', 'employerPhone' => '021234567'])->save();

        $data = $this->actingAs($this->user())->getJson(route('share-card.show', ['employer', $employer->id]))->assertOk()->json();

        $this->assertSame('บริษัท ทดสอบ จำกัด', $data['title']);
        $this->assertStringStartsWith('🏢 บริษัท ทดสอบ จำกัด', $data['text']);
        $this->assertStringContainsString('021234567', $data['text']);
    }

    public function test_each_share_is_written_to_the_activity_log(): void
    {
        $employee = $this->employee();
        $user = $this->user();

        $this->actingAs($user)->postJson(route('share-card.log'), ['type' => 'employee', 'id' => $employee->id, 'method' => 'copy_image'])->assertOk();

        $log = ActivityLog::where('action', 'share')->first();
        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(Employee::class, $log->subject_type);
        $this->assertSame($employee->id, (int) $log->subject_id);
        $this->assertStringContainsString('คัดลอกเป็นรูปการ์ด', $log->description);
        $this->assertSame('copy_image', $log->properties['method']);

        $this->actingAs($user)->postJson(route('share-card.log'), ['type' => 'employee', 'id' => $employee->id, 'method' => 'hack'])->assertStatus(422);
    }

    public function test_settings_page_is_admin_only_and_saves(): void
    {
        $this->actingAs($this->user('staff'))->get(route('admin.settings.share.index'))->assertForbidden();

        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('admin.settings.share.index'))->assertOk()->assertSee('name="employee[]"', false);

        $this->actingAs($admin)->post(route('admin.settings.share.store'), [
            'employee' => ['passport_no', 'not_a_field'],
            'employer' => ['tax_id'],
        ])->assertRedirect();

        $this->assertSame(['employee' => ['passport_no'], 'employer' => ['tax_id']], ShareCardService::allowedFields());
    }

    public function test_share_handle_component_renders_drag_and_menu_hooks(): void
    {
        $html = Blade::render('<x-share-handle type="employee" :id="7" name="A" />');

        $this->assertStringContainsString('draggable="true"', $html);
        $this->assertStringContainsString('data-share-handle', $html);
        $this->assertStringContainsString('data-share-menu', $html);
        $this->assertStringContainsString('data-share-type="employee"', $html);
        $this->assertStringContainsString('data-share-id="7"', $html);
    }

    public function test_logged_in_pages_load_the_share_script(): void
    {
        $html = $this->actingAs($this->user())->get(route('notifications.index'))->assertOk()->getContent();

        $this->assertStringContainsString('window.ShareCard =', $html);
        $this->assertStringContainsString('window.ShareCard.decorateDrag', $html);
    }
}

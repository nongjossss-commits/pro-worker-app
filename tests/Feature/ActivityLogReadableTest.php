<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttachmentSizeScanner;
use App\Support\ActivityLogPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogReadableTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        return $user;
    }

    private function updateLog(array $old, array $new, ?Employee $employee = null): ActivityLog
    {
        return ActivityLog::create([
            'action' => 'update',
            'subject_type' => Employee::class,
            'subject_id' => $employee?->id ?? 999,
            'description' => 'Update Employee',
            'properties' => ['old' => $old, 'attributes' => $new],
        ]);
    }

    public function test_changes_are_described_in_plain_thai(): void
    {
        $log = $this->updateLog(
            ['name_list_number' => null, 'employee_doc_1' => null, 'employeePassword' => 'x', 'employeeNameEn' => 'Old Name'],
            ['name_list_number' => 'RA-123', 'employee_doc_1' => 'employee_files/1/passport.pdf', 'employeePassword' => 'y', 'employeeNameEn' => 'New Name']
        );
        $lines = implode("\n", ActivityLogPresenter::changeLines($log));

        $this->assertStringContainsString('กรอก<strong>เลข RA (outsource)</strong>: <strong>RA-123</strong>', $lines);
        $this->assertStringContainsString('แนบไฟล์ในช่อง <strong>ไฟล์ 1. พาสปอร์ต</strong>', $lines);
        $this->assertStringContainsString('passport.pdf', $lines);
        $this->assertStringContainsString('<strong>ชื่อ-สกุล (อังกฤษ)</strong>: จาก', $lines);
        $this->assertStringNotContainsString("'y'", $lines);
        $this->assertStringNotContainsString('>y<', $lines); // password value never shown
        $this->assertStringContainsString('แก้ไขข้อมูลลูกจ้าง', ActivityLogPresenter::sentence($log));
    }

    public function test_moving_a_file_between_slots_reads_as_a_move(): void
    {
        $log = $this->updateLog(
            ['employee_doc_2' => 'employee_files/1/visa.pdf', 'employee_doc_9' => null],
            ['employee_doc_2' => null, 'employee_doc_9' => 'employee_files/1/visa.pdf']
        );
        $lines = ActivityLogPresenter::changeLines($log);

        $this->assertCount(1, $lines);
        $this->assertStringContainsString('ย้ายไฟล์จากช่อง <strong>ไฟล์ 2. วีซ่า</strong> ไปช่อง <strong>ไฟล์เอกสารอื่นๆ 1</strong>', $lines[0]);
    }

    public function test_login_is_logged_once_and_failed_login_is_logged(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-pass')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $this->assertSame(1, ActivityLog::where('action', 'login_failed')->count());

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);
        $this->assertSame(1, ActivityLog::where('action', 'login')->where('user_id', $user->id)->count());

        $this->post('/logout');
        $logout = ActivityLog::where('action', 'logout')->first();
        $this->assertSame('manual', $logout->properties['reason']);
        $this->assertSame('ออกจากระบบ', ActivityLogPresenter::sentence($logout));
    }

    public function test_search_finds_an_employee_and_the_day_log_by_ra_number(): void
    {
        $admin = $this->admin();
        $employee = Employee::factory()->create(['name_list_number' => '6516+16549864654']);
        $log = $this->updateLog(['name_list_number' => null], ['name_list_number' => '6516+16549864654'], $employee);

        $this->actingAs($admin)
            ->getJson(route('admin.activity-logs.search-subject', ['q' => '6516']))
            ->assertOk()
            ->assertJsonFragment(['id' => $employee->id, 'type' => 'employee']);

        $d = $log->created_at;
        $this->actingAs($admin)
            ->get(route('admin.activity-logs.day', [$d->year, $d->month, $d->day]) . '?search=6516')
            ->assertOk()
            ->assertSee('เลข RA (outsource)', false);
    }

    public function test_attachment_size_menu_lists_big_files_with_their_owner(): void
    {
        $dir = storage_path('app/public/employee_files/test-size-scan');
        File::ensureDirectoryExists($dir);
        $path = 'employee_files/test-size-scan/big-passport.jpg';
        file_put_contents(storage_path('app/public/' . $path), str_repeat('x', 6 * 1024 * 1024));

        try {
            $employee = Employee::factory()->create(['employee_doc_1' => $path, 'employeeNameEn' => 'Big File Person']);

            $result = app(AttachmentSizeScanner::class)->scan(5 * 1024 * 1024, true);
            $file = collect($result['files'])->firstWhere('path', $path);
            $this->assertNotNull($file);
            $this->assertSame($employee->id, $file['owners'][0]['id']);
            $this->assertSame('ไฟล์ 1. พาสปอร์ต', $file['owners'][0]['field']);

            // The page frame loads at once; the list comes from the list route.
            $this->actingAs($this->admin())
                ->get(route('admin.attachment-sizes.index', ['min' => 5]))
                ->assertOk()
                ->assertSee('id="asz-list"', false)
                ->assertSee('attachment-sizes\/list', false);
            $this->actingAs($this->admin())
                ->get(route('admin.attachment-sizes.list', ['min' => 5, 'refresh' => 1]))
                ->assertOk()
                ->assertSee('big-passport.jpg')
                ->assertSee('Big File Person');
        } finally {
            File::deleteDirectory($dir);
        }
    }
}

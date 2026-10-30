<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Support\AttachmentArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-10-30:
 *  - two people editing the same employee: a form opened before someone
 *    else's save no longer puts their old value back
 *  - replaced files are kept 30 days, then deleted; only Super Admin, or
 *    users it was granted to, can see them
 */
class StaleFormAndArchiveAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Role::findOrCreate($role);
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function employee(): Employee
    {
        return Employee::factory()->create([
            'employer_id' => Employer::factory()->create()->id,
            'employeePassport' => 'AA111', 'father_name' => 'Old Father',
        ]);
    }

    public function test_a_stale_form_does_not_undo_someone_elses_change(): void
    {
        $a = $this->user('super-admin');
        $b = $this->user('super-admin');
        $e = $this->employee();
        $openedAt = now()->subMinute()->timestamp; // A opens the form

        $this->actingAs($b);
        $e->update(['employeePassport' => 'BB222']); // B fixes the passport afterwards

        // A saves: the form still holds the old passport, A only changed the father's name
        $response = $this->actingAs($a)->put(route('employees.update', $e), [
            'employer_id' => $e->employer_id, 'employeeNameEn' => $e->getRawOriginal('employeeNameEn'),
            'employeePassport' => 'AA111', 'father_name' => 'New Father',
            '_form_loaded_at' => $openedAt,
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $e->refresh();
        $this->assertSame('BB222', $e->employeePassport);   // B's change kept
        $this->assertSame('New Father', $e->father_name);   // A's change saved
        $this->assertNotEmpty($response->json('kept_notice'));
    }

    public function test_a_field_both_people_changed_takes_the_later_save(): void
    {
        $a = $this->user('super-admin');
        $e = $this->employee();
        $openedAt = now()->subMinute()->timestamp;
        $e->update(['employeePassport' => 'BB222']);

        $this->actingAs($a)->put(route('employees.update', $e), [
            'employer_id' => $e->employer_id, 'employeeNameEn' => $e->getRawOriginal('employeeNameEn'),
            'employeePassport' => 'CC333', '_form_loaded_at' => $openedAt,
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $this->assertSame('CC333', $e->fresh()->employeePassport);
    }

    public function test_replaced_files_page_is_for_super_admin_and_granted_users_only(): void
    {
        Storage::fake('private');
        $e = $this->employee();
        Storage::disk('private')->put("attachment_archive/employees/{$e->id}/" . now()->format('Ymd_His') . '_employee_doc_1_old.pdf', 'old');
        $file = basename(Storage::disk('private')->files("attachment_archive/employees/{$e->id}")[0]);

        $this->actingAs($this->user('super-admin'))->get(route('attachment-archive.index', ['employees', $e->id]))
            ->assertOk()->assertSee('old.pdf');

        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('attachment-archive.index', ['employees', $e->id]))->assertForbidden();
        $this->get(route('attachment-archive.show', ['employees', $e->id, $file]))->assertForbidden();

        $admin->givePermissionTo('view-attachment-archive');
        $this->actingAs($admin->fresh())->get(route('attachment-archive.show', ['employees', $e->id, $file]))->assertOk();
        // only this record's own kept files
        $this->get(route('attachment-archive.show', ['employees', $e->id, '..%2F..%2F.env']))->assertNotFound();
    }

    public function test_super_admin_can_tick_the_permission_for_an_admin(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($this->user('super-admin'))->get(route('admin.users.edit', $admin))
            ->assertOk()->assertSee('value="view-attachment-archive"', false);

        $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role_name' => 'admin',
            'permissions' => ['view-attachment-archive'],
        ]);
        $this->assertTrue($admin->fresh()->hasDirectPermission('view-attachment-archive'));
    }

    public function test_kept_files_are_deleted_after_30_days(): void
    {
        Storage::fake('private');
        $old = 'attachment_archive/employees/5/' . now()->subDays(31)->format('Ymd_His') . '_employee_doc_1_a.pdf';
        $new = 'attachment_archive/employees/5/' . now()->subDays(2)->format('Ymd_His') . '_employee_doc_2_b.pdf';
        Storage::disk('private')->put($old, 'x');
        Storage::disk('private')->put($new, 'y');

        $this->artisan('app:prune-attachment-archive')->assertSuccessful();

        $this->assertFalse(Storage::disk('private')->exists($old));
        $this->assertTrue(Storage::disk('private')->exists($new));
        $files = AttachmentArchive::files('employees', 5);
        $this->assertSame('employee_doc_2', $files[0]['field']);
        $this->assertSame('b.pdf', $files[0]['name']);
    }
}

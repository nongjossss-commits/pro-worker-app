<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\RoleAndPermissionSeeder;

class RolesPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_cannot_access_admin_page()
    {
        $response = $this->get(route('admin.roles_permissions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden()
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($user)->get(route('admin.roles_permissions.index'));
        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_page()
    {
        // สร้าง Admin User ขึ้นมาใหม่สำหรับ Test นี้โดยเฉพาะ
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        // --- โค้ดนักสืบ ---
        // ตรวจสอบว่า User คนนี้มี Role 'admin' จริงหรือไม่
        if (! $adminUser->hasRole('admin')) {
            $this->fail('Assertion failed: The created user does not have the "admin" role.');
        }

        // ตรวจสอบว่า Role 'admin' มี Permission อะไรบ้าง
        $adminRole = Role::findByName('admin');
        dump('Admin Role Permissions:', $adminRole->permissions->pluck('name')->toArray());

        // ตรวจสอบว่า User คนนี้มี Permission โดยตรงหรือไม่ (ถ้ามี)
        dump('Direct User Permissions:', $adminUser->getDirectPermissions()->pluck('name')->toArray());

        // ตรวจสอบ Permission ทั้งหมดของ User (ทั้งทางตรงและผ่าน Role)
        dump('All User Permissions (via roles):', $adminUser->getAllPermissions()->pluck('name')->toArray());
        // --- สิ้นสุดโค้ดนักสืบ ---

        $response = $this->actingAs($adminUser)->get(route('admin.roles_permissions.index'));

        // หาก Test ล้มเหลว ให้แสดงเนื้อหาของหน้าที่ได้รับกลับมาทั้งหมดเพื่อดูว่ามันคือหน้าอะไร
        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $response->assertSeeText('Manage Roles and Permissions');
    }
}
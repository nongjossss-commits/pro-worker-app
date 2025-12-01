<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'use-chat', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('use-chat');
        $staffRole->givePermissionTo('use-chat');

        Storage::fake('public');
    }

    public function test_fetch_contacts_admin_access()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('staff');

        $response = $this->actingAs($admin)->getJson(route('chat.contacts'));

        $response->assertStatus(200);
        // Should contain community group and other user
        $response->assertJsonFragment(['name' => 'Community Chat']);
        $response->assertJsonFragment(['name' => $otherUser->name]);
    }

    public function test_create_group_with_avatar()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $file = UploadedFile::fake()->image('group_icon.jpg');

        $response = $this->actingAs($admin)->postJson(route('chat.groups.create'), [
            'name' => 'Test Group',
            'avatar' => $file,
            'members' => []
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('chat_groups', ['name' => 'Test Group']);

        $group = ChatGroup::where('name', 'Test Group')->first();
        $this->assertNotNull($group->avatar_path);
        Storage::disk('public')->assertExists($group->avatar_path);
    }

    public function test_update_group_info()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $group = ChatGroup::create([
            'name' => 'Old Name',
            'type' => 'private_group',
            'created_by' => $admin->id
        ]);
        $group->members()->attach($admin->id, ['role' => 'admin']);

        $newFile = UploadedFile::fake()->image('new_icon.jpg');

        $response = $this->actingAs($admin)->postJson(route('chat.groups.update', $group->id), [
            'name' => 'New Name',
            'avatar' => $newFile
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('chat_groups', ['id' => $group->id, 'name' => 'New Name']);

        $group->refresh();
        Storage::disk('public')->assertExists($group->avatar_path);
    }

    public function test_search_users_filter()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $staff = User::factory()->create(['name' => 'Staff Member']);
        $staff->assignRole('staff');

        $response = $this->actingAs($admin)->getJson(route('chat.users.search', ['q' => 'Staff']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['value' => 'Staff Member']);
    }

    public function test_search_users_in_group_context()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $staffInGroup = User::factory()->create(['name' => 'In Group']);
        $staffInGroup->assignRole('staff');

        $staffOutGroup = User::factory()->create(['name' => 'Out Group']);
        $staffOutGroup->assignRole('staff');

        $group = ChatGroup::create(['name' => 'Context Group', 'created_by' => $admin->id]);
        $group->members()->attach($admin->id, ['role' => 'admin']);
        $group->members()->attach($staffInGroup->id, ['role' => 'member']);

        // Search with group_id context
        $response = $this->actingAs($admin)->getJson(route('chat.users.search', [
            'q' => 'Group',
            'group_id' => $group->id
        ]));

        $response->assertStatus(200);
        $response->assertJsonFragment(['value' => 'In Group']);
        $response->assertJsonMissing(['value' => 'Out Group']);
    }
}

<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SuperAdminSetting;
use App\Models\User;
use App\Services\SuperAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SuperAdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache before each test
        Cache::forget(SuperAdminService::CACHE_KEY);
    }

    public function test_toggle_visibility_deletes_record_on_true()
    {
        // Create role
        \Spatie\Permission\Models\Role::create(['name' => 'super-admin']);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $key = 'dashboard';

        // Initial state: Default is visible (no record)
        $this->assertTrue(\App\Facades\SuperAdmin::isVisible($key));
        $this->assertDatabaseMissing('super_admin_settings', ['key' => $key]);

        // 1. Hide it
        $response = $this->actingAs($user)
            ->postJson(route('super-admin.settings.update-visibility'), [
                'key' => $key,
                'is_visible' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify DB: Record created with false
        $this->assertDatabaseHas('super_admin_settings', [
            'key' => $key,
            'is_visible' => 0,
        ]);

        // Verify Service (cache cleared by controller)
        // Simulate sidebar rendering
        $this->assertFalse(\App\Facades\SuperAdmin::isVisible($key));

        // 2. Show it again (Delete record logic)
        $response = $this->actingAs($user)
            ->postJson(route('super-admin.settings.update-visibility'), [
                'key' => $key,
                'is_visible' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify DB: Record should be DELETED because default is true
        $this->assertDatabaseMissing('super_admin_settings', [
            'key' => $key,
        ]);

        // Verify Service
        $this->assertTrue(\App\Facades\SuperAdmin::isVisible($key));
    }
}

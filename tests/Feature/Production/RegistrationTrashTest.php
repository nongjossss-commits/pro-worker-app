<?php

namespace Tests\Feature\Production;

use App\Models\User;
use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RegistrationTrashTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'edit-employees']));

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);
    }

    public function test_can_fetch_trash_items()
    {
        // Create deleted employees (enough to trigger pagination)
        Employee::factory()->count(15)->create([
            'status' => 'registration_pending',
            'deleted_at' => now()
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->get(route('production.registration.trash'));

        $response->assertStatus(200);
        // Ensure it returns the partial view
        $response->assertViewIs('production.registration.partials.trash_list');
        // Ensure pagination links are present (Bootstrap class)
        $response->assertSee('pagination');
    }

    public function test_trash_pagination_returns_partial()
    {
        // Create 15 deleted employees (assuming pagination is 10)
        Employee::factory()->count(15)->create([
            'status' => 'registration_pending',
            'deleted_at' => now()
        ]);

        // Request page 2
        $response = $this->actingAs($this->adminUser)
                         ->get(route('production.registration.trash', ['page' => 2]));

        $response->assertStatus(200);
        $response->assertViewIs('production.registration.partials.trash_list');
    }
}

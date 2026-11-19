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
    protected $tab;
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Registration / Renewal became multi-tab menus (one ResolutionTab per
        // resolution) - every route now carries {resolutionTab}. One tab here,
        // set as the default route parameter, so route(...) keeps working.
        $this->tab = \App\Models\ResolutionTab::create(['name' => 'Test registration', 'type' => 'registration', 'slug' => 'test-registration', 'sort_order' => 1, 'is_default' => true]);
        \Illuminate\Support\Facades\URL::defaults(['resolutionTab' => $this->tab->id]);

        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'edit-employees']));

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);
    }

    public function test_can_fetch_trash_items()
    {
        // Create deleted employees (enough to trigger pagination)
        Employee::factory()->count(15)->create(['resolution_tab_id' => $this->tab->id,
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
        Employee::factory()->count(15)->create(['resolution_tab_id' => $this->tab->id,
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

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Main sidebar grouped into collapsible sections (components/sidebar-group).
 * Items keep their own permission checks; a group with nothing visible for
 * the user is not rendered; the group of the current page starts open.
 */
class SidebarGroupsTest extends TestCase
{
    use RefreshDatabase;

    private function render(User $user, string $routeName = 'dashboard'): string
    {
        $this->actingAs($user);
        // The menu decides "active" from the current route.
        $this->app['request']->setRouteResolver(fn () => app('router')->getRoutes()->getByName($routeName)->bind($this->app['request']));
        return view('partials.sidebar-menu')->render();
    }

    private function menuGroups(string $html): array
    {
        preg_match_all('/data-bs-target="#sidebar-group-([a-z]+)"/', $html, $m);
        return $m[1];
    }

    public function test_super_admin_sees_the_grouped_menu_with_the_current_group_open(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->render($admin, 'employees.index');

        foreach (['alerts', 'data', 'production', 'finance', 'system'] as $g) {
            $this->assertContains($g, $this->menuGroups($html));
        }
        // Employees page → "Records" group open, the others closed.
        $this->assertStringContainsString('class="collapse sidebar-group-body show" id="sidebar-group-data"', $html);
        $this->assertStringContainsString('class="collapse sidebar-group-body " id="sidebar-group-system"', $html);
        // One group open at a time.
        $this->assertSame(substr_count($html, 'data-sidebar-group'), substr_count($html, 'data-bs-parent="#main-nav"'));
        $this->assertStringContainsString(route('employees.index'), $html);
        $this->assertStringContainsString(route('super-admin.settings.index'), $html);
    }

    public function test_limited_user_only_gets_the_groups_they_have_items_in(): void
    {
        Permission::findOrCreate('view-employees');
        Role::findOrCreate('staff');
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $staff->givePermissionTo('view-employees');

        $groups = $this->menuGroups($this->render($staff));
        $this->assertContains('data', $groups);
        $this->assertNotContains('system', $groups);   // no user management / templates / trash / super admin
        $this->assertNotContains('alerts', $groups);   // no notifications / activity log rights

        $nobody = User::factory()->create();
        $this->assertSame([], $this->menuGroups($this->render($nobody)));
    }
}

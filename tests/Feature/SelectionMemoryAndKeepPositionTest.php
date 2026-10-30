<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-10-30:
 *  - ticked employees are remembered across pages / refreshes / tabs until
 *    "Clear Selection", and bulk actions use the whole selection (not just
 *    the boxes on screen) — Notifications, Incomplete Data, the employer's
 *    Employees tab, Groups & Teams, Employment History (own list)
 *  - after an action that reloads the page, it comes back where the user was
 *    (public/js/keep-position.js); moving an employee to the trash removes the
 *    card in place instead of reloading
 * The behaviour itself was checked in a browser; these guard the wiring.
 */
class SelectionMemoryAndKeepPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_layouts_load_the_keep_position_script(): void
    {
        Role::findOrCreate('super-admin');
        $u = User::factory()->create();
        $u->assignRole('super-admin');

        $html = $this->actingAs($u)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('js/keep-position.js', $html);
        $this->assertFileExists(public_path('js/keep-position.js'));
        $this->assertStringContainsString('js/keep-position.js', file_get_contents(resource_path('views/labor/layout.blade.php')));
    }

    public function test_bulk_actions_use_the_remembered_selection(): void
    {
        foreach ([
            'views/notifications/index.blade.php',
            'views/admin/incomplete_employees/index.blade.php',
            'views/employers/edit.blade.php',
            'views/groups/manage.blade.php',
        ] as $view) {
            $blade = file_get_contents(resource_path($view));
            $this->assertStringNotContainsString("document.querySelectorAll('.employee-checkbox:checked')", $blade, $view);
            $this->assertStringNotContainsString("Array.from(activePane.querySelectorAll('.bulk-action-checkbox:checked'))", $blade, $view);
        }

        $history = file_get_contents(resource_path('views/employees/history.blade.php'));
        $this->assertSame(1, substr_count($history, "querySelectorAll('.history-employee-checkbox:checked')")); // only the fallback

        $bar = file_get_contents(resource_path('views/components/bulk-action-bar.blade.php'));
        $this->assertStringContainsString("'bulkSel:' + barId", $bar);
        $this->assertStringContainsString('window.bulkBarSelectedIds', $bar);

        $scripts = file_get_contents(resource_path('views/layouts/_app_scripts.blade.php'));
        $this->assertStringContainsString('window.getSelectedEmployeeCheckboxes = function', $scripts);
    }

    public function test_trashing_an_employee_removes_the_card_without_a_reload(): void
    {
        $scripts = file_get_contents(resource_path('views/layouts/_app_scripts.blade.php'));
        $start = strpos($scripts, "e.target.matches('.delete-employee-form')");
        $block = substr($scripts, $start, 3500);

        $this->assertStringContainsString('box.remove()', $block);
        $this->assertStringContainsString("__('Moved to trash')", $block);
    }
}

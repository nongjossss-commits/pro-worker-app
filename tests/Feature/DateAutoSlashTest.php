<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Date boxes: type 09062026 → 09/06/2026 (public/js/date-autoslash.js) —
 * loaded by both layouts, and the date + time popups accept typing.
 */
class DateAutoSlashTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_layout_loads_the_helper_with_its_warning_text(): void
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('js/date-autoslash.js', $html);
        $this->assertStringContainsString('window.DateAutoSlashText', $html);
        $this->assertStringContainsString('input[type="date"], input.js-flatpickr', $html);
        $this->assertFileExists(public_path('js/date-autoslash.js'));
    }

    public function test_labor_layout_and_datetime_popups_allow_typing(): void
    {
        $labor = file_get_contents(resource_path('views/labor/layout.blade.php'));
        $this->assertStringContainsString('js/date-autoslash.js', $labor);
        $this->assertStringContainsString("altFormat: 'd/m/Y', allowInput: true", $labor);

        foreach ([
            'views/workflow/partials/_item_card.blade.php',
            'views/workflow/partials/day_appointments_list.blade.php',
            'views/production/registration/_employee_card.blade.php',
        ] as $view) {
            $this->assertMatchesRegularExpression("/altFormat: 'd\\/m\\/Y H:i',\\s+allowInput: true/", file_get_contents(resource_path($view)), $view);
        }
    }
}

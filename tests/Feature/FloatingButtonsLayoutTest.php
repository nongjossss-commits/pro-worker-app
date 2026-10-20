<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Floating buttons must not cover page controls:
 *  - the card frame's back-to-top is a small pill on the frame's bottom edge
 *    (not in the bottom-right corner over a card's operator button);
 *  - the page back-to-top on the right sits above the chat launcher.
 * And a SweetAlert input opened over a Bootstrap modal ("Rename team")
 * keeps the focus (focus guard in partials/_ui_theme_scripts).
 */
class FloatingButtonsLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_buttons_are_placed_clear_of_content_and_swal_focus_is_guarded(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin)->get(route('employees.index'))->assertOk()->getContent();

        $this->assertTrue(str_contains($html, 'card-scroll-back-top-btn"'), 'frame back-to-top button rendered');
        $this->assertTrue(str_contains($html, '<span>' . e(__('Back to top')) . '</span>'), 'pill has a label');
        $this->assertTrue((bool) preg_match('/\.card-scroll-back-top-btn \{[^}]*left: 50%;[^}]*bottom: -13px;/s', $html), 'pill centred on the bottom edge');
        $this->assertTrue((bool) preg_match('/\.scroll-to-top\.right \{[^}]*bottom: 92px;/s', $html), 'page button above the chat launcher');
        $this->assertTrue(str_contains($html, '__swalFocusGuard'), 'SweetAlert focus guard loaded');
    }
}

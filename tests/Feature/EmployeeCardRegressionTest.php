<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Card bugs fixed 2026-10-28:
 *  - Employment History tiles: the work-status box pushed the action buttons
 *    out of the tile (cut off) and sat under the nationality row.
 *  - Workflow / resolution cards: free text (an English name like O'Conner,
 *    a location, an insurance type…) written into Alpine x-data as '{{ … }}'
 *    broke the whole x-data block — fields blank, editing dead.
 */
class EmployeeCardRegressionTest extends TestCase
{
    public function test_history_tile_stacks_the_status_box_above_the_buttons(): void
    {
        $css = file_get_contents(resource_path('views/partials/_employee_tiles.blade.php'));

        $this->assertStringContainsString('.emp-tile-grid .list-group-item > .d-flex.w-100:not(.position-absolute) { display: contents !important; }', $css);
        $this->assertStringContainsString('.emp-tile-grid .list-group-item > .position-absolute.top-0.start-0 { order: 3;', $css);
        $this->assertStringContainsString('.emp-tile-grid .list-group-item > .d-flex.w-100 > .ms-auto { order: 4; }', $css);
        $this->assertStringNotContainsString('align-items: stretch; height: 100%; gap: .35rem;', $css);
    }

    public function test_card_scripts_do_not_quote_free_text_by_hand(): void
    {
        foreach ([
            'views/production/registration/_employee_card.blade.php',
            'views/workflow/partials/_item_card.blade.php',
        ] as $view) {
            $blade = file_get_contents(resource_path($view));
            $this->assertDoesNotMatchRegularExpression(
                "/'\{\{\s*\\\$(employee|item|app)[^}]*(Name|email|password|outsource_code|insurance_type|Value|Display|Location|Human)[^}]*\}\}'/",
                $blade,
                $view
            );
        }
    }
}

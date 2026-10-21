<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Phones: list tables become cards (public/js/mobile-table-cards.js +
 * public/css/mobile-table-cards.css, loaded by both layouts); accounting
 * reports / ledgers / document line items keep the side-scrolling table
 * (data-mobile-table="scroll").
 */
class MobileTableCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_loads_the_card_layout_for_phones(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin)->get(route('employers.index'))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'css/mobile-table-cards.css'), 'stylesheet in the main layout');
        $this->assertTrue(str_contains($html, 'js/mobile-table-cards.js'), 'script in the main layout');
        $this->assertTrue(str_contains($html, 'window.MobileTableCardsText'), 'texts passed');
        $this->assertTrue(str_contains($html, 'css/mobile-fit.css'), 'phone fit fixes loaded');

        $labor = file_get_contents(resource_path('views/labor/layout.blade.php'));
        $this->assertTrue(str_contains($labor, 'css/mobile-table-cards.css') && str_contains($labor, 'js/mobile-table-cards.js'), 'Labor layout too');

        $css = file_get_contents(public_path('css/mobile-table-cards.css'));
        $this->assertTrue(str_contains($css, '@media (max-width: 767.98px)'), 'phones only');
    }

    public function test_accounting_reports_keep_the_scrolling_table(): void
    {
        $reports = [
            'financial/books-reports/_content', 'financial/ledger/index', 'financial/ledger/show',
            'financial/reconciliation/index', 'financial/reconciliation/account', 'financial/tax_reports/vat',
            'financial/tax_reports/wht', 'labor/books/show', 'labor/reports/index', 'labor/bills/show',
        ];
        foreach ($reports as $view) {
            $src = file_get_contents(resource_path('views/' . $view . '.blade.php'));
            $this->assertSame(
                substr_count($src, '<table'),
                substr_count($src, '<table data-mobile-table="scroll"'),
                "$view: every table keeps the scrolling layout"
            );
        }
    }
}

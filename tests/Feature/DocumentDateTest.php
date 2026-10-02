<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductionDocumentController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Receipts / tax invoices / advance receipts can be dated back to the day the
 * money arrived (?doc_date=YYYY-MM-DD) — ProductionDocumentController::documentDate().
 */
class DocumentDateTest extends TestCase
{
    private function date(array $query, $default = '2026-07-15 00:00:00'): string
    {
        $c = app(ProductionDocumentController::class);
        $m = new \ReflectionMethod($c, 'documentDate');
        $m->setAccessible(true);
        return $m->invoke($c, Request::create('/x', 'GET', $query), Carbon::parse($default))->format('Y-m-d');
    }

    public function test_chosen_past_date_is_used(): void
    {
        $this->assertSame('2026-01-05', $this->date(['doc_date' => '2026-01-05']));
        $this->assertSame(now()->format('Y-m-d'), $this->date(['doc_date' => now()->format('Y-m-d')]));
    }

    public function test_missing_future_or_invalid_dates_fall_back_to_the_default(): void
    {
        $this->assertSame('2026-07-15', $this->date([]));
        $this->assertSame('2026-07-15', $this->date(['doc_date' => now()->addDay()->format('Y-m-d')]));
        $this->assertSame('2026-07-15', $this->date(['doc_date' => '2026-02-30']));
        $this->assertSame('2026-07-15', $this->date(['doc_date' => '15/07/2026']));
        $this->assertSame('2026-07-15', $this->date(['doc_date' => "2026-01-01'; drop"]));
    }

    public function test_document_shows_the_controller_date_not_always_today(): void
    {
        $layout = file_get_contents(resource_path('views/documents/layout.blade.php'));
        $this->assertStringNotContainsString("{{ date('d/m/Y') }}", $layout);
        $this->assertStringContainsString("Carbon::parse(\$date ?? now())->format('d/m/Y')", $layout);
    }
}

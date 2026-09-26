<?php

namespace Tests\Feature;

use App\Models\LaborBill;
use App\Models\LaborLedgerEntry;
use App\Models\LaborTeam;
use App\Models\LaborTeamMember;
use App\Services\LaborBillPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaborBillPdfTest extends TestCase
{
    use RefreshDatabase;

    private function makeBill(int $entries, string $status = 'issued'): LaborBill
    {
        $team = LaborTeam::create(['name' => 'ทีมทดสอบ', 'is_active' => true, 'customer_address' => '1 ถนนทดสอบ']);
        $member = LaborTeamMember::create(['labor_team_id' => $team->id, 'name' => 'ลูกทีมทดสอบ', 'is_active' => true]);
        for ($i = 0; $i < $entries; $i++) {
            LaborLedgerEntry::create([
                'labor_team_id' => $team->id,
                'labor_team_member_id' => $member->id,
                'entry_date' => '2026-09-' . str_pad((string) (1 + $i % 25), 2, '0', STR_PAD_LEFT),
                'description' => 'ค่ายื่นคำขอ — เลขคำขอ ' . (1000 + $i),
                'amount' => 500 * 3,
                'quantity' => 3,
                'unit_rate' => 500,
                'qty_laos' => 1,
                'qty_myanmar' => 2,
            ]);
        }

        return LaborBill::create([
            'labor_team_id' => $team->id,
            'bill_no' => 'LB-2026-0' . rand(100, 999),
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'previous_balance' => 1000,
            'period_charges' => 1500 * $entries,
            'total_due' => 1000 + 1500 * $entries,
            'status' => $status,
            'issued_at' => now(),
            'void_reason' => $status === 'void' ? 'ทดสอบยกเลิก' : null,
        ])->fresh(['team', 'financialProfile']);
    }

    private function pageCount(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page\b(?!s)#', $pdf);
    }

    public function test_short_bill_fits_on_one_page(): void
    {
        $pdf = app(LaborBillPdfService::class)->generate($this->makeBill(5));

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(1, $this->pageCount($pdf));
    }

    public function test_long_bill_continues_onto_more_pages(): void
    {
        $pdf = app(LaborBillPdfService::class)->generate($this->makeBill(40));

        $this->assertGreaterThan(1, $this->pageCount($pdf));
    }

    public function test_void_and_empty_bills_render(): void
    {
        $this->assertStringStartsWith('%PDF', app(LaborBillPdfService::class)->generate($this->makeBill(2, 'void')));
        $this->assertStringStartsWith('%PDF', app(LaborBillPdfService::class)->generate($this->makeBill(0)));
    }
}

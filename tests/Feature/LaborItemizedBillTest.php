<?php

namespace Tests\Feature;

use App\Models\LaborBill;
use App\Models\LaborChargeType;
use App\Models\LaborLedgerEntry;
use App\Models\LaborTeam;
use App\Models\LaborTeamMember;
use App\Models\User;
use App\Services\LaborBillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bills page: "วางบิลแยกรายการ" (ticked, not-yet-billed entries, one bill per
 * team), "ใบวางบิลเรียกเก็บค่าใช้จ่ายรายเดือน" (per-member monthly charges,
 * one bill/PDF per team) and the per-team billed / paid / unbilled figures.
 */
class LaborItemizedBillTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    private function charge(LaborTeam $team, ?LaborChargeType $type, string $date, float $amount, ?LaborTeamMember $member = null): LaborLedgerEntry
    {
        return LaborLedgerEntry::create([
            'labor_team_id' => $team->id,
            'labor_team_member_id' => $member?->id,
            'labor_charge_type_id' => $type?->id,
            'entry_date' => $date,
            'description' => ($type->name ?? 'รายการ') . ' ' . $date,
            'amount' => $amount,
        ]);
    }

    public function test_unbilled_list_filters_by_type_and_month_and_skips_statement_periods(): void
    {
        $team = LaborTeam::create(['name' => 'ทีม A', 'is_active' => true]);
        $mou = LaborChargeType::create(['name' => 'MOU', 'rate' => 100, 'is_active' => true]);
        $other = LaborChargeType::create(['name' => 'อื่น ๆ', 'rate' => 50, 'is_active' => true]);

        $old = $this->charge($team, $mou, '2026-08-10', 100);       // covered by the statement below
        $sep = $this->charge($team, $mou, '2026-09-05', 200);
        $sepOther = $this->charge($team, $other, '2026-09-06', 50);
        $oct = $this->charge($team, $mou, '2026-10-02', 300);
        LaborBill::create([
            'labor_team_id' => $team->id, 'bill_no' => 'LB-2026-0001', 'period_start' => '2026-08-01', 'period_end' => '2026-08-31',
            'previous_balance' => 0, 'period_charges' => 100, 'total_due' => 100, 'status' => 'issued', 'issued_at' => now(),
        ]);

        $ids = fn ($params) => collect($this->actingAs($this->admin)->getJson(route('labor.bills.unbilled', $params))->assertOk()->json('entries'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$sep->id, $sepOther->id, $oct->id], $ids(['mode' => 'all']));
        $this->assertEqualsCanonicalizing([$sep->id, $oct->id], $ids(['mode' => 'all', 'charge_type_ids' => [$mou->id]]));
        $this->assertSame([$sep->id], $ids(['mode' => 'month', 'month' => '2026-09', 'charge_type_ids' => [$mou->id]]));
        $this->assertSame([$oct->id], $ids(['mode' => 'day', 'date' => '2026-10-02']));
        $this->assertNotContains($old->id, $ids(['mode' => 'all']));

        // From–to range on the date recorded, across months.
        $this->assertEqualsCanonicalizing([$sep->id, $sepOther->id], $ids(['mode' => 'range', 'from' => '2026-09-01', 'to' => '2026-09-30']));
        $this->assertEqualsCanonicalizing([$sepOther->id, $oct->id], $ids(['mode' => 'range', 'from' => '2026-09-06', 'to' => '2026-10-31']));

        // Billed / all: billed rows come back with the bill they're on.
        app(LaborBillService::class)->generateItemized([$sep->id]);
        $billed = $this->actingAs($this->admin)->getJson(route('labor.bills.unbilled', ['status' => 'billed', 'mode' => 'all']))->json('entries');
        $this->assertEqualsCanonicalizing([$old->id, $sep->id], array_column($billed, 'id'));
        $this->assertSame('LB-2026-0001', collect($billed)->firstWhere('id', $old->id)['bill_no']);
        $this->assertNotNull(collect($billed)->firstWhere('id', $sep->id)['bill_no']);
        $this->assertCount(4, $ids(['status' => 'all', 'mode' => 'all']));
        $this->assertEqualsCanonicalizing([$sepOther->id, $oct->id], $ids(['status' => 'unbilled', 'mode' => 'all']));

        // Team filter keeps other teams out.
        $teamB = LaborTeam::create(['name' => 'ทีม B', 'is_active' => true]);
        $bEntry = $this->charge($teamB, $mou, '2026-09-07', 10);
        $this->assertNotContains($bEntry->id, $ids(['team_id' => $team->id, 'status' => 'all', 'mode' => 'all']));
        $this->assertSame([$bEntry->id], $ids(['team_id' => $teamB->id, 'status' => 'all', 'mode' => 'all']));
    }

    public function test_itemized_bill_takes_only_ticked_entries_one_bill_per_team_and_void_releases_them(): void
    {
        $a = LaborTeam::create(['name' => 'ทีม A', 'is_active' => true]);
        $b = LaborTeam::create(['name' => 'ทีม B', 'is_active' => true]);
        $mou = LaborChargeType::create(['name' => 'MOU', 'rate' => 100, 'is_active' => true]);
        $a1 = $this->charge($a, $mou, '2026-09-01', 100);
        $a2 = $this->charge($a, $mou, '2026-09-02', 150);
        $a3 = $this->charge($a, $mou, '2026-09-03', 999);   // not ticked
        $b1 = $this->charge($b, $mou, '2026-09-04', 400);

        $this->actingAs($this->admin)->post(route('labor.bills.itemized.store'), [
            'entry_ids' => [$a1->id, $a2->id, $b1->id],
            'charge_type_ids' => [$mou->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bills = LaborBill::orderBy('id')->get();
        $this->assertCount(2, $bills);
        $billA = $bills->firstWhere('labor_team_id', $a->id);
        $this->assertSame('itemized', $billA->bill_type);
        $this->assertSame([$mou->id], $billA->charge_type_ids);
        $this->assertEquals(0, $billA->previous_balance);
        $this->assertEquals(250, $billA->total_due);
        $this->assertEquals(400, $bills->firstWhere('labor_team_id', $b->id)->total_due);
        Storage::disk('public')->assertExists($billA->pdf_path);
        $this->assertNull($a3->fresh()->labor_bill_id);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $this->actingAs($this->admin)->get(route('labor.bills.show', $billA))->assertOk()->assertSee($billA->bill_no);

        // Billed entries can't be billed again, edited or deleted.
        $this->actingAs($this->admin)->post(route('labor.bills.itemized.store'), ['entry_ids' => [$a1->id]])
            ->assertSessionHasErrors('entry_ids');
        $this->actingAs($this->admin)->delete(route('labor.ledger.destroy', [$a, $a1]))->assertSessionHasErrors('entry');
        $this->assertNotSoftDeleted($a1);

        // Voiding releases them.
        app(LaborBillService::class)->void($billA, 'ผิด');
        $this->assertNull($a1->fresh()->labor_bill_id);
        $this->assertNull($a2->fresh()->labor_bill_id);
    }

    public function test_monthly_bill_records_charges_and_makes_one_bill_per_team(): void
    {
        $a = LaborTeam::create(['name' => 'ทีม A', 'is_active' => true]);
        $b = LaborTeam::create(['name' => 'ทีม B', 'is_active' => true]);
        $ss = LaborChargeType::create(['name' => 'ประกันสังคม', 'rate' => 0, 'is_active' => true]);
        $m1 = LaborTeamMember::create(['labor_team_id' => $a->id, 'name' => 'สมชาย', 'is_active' => true]);
        $m2 = LaborTeamMember::create(['labor_team_id' => $a->id, 'name' => 'สมหญิง', 'is_active' => true]);
        $m3 = LaborTeamMember::create(['labor_team_id' => $b->id, 'name' => 'สมศรี', 'is_active' => true]);

        $post = fn (string $month, array $rows) => $this->actingAs($this->admin)->post(route('labor.bills.monthly.store'), [
            'month' => $month, 'labor_charge_type_id' => $ss->id, 'entry_date' => $month . '-25', 'rows' => $rows,
        ]);

        $post('2026-09', [
            ['member_id' => $m1->id, 'amount' => 750, 'description' => 'ประกันสังคม กันยายน 2569'],
            ['member_id' => $m2->id, 'amount' => 500, 'description' => 'ประกันสังคม กันยายน 2569'],
            ['member_id' => $m3->id, 'amount' => 750, 'description' => 'ประกันสังคม กันยายน 2569'],
        ])->assertSessionHasNoErrors();

        $bills = LaborBill::where('bill_type', 'monthly')->get();
        $this->assertCount(2, $bills);
        $billA = $bills->firstWhere('labor_team_id', $a->id);
        $this->assertEquals(1250, $billA->total_due);
        $this->assertSame('2026-09-01', $billA->period_start->toDateString());
        $this->assertSame('2026-09-30', $billA->period_end->toDateString());
        $this->assertSame(2, $billA->entries()->count());
        foreach ($bills as $bill) {
            Storage::disk('public')->assertExists($bill->pdf_path);
        }
        // The charges are on the team balance like any other charge.
        $this->assertEquals(1250, (float) $a->ledgerEntries()->sum('amount'));

        // Same member, same month and type → refused.
        $post('2026-09', [['member_id' => $m1->id, 'amount' => 750, 'description' => 'ซ้ำ']])->assertSessionHasErrors('rows');
        $this->assertSame(3, LaborLedgerEntry::count());

        // Next month: last month's amount/description are offered, and this month's bill is flagged.
        $defaults = $this->actingAs($this->admin)->getJson(route('labor.bills.monthly.defaults', ['month' => '2026-10', 'charge_type_id' => $ss->id]))->json('defaults');
        $this->assertEquals(750, $defaults[$m1->id]['amount']);
        $this->assertSame('ประกันสังคม กันยายน 2569', $defaults[$m1->id]['description']);
        $this->assertNull($defaults[$m1->id]['billed_no']);
        $same = $this->actingAs($this->admin)->getJson(route('labor.bills.monthly.defaults', ['month' => '2026-09', 'charge_type_id' => $ss->id]))->json('defaults');
        $this->assertSame($billA->bill_no, $same[$m1->id]['billed_no']);
    }

    public function test_bills_page_shows_billed_paid_and_unbilled_per_team(): void
    {
        $team = LaborTeam::create(['name' => 'ทีมติดตาม', 'is_active' => true]);
        $mou = LaborChargeType::create(['name' => 'MOU', 'rate' => 100, 'is_active' => true]);
        $billed = $this->charge($team, $mou, '2026-09-01', 1000);
        $this->charge($team, $mou, '2026-09-02', 300); // not billed
        app(LaborBillService::class)->generateItemized([$billed->id]);
        LaborLedgerEntry::create(['labor_team_id' => $team->id, 'entry_date' => '2026-09-10', 'description' => 'ชำระ', 'amount' => -400]);

        $this->actingAs($this->admin);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $html = $this->get(route('labor.bills.index'))->assertOk()->getContent();

        $this->assertStringContainsString('ทีมติดตาม', $html);
        // billed 1,000 · paid 400 · remaining 600 · not billed 300
        $this->assertMatchesRegularExpression('/ทีมติดตาม<\/td>\s*<td class="text-end">1,000\.00<\/td>\s*<td class="text-end text-success">400\.00<\/td>\s*<td class="text-end fw-bold text-danger">600\.00<\/td>\s*<td class="text-end text-warning-emphasis fw-semibold">\s*300\.00/u', $html);
        $this->assertStringContainsString('id="itemizedBillModal"', $html);
        $this->assertStringContainsString('id="monthlyBillModal"', $html);
    }
}

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

        $post = fn (string $month, array $rows, string $title = 'ประกันสังคม') => $this->actingAs($this->admin)->post(route('labor.bills.monthly.store'), [
            'month' => $month, 'title' => $title, 'entry_date' => $month . '-25', 'rows' => $rows,
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

        // The typed heading is kept on the bill; the charges carry no charge type.
        $this->assertSame('ประกันสังคม', $billA->title);
        $this->assertSame(0, LaborLedgerEntry::whereNotNull('labor_charge_type_id')->count());

        // Same month and heading (spacing / case ignored) again → the SAME bill is
        // updated: an edited amount, plus a member who wasn't billed yet is added.
        // No second bill, bill number unchanged.
        $m4 = LaborTeamMember::create(['labor_team_id' => $a->id, 'name' => 'สมปอง', 'is_active' => true]);
        $post('2026-09', [
            ['member_id' => $m1->id, 'amount' => 800, 'description' => 'ประกันสังคม กันยายน 2569 (แก้)'],
            ['member_id' => $m4->id, 'amount' => 300, 'description' => 'ประกันสังคม กันยายน 2569'],
        ], '  ประกันสังคม ')->assertSessionHasNoErrors();
        $this->assertSame(2, LaborBill::where('bill_type', 'monthly')->count());
        $billA->refresh();
        $this->assertEquals(1600, $billA->total_due); // 800 + 500 + 300
        $this->assertEquals(1600, $billA->period_charges);
        $this->assertSame(3, $billA->entries()->count());
        $this->assertEquals(800, (float) $billA->entries()->where('labor_team_member_id', $m1->id)->value('amount'));
        $this->assertEquals(1600, (float) $a->ledgerEntries()->sum('amount')); // one figure, not two
        $this->assertSame(4, LaborLedgerEntry::count());
        Storage::disk('public')->assertExists($billA->pdf_path);

        // A bill that already has a payment can't be changed (nothing is saved).
        $billB = LaborBill::where('bill_type', 'monthly')->where('labor_team_id', $b->id)->first();
        \App\Models\LaborBillPayment::create(['labor_bill_id' => $billB->id, 'amount' => 100, 'paid_at' => '2026-09-28', 'payment_method' => 'cash']);
        $entriesBefore = LaborLedgerEntry::count();
        $post('2026-09', [['member_id' => $m3->id, 'amount' => 999, 'description' => 'แก้']])->assertSessionHasErrors('rows');
        $this->assertEquals(750, $billB->fresh()->total_due);
        $this->assertSame($entriesBefore, LaborLedgerEntry::count());

        // A different heading in the same month is a different expense → its own bill.
        $post('2026-09', [['member_id' => $m1->id, 'amount' => 1200, 'description' => 'ค่าจองบิน ชุด ก.ย.']], 'ค่าจองบิน')->assertSessionHasNoErrors();
        $this->assertSame(3, LaborBill::where('bill_type', 'monthly')->count());

        // An older bill made with a charge type (no title) still counts as that heading.
        $old = LaborBill::create([
            'labor_team_id' => $b->id, 'bill_no' => 'LB-OLD-1', 'bill_type' => 'monthly', 'charge_type_ids' => [$ss->id],
            'period_start' => '2026-08-01', 'period_end' => '2026-08-31', 'previous_balance' => 0, 'period_charges' => 750,
            'total_due' => 750, 'status' => 'issued', 'is_auto_generated' => false, 'issued_at' => now(),
        ]);
        LaborLedgerEntry::create(['labor_team_id' => $b->id, 'labor_team_member_id' => $m3->id, 'labor_charge_type_id' => $ss->id,
            'labor_bill_id' => $old->id, 'entry_date' => '2026-08-25', 'description' => 'เก่า', 'amount' => 750]);
        $post('2026-08', [['member_id' => $m3->id, 'amount' => 700, 'description' => 'แก้ยอด ส.ค.']])->assertSessionHasNoErrors();
        $this->assertSame(1, LaborBill::whereDate('period_start', '2026-08-01')->count()); // updated, not re-issued
        $this->assertEquals(700, $old->fresh()->total_due);
        $this->assertSame('ประกันสังคม', $old->fresh()->title);

        // Reopening: no type to choose — each person's latest monthly bill is offered
        // (even right after issuing it), plus the latest heading and recent headings.
        $res = $this->actingAs($this->admin)->getJson(route('labor.bills.monthly.defaults', ['month' => '2026-10']))->json();
        $this->assertEquals(1200, $res['defaults'][$m1->id]['amount']); // latest bill of สมชาย
        $this->assertSame('ค่าจองบิน ชุด ก.ย.', $res['defaults'][$m1->id]['description']);
        $this->assertSame('2026-09', $res['defaults'][$m1->id]['source_month']);
        $this->assertEquals(500, $res['defaults'][$m2->id]['amount']);
        $this->assertSame([], $res['defaults'][$m1->id]['billed']);
        $this->assertSame('ประกันสังคม', $res['last_title']); // heading of the most recently created bill (the old one above)
        $this->assertContains('ประกันสังคม', $res['titles']);
        $same = $this->actingAs($this->admin)->getJson(route('labor.bills.monthly.defaults', ['month' => '2026-09']))->json('defaults');
        $this->assertEqualsCanonicalizing(['ประกันสังคม', 'ค่าจองบิน'], array_column($same[$m1->id]['billed'], 'title'));
        $this->assertContains($billA->bill_no, array_column($same[$m1->id]['billed'], 'bill_no'));
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

<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-10-31 Workflow tab "แจ้งเข้า บต.52 / บต.55 / บต.56":
 *  - a card's "Add" puts more finished change-employer employees of the
 *    same employer on THAT card (no second card);
 *  - "Return to list" takes an employee off a card and back into
 *    "Add Employees" (not a cancel; the employee record is untouched).
 */
class NotifyInReportAddAndReturnTest extends TestCase
{
    use RefreshDatabase;

    private WorkType $notifyIn;
    private WorkType $report;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
        $this->notifyIn = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'is_system' => true, 'order' => 1]);
        $this->report = WorkType::where('slug', WorkType::SLUG_NOTIFY_IN_REPORT)->firstOrFail();
    }

    private function finished(Employer $employer, int $n): array
    {
        $order = ProductionOrder::create(['employer_id' => $employer->id, 'work_type_id' => $this->notifyIn->id, 'status' => 'active']);
        return Employee::factory()->count($n)->create(['employer_id' => $employer->id])->map(fn ($e) => ProductionItem::create([
            'production_order_id' => $order->id, 'employee_id' => $e->id, 'status' => 'completed', 'completed_at' => now(),
        ]))->all();
    }

    private function candidateIds(): array
    {
        return collect($this->getJson(route('workflow.notify_in_report.candidates'))->json('groups'))
            ->flatMap(fn ($g) => collect($g['employees'])->pluck('source_item_id'))->sort()->values()->all();
    }

    private function logged(ProductionOrder $card, string $text): bool
    {
        return ActivityLog::where('subject_type', ProductionOrder::class)->where('subject_id', $card->id)
            ->get()->contains(fn ($l) => str_contains((string) $l->description, $text));
    }

    public function test_add_later_employees_to_the_same_card(): void
    {
        $a = Employer::factory()->create();
        $b = Employer::factory()->create();
        $morning = $this->finished($a, 3);
        $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => collect($morning)->pluck('id')->all()])->assertOk();
        $card = ProductionOrder::where('work_type_id', $this->report->id)->firstOrFail();

        // evening: 2 more for A, 1 for B
        $evening = $this->finished($a, 2);
        $other = $this->finished($b, 1);
        $res = $this->postJson(route('workflow.notify_in_report.store'), [
            'order_id' => $card->id,
            'source_item_ids' => array_merge(collect($evening)->pluck('id')->all(), [$other[0]->id]),
        ])->assertOk()->json();

        $this->assertSame([0, 2], [$res['cards'], $res['employees']]);
        $this->assertSame(1, ProductionOrder::where('work_type_id', $this->report->id)->count(), 'no new card');
        $this->assertSame(5, $card->items()->count());
        // the other employer's employee is not put on A's card and is still offered
        $this->assertSame([$other[0]->id], $this->candidateIds());
        $this->assertTrue($this->logged($card, 'เพิ่มลูกจ้าง 2 ราย'));

        // a finished card takes nobody
        $card->update(['status' => 'completed']);
        $this->postJson(route('workflow.notify_in_report.store'), ['order_id' => $card->id, 'source_item_ids' => [$other[0]->id]])->assertStatus(422);
        // a card of another tab is refused
        $otherTab = ProductionOrder::create(['employer_id' => $b->id, 'work_type_id' => $this->notifyIn->id, 'status' => 'active']);
        $this->postJson(route('workflow.notify_in_report.store'), ['order_id' => $otherTab->id, 'source_item_ids' => [$other[0]->id]])->assertNotFound();
    }

    public function test_return_to_list_takes_the_employee_off_the_card_and_offers_them_again(): void
    {
        $a = Employer::factory()->create();
        $src = $this->finished($a, 2);
        $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => collect($src)->pluck('id')->all()])->assertOk();
        $card = ProductionOrder::where('work_type_id', $this->report->id)->firstOrFail();
        $item = $card->items()->where('source_item_id', $src[0]->id)->firstOrFail();
        $this->assertSame([], $this->candidateIds());

        $this->postJson(route('workflow.notify_in_report.return_item', $item->id))->assertOk()->assertJson(['success' => true]);

        $this->assertSoftDeleted('production_items', ['id' => $item->id]);
        $this->assertSame(1, $card->items()->count());
        $this->assertNotNull(Employee::find($src[0]->employee_id), 'employee untouched');
        $this->assertSame([$src[0]->id], $this->candidateIds(), 'offered again');
        $this->assertTrue($this->logged($card, 'กลับไปเป็นรายชื่อรอเลือก'));

        // ... and can go back on the same card
        $this->postJson(route('workflow.notify_in_report.store'), ['order_id' => $card->id, 'source_item_ids' => [$src[0]->id]])->assertOk();
        $this->assertSame(2, $card->items()->count());

        // only for pending employees on an open card of this tab
        $done = $card->items()->firstOrFail();
        $done->update(['status' => 'completed']);
        $this->postJson(route('workflow.notify_in_report.return_item', $done->id))->assertStatus(422);
        $otherTab = ProductionOrder::create(['employer_id' => $a->id, 'work_type_id' => $this->notifyIn->id, 'status' => 'active']);
        $elsewhere = ProductionItem::create(['production_order_id' => $otherTab->id, 'employee_id' => $src[1]->employee_id, 'status' => 'pending']);
        $this->postJson(route('workflow.notify_in_report.return_item', $elsewhere->id))->assertForbidden();
    }

    public function test_card_and_employee_buttons_are_on_the_page(): void
    {
        $a = Employer::factory()->create();
        $src = $this->finished($a, 1);
        $this->postJson(route('workflow.notify_in_report.store'), ['source_item_ids' => [$src[0]->id]])->assertOk();
        $card = ProductionOrder::where('work_type_id', $this->report->id)->firstOrFail();
        $item = $card->items()->firstOrFail();

        $html = $this->get(route('workflow.index', ['tab' => $this->report->slug]))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'window.openNotifyInReportModal(' . $card->id . ', ' . $a->id . ')'), 'window.openNotifyInReportModal(' . $card->id . ', ' . $a->id . ')');
        $this->assertTrue(str_contains($html, 'window.returnNotifyInReportItem = function'), 'window.returnNotifyInReportItem = function');
        $this->assertTrue(str_contains($html, 'notifyInReportTarget'), 'notifyInReportTarget');

        // employee cards load when the card is opened
        $items = $this->get(route('workflow.items', $card->id))->assertOk()->getContent();
        $this->assertTrue(str_contains($items, 'returnNotifyInReportItem(' . $item->id . ')'), 'return button');
        $this->assertFalse(str_contains($items, 'sendBackToPreProduction(' . $item->id . ')'), 'no preparation stage in this tab');
    }
}

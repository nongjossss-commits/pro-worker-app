<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Notification;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Page-load fixes (2026-10-24): prebuilt Tailwind CSS instead of the Play CDN,
 * Workflow finance modal fetched on open, and per-page preloads that replace
 * one-query-per-card lookups.
 */
class PageLoadPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        return $user;
    }

    public function test_layout_uses_prebuilt_tailwind_css_not_the_play_cdn(): void
    {
        $html = $this->actingAs($this->admin())->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('cdn.tailwindcss.com', $html);
        $this->assertStringContainsString('css/tailwind-utilities.css', $html);
        $this->assertFileExists(public_path('css/tailwind-utilities.css'));
    }

    public function test_workflow_index_does_not_embed_the_finance_tab_per_order(): void
    {
        $this->actingAs($this->admin());
        $er = Employer::factory()->create();
        $wt = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'is_system' => true, 'order' => 1]);
        $order = ProductionOrder::create(['employer_id' => $er->id, 'work_type_id' => $wt->id, 'status' => 'active']);

        $html = $this->get(route('workflow.index', ['tab' => 'notify_in']))->assertOk()->getContent();

        $this->assertStringContainsString('id="financeModal-' . $order->id . '"', $html);
        $this->assertStringContainsString('id="finance-modal-body-' . $order->id . '"', $html);
        $this->assertStringContainsString('openFinanceModal(' . $order->id . ')', $html);
        $this->assertStringContainsString('window.openFinanceModal', $html);
        $this->assertStringNotContainsString('financialManager(', $html);
    }

    public function test_preload_last_edited_fills_every_row_in_fixed_queries(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $employers = Employer::factory()->count(3)->create(); // each logs a "create"
        $employers[1]->update(['employerNameTh' => 'แก้ไขแล้ว']);
        $latest = ActivityLog::where('subject_id', $employers[1]->id)->where('subject_type', $employers[1]->getMorphClass())->max('id');

        $fresh = Employer::whereIn('id', $employers->pluck('id'))->get();
        DB::enableQueryLog();
        Employer::preloadLastEdited($fresh);
        $this->assertCount(3, DB::getQueryLog()); // log ids, logs, users — not one per row
        DB::disableQueryLog();

        foreach ($fresh as $employer) {
            $this->assertTrue($employer->relationLoaded('lastEditedActivity'));
            $this->assertNotNull($employer->lastEditedActivity);
        }
        $this->assertSame($latest, $fresh->firstWhere('id', $employers[1]->id)->lastEditedActivity->id);
    }

    public function test_notification_cards_load_open_work_in_one_query(): void
    {
        $er = Employer::factory()->create();
        $wt = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'แจ้งเข้า', 'is_system' => true, 'order' => 1]);
        $order = ProductionOrder::create(['employer_id' => $er->id, 'work_type_id' => $wt->id, 'status' => 'active']);
        foreach (range(1, 4) as $i) {
            $emp = Employee::factory()->create(['employer_id' => $er->id]);
            ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp->id, 'status' => 'pending']);
            Notification::create(['employee_id' => $emp->id, 'type' => 'visa_expiry', 'message' => 'Visa expiring',
                'due_date' => now()->addDays(20)->toDateString(), 'status' => 'pending', 'days_remaining' => 20]);
        }

        $itemQueries = 0;
        DB::listen(function ($q) use (&$itemQueries) {
            if (str_starts_with($q->sql, 'select * from "production_items"')) {
                $itemQueries++;
            }
        });
        $html = $this->actingAs($this->admin())->get(route('notifications.index', ['active_tab' => 'visa_expiry']))->assertOk()->getContent();

        $this->assertLessThanOrEqual(1, $itemQueries);
        $this->assertStringContainsString('แจ้งเข้า', $html); // the open-work badge still shows
    }
}

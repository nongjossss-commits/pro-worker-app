<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\FinancialTransaction;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-07: the payment reminder's employee list is its own page after the
 * reminder (photo, IDs, price — like the invoice), not a table in the middle
 * of it, so 100 people don't push the total and signatures away.
 */
class ReminderEmployeeListTest extends TestCase
{
    use RefreshDatabase;

    private ProductionOrder $order;
    private FinancialTransaction $bill;

    private function bill(string $mode): void
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);

        $employer = Employer::factory()->create();
        $this->order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => WorkType::create(['name' => 'Job', 'slug' => 'job-' . uniqid()])->id,
            'status' => 'active',
        ]);
        $items = [];
        foreach (range(1, 2) as $i) {
            $items[] = ProductionItem::create([
                'production_order_id' => $this->order->id,
                'employee_id' => Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => "PERSON {$i}", 'employeePassport' => "PP{$i}"])->id,
                'status' => 'pending',
            ]);
        }
        $ids = collect($items)->pluck('id')->all();
        $group = $this->order->financialGroups()->create(['name' => 'G', 'financial_data' => [
            'pricing_mode' => 'per_head',
            'pricing_tiers' => [['price' => 500, 'count' => 2, 'item_ids' => $ids]],
        ]]);
        $this->bill = $group->transactions()->create([
            'production_order_id' => $this->order->id, 'amount' => 1000, 'paid_amount' => 100, 'type' => 'installment',
            'status' => 'partial', 'payment_collection_mode' => $mode,
        ]);
        $this->bill->items()->attach($ids);
        if ($mode === 'per_employee') {
            $this->bill->itemPayments()->create(['production_item_id' => $ids[0], 'amount' => 100, 'paid_at' => now()]);
        }
    }

    private function reminder(string $extra = 'include_employee_list=1'): string
    {
        return $this->get("/production/{$this->order->id}/documents/reminder?transaction_ids={$this->bill->id}&{$extra}")->assertOk()->getContent();
    }

    public function test_the_list_is_a_separate_page_after_the_reminder(): void
    {
        $this->bill('per_employee');
        $html = $this->reminder();

        $list = strpos($html, '<div class="page list-page');
        $this->assertNotFalse($list);
        $this->assertTrue(strpos($html, 'class="outstanding-box"') < $list, 'total comes before the list');
        $this->assertTrue(strpos($html, 'ผู้ออกเอกสาร') < $list, 'signatures come before the list');
        $this->assertStringContainsString('Employee list attached', substr($html, 0, $list));
        $this->assertStringNotContainsString('PERSON 1', substr($html, 0, $list));

        $page = substr($html, $list);
        $this->assertStringContainsString('PERSON 1', $page);
        $this->assertStringContainsString('PP2', $page);
        $this->assertStringContainsString('รูปถ่าย', $page);
        $this->assertStringContainsString('คงเหลือ', $page);
        $this->assertStringContainsString('400.00', $page, 'what PERSON 1 still owes');
        $this->assertStringContainsString('900.00', $page, 'total still owed');
        $this->assertStringContainsString('page-break-before: always', $html);

        // the photo, like the invoice's list
        Employee::where('employeeNameEn', 'PERSON 2')->update(['employeePhoto' => 'employee_files/x/p2.jpg']);
        $this->assertStringContainsString('storage/employee_files/x/p2.jpg', $this->reminder());
    }

    public function test_lump_sum_bill_has_no_paid_columns(): void
    {
        $this->bill('lump_sum');
        $page = substr($this->reminder(), 0);
        $this->assertStringContainsString('รูปถ่าย', $page);
        $this->assertStringNotContainsString('>คงเหลือ<', $page);
    }

    public function test_without_the_option_there_is_no_list_and_list_only_is_just_the_list(): void
    {
        $this->bill('per_employee');

        $plain = $this->reminder('x=1');
        $this->assertStringNotContainsString('<div class="page list-page', $plain);
        $this->assertStringNotContainsString('Employee list attached', $plain);
        $this->assertStringContainsString('class="outstanding-box"', $plain);

        $only = $this->reminder('list_only=1');
        $this->assertStringContainsString('list-only', $only);
        $this->assertStringNotContainsString('<div class="outstanding-box"', $only);
    }
}

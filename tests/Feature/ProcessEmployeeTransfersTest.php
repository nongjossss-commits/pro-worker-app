<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * app:process-employee-transfers — แจ้งเข้า / เปลี่ยนนายจ้าง moves the employee
 * 24h after the job is completed; jobs older than 7 days are left alone.
 */
class ProcessEmployeeTransfersTest extends TestCase
{
    use RefreshDatabase;

    private function transferJob(Employee $employee, Employer $to, $completedAt): ProductionItem
    {
        $workType = WorkType::where('slug', 'notify_in')->first()
            ?? WorkType::forceCreate(['name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', 'slug' => 'notify_in']);
        $order = ProductionOrder::create(['employer_id' => $to->id, 'status' => 'active', 'created_by' => User::factory()->create()->id]);
        $order->forceFill(['work_type_id' => $workType->id])->save();
        $item = ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $employee->id, 'status' => 'pending']);
        $item->forceFill(['status' => 'completed', 'completed_at' => $completedAt, 'is_transfer_processed' => false])->save();
        return $item;
    }

    public function test_recent_completed_transfer_moves_the_employee(): void
    {
        $from = Employer::factory()->create();
        $to = Employer::factory()->create();
        $employee = Employee::factory()->create(['employer_id' => $from->id]);
        $item = $this->transferJob($employee, $to, now()->subDays(2));

        $this->artisan('app:process-employee-transfers')->assertSuccessful();

        $this->assertSame($to->id, $employee->fresh()->employer_id);
        $this->assertTrue((bool) $item->fresh()->is_transfer_processed);
        $this->assertDatabaseHas('activity_logs', ['action' => 'transfer', 'subject_id' => $employee->id]);
    }

    public function test_jobs_older_than_seven_days_and_newer_than_24h_are_not_touched(): void
    {
        $from = Employer::factory()->create();
        $to = Employer::factory()->create();
        $old = Employee::factory()->create(['employer_id' => $from->id]);
        $fresh = Employee::factory()->create(['employer_id' => $from->id]);
        $oldItem = $this->transferJob($old, $to, now()->subDays(30));
        $this->transferJob($fresh, $to, now()->subHours(3));

        $this->artisan('app:process-employee-transfers')->assertSuccessful();

        $this->assertSame($from->id, $old->fresh()->employer_id);
        $this->assertFalse((bool) $oldItem->fresh()->is_transfer_processed);
        $this->assertSame($from->id, $fresh->fresh()->employer_id);
    }

    public function test_transfer_command_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('app:process-employee-transfers')->assertSuccessful();
    }
}

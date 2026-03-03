<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionItem;
use App\Models\WorkType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessEmployeeTransfers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-employee-transfers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes employee transfers (Notify In / Change Employer) that have passed the 24-hour completion mark.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting employee transfer process...');

        // Find the specific work type for "แจ้งเข้า / เปลี่ยนนายจ้าง"
        // Adjust the matching string or use slug if available
        $workType = WorkType::where('name', 'like', '%แจ้งเข้า / เปลี่ยนนายจ้าง%')
            ->orWhere('slug', 'notify_in')
            ->first();

        if (!$workType) {
            $this->error('Work Type "แจ้งเข้า / เปลี่ยนนายจ้าง" not found.');
            return;
        }

        $thresholdDate = Carbon::now()->subHours(24);

        // Find completed items older than 24 hours that haven't been processed yet
        $items = ProductionItem::whereHas('order', function ($query) use ($workType) {
                $query->where('work_type_id', $workType->id);
            })
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $thresholdDate)
            ->where('is_transfer_processed', false)
            ->with(['order', 'employee'])
            ->get();

        if ($items->isEmpty()) {
            $this->info('No pending transfers found.');
            return;
        }

        $transferredCount = 0;

        foreach ($items as $item) {
            $employee = $item->employee;
            $order = $item->order;

            if (!$employee || !$order) {
                // If there's an issue with the relationship, still mark as processed so it isn't stuck forever.
                $item->update(['is_transfer_processed' => true]);
                continue;
            }

            // Proceed with transfer (different employer or still marked as terminated)
            if ($employee->employer_id !== $order->employer_id || $employee->terminated_at !== null) {
                DB::transaction(function () use ($employee, $order, $item) {
                    $employee->update([
                        'employer_id' => $order->employer_id,
                        'status' => 'active',
                        'terminated_at' => null,
                        'termination_reason' => null,
                        'termination_date' => null
                    ]);

                    $item->update(['is_transfer_processed' => true]);
                });

                $this->info("Transferred Employee ID {$employee->id} to Employer ID {$order->employer_id}.");
                Log::info("Schedule Job ProcessEmployeeTransfers: Transferred Employee ID {$employee->id} to Employer ID {$order->employer_id} from Production Item {$item->id}");
                $transferredCount++;
            } else {
                // Even if no change was needed, we still mark it as processed so it doesn't get picked up again.
                $item->update(['is_transfer_processed' => true]);
            }
        }

        $this->info("Successfully transferred {$transferredCount} employees.");
    }
}

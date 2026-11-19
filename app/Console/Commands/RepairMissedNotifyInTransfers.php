<?php

namespace App\Console\Commands;

use App\Helpers\ActivityLogHelper;
use App\Models\Employee;
use App\Models\ProductionItem;
use App\Models\WorkType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off repair (2026-11-19): employees whose "แจ้งเข้า / เปลี่ยนนายจ้าง" job
 * was FINISHED but who were never moved to that card's employer — they still
 * sit at the old employer, notified out, so they are missing from the Employees
 * menu and every menu that lists active employees. Cause: the 24-hour mover
 * (app:process-employee-transfers) was only scheduled from 27/09/2026 and only
 * looks back 7 days, so jobs finished before that were never processed.
 *
 * Only clear-cut cases are touched — all of these must hold:
 *   - the notify_in job is completed (card not cancelled) and not yet processed
 *   - the employee is STILL notified out (terminated_at set) — i.e. nobody has
 *     moved / re-registered them anywhere since
 *   - they were notified out on or before the day that job was finished
 *   - it is their most recently finished notify_in job
 * The fix is the same as the 24-hour mover: employer = that card's employer,
 * notify-out cleared, a Registration/Renewal status kept, logged.
 *
 * Also (2026-11-19): employees notified out while an OPEN change-employer job
 * at another employer exists (left from before the rule that now moves them
 * at once) - moved to that job's employer; the job itself is left as it is.
 *
 * Shows the list only; nothing changes unless run with --apply.
 */
class RepairMissedNotifyInTransfers extends Command
{
    protected $signature = 'app:repair-missed-notify-in-transfers {--apply : Actually move the employees (otherwise only lists them)}';

    protected $description = 'List (or with --apply, fix) employees whose finished change-employer job never moved them to the new employer';

    public function handle(): int
    {
        $notifyIn = WorkType::where('slug', 'notify_in')->first();
        if (! $notifyIn) {
            $this->error('Work type notify_in not found.');
            return self::FAILURE;
        }

        $items = ProductionItem::query()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('is_transfer_processed', false)
            ->whereHas('order', fn ($o) => $o->withoutGlobalScope('employerTenancy')->where('work_type_id', $notifyIn->id)->where('status', '!=', 'cancelled'))
            ->with(['order' => fn ($o) => $o->withoutGlobalScope('employerTenancy')->with('employer'), 'employee' => fn ($e) => $e->withoutGlobalScope('employerTenancy')->with('employer')])
            ->orderByDesc('completed_at')
            ->get();

        $cases = collect();
        foreach ($items->groupBy('employee_id') as $employeeId => $rows) {
            $item = $rows->first(); // most recently finished notify_in job
            $emp = $item->employee;
            $order = $item->order;
            if (! $emp || ! $order || ! $emp->terminated_at) {
                continue;
            }
            if ((int) $emp->employer_id === (int) $order->employer_id) {
                continue;
            }
            if ($emp->terminated_at->startOfDay()->gt($item->completed_at->copy()->startOfDay())) {
                continue; // notified out AFTER the job finished — a later, separate event
            }
            $cases->push(compact('item', 'emp', 'order'));
        }

        // Also: notified out while an OPEN (not yet finished) change-employer job at
        // another employer exists — the rule since 2026-11-19 moves them right away
        // (EmployeeObserver / WorkflowController::rejoinNotifiedOutEmployee()); this
        // catches the ones left from before. Their latest open job decides.
        $open = ProductionItem::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('order', fn ($o) => $o->withoutGlobalScope('employerTenancy')->where('work_type_id', $notifyIn->id)->where('status', '!=', 'cancelled'))
            ->whereHas('employee', fn ($e) => $e->withoutGlobalScope('employerTenancy')->whereNotNull('terminated_at'))
            ->with(['order' => fn ($o) => $o->withoutGlobalScope('employerTenancy')->with('employer'), 'employee' => fn ($e) => $e->withoutGlobalScope('employerTenancy')->with('employer')])
            ->orderByDesc('id')
            ->get();
        foreach ($open->groupBy('employee_id') as $employeeId => $rows) {
            if ($cases->contains(fn ($c) => (int) $c['emp']->id === (int) $employeeId)) {
                continue;
            }
            $item = $rows->first();
            $emp = $item->employee;
            $order = $item->order;
            if (! $emp || ! $order || ! $emp->terminated_at || (int) $emp->employer_id === (int) $order->employer_id) {
                continue;
            }
            $cases->push(compact('item', 'emp', 'order') + ['open' => true]);
        }

        if ($cases->isEmpty()) {
            $this->info('Nothing to repair.');
            return self::SUCCESS;
        }

        $this->table(['Employee', 'Now at (notified out)', 'Should be at', 'Job finished'], $cases->map(fn ($c) => [
            $c['emp']->id . ' ' . ($c['emp']->getRawOriginal('employeeNameEn') ?: $c['emp']->getRawOriginal('employeeNameTh')),
            (optional($c['emp']->employer)->getRawOriginal('employerNameTh') ?? '-') . ' (' . $c['emp']->terminated_at->format('d/m/Y') . ')',
            optional($c['order']->employer)->getRawOriginal('employerNameTh') ?? '-',
            ! empty($c['open']) ? 'still in progress' : $c['item']->completed_at->format('d/m/Y'),
        ])->all());

        if (! $this->option('apply')) {
            $this->warn($cases->count() . ' employee(s) listed. Nothing changed — run again with --apply to move them.');
            return self::SUCCESS;
        }

        foreach ($cases as $c) {
            DB::transaction(function () use ($c) {
                $emp = $c['emp'];
                $order = $c['order'];
                $oldId = $emp->employer_id;
                $oldName = optional($emp->employer)->getRawOriginal('employerNameTh') ?? 'N/A';
                $newName = optional($order->employer)->getRawOriginal('employerNameTh') ?? 'N/A';

                $emp->update([
                    'employer_id' => $order->employer_id,
                    'terminated_at' => null,
                    'termination_reason' => null,
                    'status' => in_array($emp->status, [null, '', 'resigned'], true) ? 'active' : $emp->status,
                ]);
                if (empty($c['open'])) {
                    $c['item']->update(['is_transfer_processed' => true]);
                }

                ActivityLogHelper::logAction('transfer', 'ย้ายลูกจ้าง ' . $emp->getRawOriginal('employeeNameEn') . ' จาก ' . $oldName . ' ไป ' . $newName . (empty($c['open']) ? ' (ซ่อมข้อมูล: งานแจ้งเข้า/เปลี่ยนนายจ้างเสร็จแล้วแต่ยังไม่ได้ย้าย)' : ' (ซ่อมข้อมูล: ถูกแจ้งออกขณะมีงานแจ้งเข้า/เปลี่ยนนายจ้างค้างอยู่)'), Employee::class, $emp->id, [
                    'old_employer_id' => $oldId,
                    'old_employer_name' => $oldName,
                    'new_employer_id' => $order->employer_id,
                    'new_employer_name' => $newName,
                    'production_item_id' => $c['item']->id,
                ]);
            });
        }
        $this->info('Moved ' . $cases->count() . ' employee(s).');

        return self::SUCCESS;
    }
}

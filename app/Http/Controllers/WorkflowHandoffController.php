<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkflowHandoffException;
use App\Helpers\ActivityLogHelper;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\WorkflowHandoff;
use App\Models\WorkType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Workflow "ส่งข้อมูลไปแท็บอื่น" (send to another tab).
 *
 * 1. send     — ticked employees are sent to another Workflow tab (any tab,
 *               including ones a Super Admin adds later). Nothing changes in
 *               the source card; a pending WorkflowHandoff row is created.
 * 2. index    — the target tab shows "employees sent here" to everyone who
 *               can manage Workflow there.
 * 3. receive  — picked people are added to an employer card of that tab:
 *               tabs with one card per employer use that card (created if
 *               missing); multi-card tabs ask which card, or a new one.
 *               Same rules as adding employees by hand (WorkflowController::store):
 *               one open job per employee per tab, notify_in rejoin rule.
 * 4. dismiss  — not needed after all.
 */
class WorkflowHandoffController extends Controller
{
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->can('manage-own-workflow'), 403);
    }

    /**
     * Open (not completed / cancelled) job of this employee in this tab —
     * the same one-per-tab rule as WorkflowController::store(). Ignores the
     * employer tenancy scope so the check is complete for every account.
     */
    private function openItemInTab(int $employeeId, int $workTypeId): ?ProductionItem
    {
        return ProductionItem::where('employee_id', $employeeId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('order', fn ($q) => $q->withoutGlobalScope('employerTenancy')
                ->where('work_type_id', $workTypeId)
                ->where('status', '!=', 'cancelled'))
            ->with(['order' => fn ($q) => $q->withoutGlobalScope('employerTenancy')])
            ->first();
    }

    private function employeeName(Employee $e): string
    {
        return trim(($e->employeeNameEn ?: $e->employeeNameTh) ?: ('#' . $e->id));
    }

    /**
     * Employer of the card the employee was ticked on. Accepted only when it
     * really is theirs (current employer, or the employer of one of their
     * Workflow cards) — otherwise their current employer.
     */
    private function resolveEmployerId(Employee $employee, $claimed): ?int
    {
        $claimed = (int) $claimed;
        if ($claimed && $claimed !== (int) $employee->employer_id) {
            $onTheirCard = ProductionItem::where('employee_id', $employee->id)
                ->whereHas('order', fn ($q) => $q->where('employer_id', $claimed))
                ->exists();
            if ($onTheirCard && Employer::whereKey($claimed)->exists()) {
                return $claimed;
            }
            $claimed = 0;
        }
        if ($claimed) {
            return $claimed;
        }

        return $employee->employer_id && Employer::whereKey($employee->employer_id)->exists()
            ? (int) $employee->employer_id
            : null;
    }

    public function send(Request $request): JsonResponse
    {
        $this->authorizeManage();
        $data = $request->validate([
            'target_work_type_id' => 'required|integer',
            'source_work_type_id' => 'nullable|integer',
            'employees' => 'required|array|min:1|max:500',
            'employees.*.id' => 'required|integer',
            'employees.*.employer_id' => 'nullable|integer',
        ]);

        $target = WorkType::find($data['target_work_type_id']);
        if (!$target) {
            return response()->json(['success' => false, 'message' => __('This tab no longer exists.')], 422);
        }
        $sourceId = !empty($data['source_work_type_id']) && WorkType::withTrashed()->whereKey($data['source_work_type_id'])->exists()
            ? (int) $data['source_work_type_id'] : null;

        $claims = collect($data['employees'])->keyBy('id');
        $employees = Employee::whereIn('id', $claims->keys())->get()->keyBy('id'); // tenancy scope applies

        $sent = 0;
        $skipped = [];
        DB::transaction(function () use ($claims, $employees, $target, $sourceId, &$sent, &$skipped) {
            foreach ($claims as $id => $claim) {
                $employee = $employees->get($id);
                if (!$employee) {
                    $skipped[] = ['name' => '#' . $id, 'reason' => __('Employee not found')];
                    continue;
                }
                $name = $this->employeeName($employee);

                if ($open = $this->openItemInTab($employee->id, $target->id)) {
                    $skipped[] = ['name' => $name, 'reason' => __('Already has an open job in this tab')];
                    continue;
                }
                $alreadyWaiting = WorkflowHandoff::where('employee_id', $employee->id)
                    ->where('target_work_type_id', $target->id)
                    ->where('status', WorkflowHandoff::PENDING)
                    ->lockForUpdate()
                    ->exists();
                if ($alreadyWaiting) {
                    $skipped[] = ['name' => $name, 'reason' => __('Already sent and waiting in this tab')];
                    continue;
                }
                $employerId = $this->resolveEmployerId($employee, $claim['employer_id'] ?? null);
                if (!$employerId) {
                    $skipped[] = ['name' => $name, 'reason' => __('No employer')];
                    continue;
                }

                WorkflowHandoff::create([
                    'target_work_type_id' => $target->id,
                    'employee_id' => $employee->id,
                    'employer_id' => $employerId,
                    'source_work_type_id' => $sourceId,
                    'status' => WorkflowHandoff::PENDING,
                    'sent_by' => auth()->id(),
                ]);
                $sent++;
            }
        });

        if ($sent) {
            ActivityLogHelper::logAction('workflow_handoff_send', 'ส่งข้อมูลลูกจ้าง ' . $sent . ' คน ไปแท็บ ' . $target->name . ' (Workflow)', WorkType::class, $target->id, [
                'sent' => $sent, 'skipped' => count($skipped),
            ]);
        }

        return response()->json(['success' => true, 'sent' => $sent, 'skipped' => $skipped, 'tab' => $target->name]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage();
        $workType = WorkType::findOrFail((int) $request->query('work_type_id'));

        $handoffs = WorkflowHandoff::visiblePending()
            ->where('target_work_type_id', $workType->id)
            ->with(['employee', 'employer', 'sourceWorkType', 'sender'])
            ->orderBy('created_at')
            ->get();

        $employerIds = $handoffs->pluck('employer_id')->filter()->unique()->values();
        $cards = ProductionOrder::where('work_type_id', $workType->id)
            ->where('status', 'active')
            ->whereIn('employer_id', $employerIds)
            ->withCount(['items' => fn ($q) => $q->where('status', '!=', 'cancelled')])
            // one-card tabs: the first card is the one cardFor() uses
            ->when($workType->allow_multiple_orders, fn ($q) => $q->orderByDesc('created_at'), fn ($q) => $q->orderBy('id'))
            ->get()
            ->groupBy('employer_id');

        $employers = Employer::whereIn('id', $employerIds)->get()->keyBy('id');

        return response()->json([
            'success' => true,
            'tab' => ['id' => $workType->id, 'name' => $workType->name, 'multiple' => (bool) $workType->allow_multiple_orders],
            'handoffs' => $handoffs->map(function (WorkflowHandoff $h) use ($workType) {
                $e = $h->employee;
                $open = $this->openItemInTab($e->id, $workType->id);

                return [
                    'id' => $h->id,
                    'employee_id' => $e->id,
                    'name' => $this->employeeName($e),
                    'title' => $e->employeeTitleEn ?: $e->employeeTitleTh,
                    'photo' => $e->employeePhoto,
                    'passport' => $e->employeePassport,
                    'nationality' => $e->employeeNationality,
                    'employer_id' => $h->employer_id,
                    'employer_name' => optional($h->employer)->employerNameTh ?: optional($h->employer)->employerNameEn,
                    'from_tab' => optional($h->sourceWorkType)->name,
                    'sent_by' => optional($h->sender)->name,
                    'sent_at' => $h->created_at?->format('d/m/Y H:i'),
                    // added to this tab some other way meanwhile → cannot receive, can dismiss
                    'blocked' => $open ? __('Already has an open job in this tab') : null,
                ];
            })->values(),
            'employers' => $employerIds->map(fn ($id) => [
                'id' => $id,
                'name' => optional($employers->get($id))->employerNameTh ?: optional($employers->get($id))->employerNameEn ?: ('#' . $id),
                'cards' => ($cards->get($id) ?? collect())->map(fn ($o) => [
                    'id' => $o->id,
                    'name' => $o->project_name ?: ($workType->name . ' #' . $o->id),
                    'people' => $o->items_count,
                    'created' => $o->created_at?->format('d/m/Y'),
                ])->values(),
            ])->values(),
        ]);
    }

    public function receive(Request $request): JsonResponse
    {
        $this->authorizeManage();
        $data = $request->validate([
            'work_type_id' => 'required|integer',
            'handoff_ids' => 'required|array|min:1',
            'handoff_ids.*' => 'integer',
            // multi-card tabs: employer_id => order id or "new"
            'cards' => 'nullable|array',
            'cards.*' => 'nullable|string|max:20',
        ]);
        $workType = WorkType::find($data['work_type_id']);
        if (!$workType) {
            return response()->json(['success' => false, 'message' => __('This tab no longer exists.')], 422);
        }

        try {
            $result = DB::transaction(function () use ($data, $workType) {
                $handoffs = WorkflowHandoff::visiblePending()
                    ->where('target_work_type_id', $workType->id)
                    ->whereIn('id', $data['handoff_ids'])
                    ->lockForUpdate()
                    ->with('employee')
                    ->get();
                if ($handoffs->isEmpty()) {
                    throw new WorkflowHandoffException(__('These employees were already handled. Please reload.'));
                }

                $added = [];
                $skipped = [];
                foreach ($handoffs->groupBy('employer_id') as $employerId => $group) {
                    $order = $this->cardFor($workType, (int) $employerId, $data['cards'][$employerId] ?? null);

                    foreach ($group as $h) {
                        $e = $h->employee;
                        if ($this->openItemInTab($e->id, $workType->id)) {
                            $skipped[] = ['name' => $this->employeeName($e), 'reason' => __('Already has an open job in this tab')];
                            continue; // stays waiting — can be dismissed
                        }
                        $item = ProductionItem::create([
                            'production_order_id' => $order->id,
                            'employee_id' => $e->id,
                            'group_name' => null,
                            'status' => 'pending',
                        ]);
                        if ($workType->slug === 'notify_in') {
                            WorkflowController::rejoinNotifiedOutEmployee($e->id, $order);
                        }
                        $h->update([
                            'status' => WorkflowHandoff::RECEIVED,
                            'handled_by' => auth()->id(),
                            'handled_at' => now(),
                            'production_item_id' => $item->id,
                        ]);
                        $added[] = $this->employeeName($e);
                    }
                }

                return compact('added', 'skipped');
            });
        } catch (WorkflowHandoffException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if ($result['added']) {
            ActivityLogHelper::logAction('workflow_handoff_receive', 'รับลูกจ้างที่ถูกส่งมา ' . count($result['added']) . ' คน เข้าการ์ดในแท็บ ' . $workType->name . ' (Workflow)', WorkType::class, $workType->id, [
                'employees' => $result['added'],
            ]);
        }

        return response()->json(['success' => true, 'added' => count($result['added']), 'skipped' => $result['skipped']]);
    }

    /**
     * The card to add this employer's people to. One-card-per-employer tabs:
     * that card (created if missing) — same as WorkflowController::store().
     * Multi-card tabs: the chosen open card of this employer, or a new one.
     */
    private function cardFor(WorkType $workType, int $employerId, ?string $choice): ProductionOrder
    {
        $employer = Employer::find($employerId);
        if (!$employer) {
            throw new WorkflowHandoffException(__('Employer not found'));
        }

        if (!$workType->allow_multiple_orders) {
            return ProductionOrder::where(['employer_id' => $employerId, 'work_type_id' => $workType->id, 'status' => 'active'])->orderBy('id')->first()
                ?? ProductionOrder::create([
                    'employer_id' => $employerId, 'work_type_id' => $workType->id, 'status' => 'active',
                    'type' => 'employer', 'project_name' => $workType->name . ' - ' . $employer->employerNameTh, 'created_by' => auth()->id(),
                ]);
        }

        if ($choice === null || $choice === '') {
            throw new WorkflowHandoffException(__('Choose a card for :name', ['name' => $employer->employerNameTh ?: $employer->employerNameEn]));
        }
        if ($choice === 'new') {
            return ProductionOrder::create([
                'employer_id' => $employerId,
                'work_type_id' => $workType->id,
                'type' => 'employer',
                'project_name' => $workType->name . ' - ' . now()->format('d/m/Y'),
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
        }

        $order = ProductionOrder::whereKey((int) $choice)
            ->where('employer_id', $employerId)
            ->where('work_type_id', $workType->id)
            ->where('status', 'active')
            ->first();
        if (!$order) {
            throw new WorkflowHandoffException(__('The chosen card is no longer open. Please reload.'));
        }

        return $order;
    }

    public function dismiss(Request $request): JsonResponse
    {
        $this->authorizeManage();
        $data = $request->validate([
            'handoff_ids' => 'required|array|min:1',
            'handoff_ids.*' => 'integer',
        ]);

        $count = WorkflowHandoff::visiblePending()->whereIn('id', $data['handoff_ids'])->update([
            'status' => WorkflowHandoff::DISMISSED,
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ]);

        return response()->json(['success' => true, 'dismissed' => $count]);
    }
}

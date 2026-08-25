<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobCheckSession;
use App\Models\JobCheckSessionSnapshot;
use App\Models\ProductionItem;
use App\Services\AccountingPeriodService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "โหมดเช็คงาน" (Job Check Mode) — lets an operator snapshot the current
 * step/status of every employee & item across Pre-Production, Workflow,
 * มติลงทะเบียน, and มติต่ออายุ before starting a check pass, then diff the
 * final state against that snapshot when done. Comparing only final vs
 * initial state (not an action log) means a tick-then-undo nets out to "no
 * movement" automatically — no special-case logic needed for that.
 */
class JobCheckSessionController extends Controller
{
    protected const MENU_LABELS = [
        'pre_production' => 'Pre-Production',
        'workflow' => 'Workflow',
        'registration_resolution' => 'มติลงทะเบียน',
        'renewal_resolution' => 'มติต่ออายุ',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function status(Request $request)
    {
        $session = JobCheckSession::active()->where('user_id', $request->user()->id)->first();

        return response()->json([
            'active' => (bool) $session,
            'session_id' => $session?->id,
            'started_at' => $session?->started_at?->toIso8601String(),
            'snapshot_count' => $session ? $session->snapshots()->count() : 0,
        ]);
    }

    public function start(Request $request)
    {
        if (!$request->user()->can('edit-employees')) {
            abort(403);
        }

        $session = JobCheckSession::active()->where('user_id', $request->user()->id)->first();

        if (!$session) {
            $session = JobCheckSession::create([
                'user_id' => $request->user()->id,
                'status' => 'active',
                'started_at' => now(),
            ]);

            $this->takeSnapshot($session);
        }

        return redirect()->route('workflow.index')
            ->with('success', __('Job Check Mode started. You are now confined to Pre-Production, Workflow, Registration Resolution, and Renewal Resolution until you finish.'));
    }

    public function cancel(Request $request)
    {
        $session = JobCheckSession::active()->where('user_id', $request->user()->id)->firstOrFail();
        $session->snapshots()->delete();
        $session->update(['status' => 'cancelled', 'ended_at' => now()]);

        return redirect()->route('workflow.index')->with('success', __('Job Check Mode cancelled.'));
    }

    public function finish(Request $request)
    {
        $session = JobCheckSession::active()->where('user_id', $request->user()->id)->firstOrFail();

        $now = now();
        [$moved, $notMoved] = $this->diff($session, $now);

        Storage::disk('local')->makeDirectory("job-check/{$session->id}");
        $this->writeWorkbook($moved, Storage::disk('local')->path("job-check/{$session->id}/moved.xlsx"));
        $this->writeWorkbook($notMoved, Storage::disk('local')->path("job-check/{$session->id}/not_moved.xlsx"));

        $businessDate = AccountingPeriodService::businessDate($now);
        $sequence = (int) (JobCheckSession::where('business_date', $businessDate->toDateString())
            ->where('status', 'completed')
            ->max('sequence_in_day') ?? 0) + 1;

        $session->update([
            'status' => 'completed',
            'ended_at' => $now,
            'business_date' => $businessDate,
            'sequence_in_day' => $sequence,
        ]);

        return response()->json([
            'success' => true,
            'moved_count' => array_sum(array_map('count', $moved)),
            'not_moved_count' => array_sum(array_map('count', $notMoved)),
            'download_moved' => route('job-check.download', ['session' => $session->id, 'type' => 'moved']),
            'download_not_moved' => route('job-check.download', ['session' => $session->id, 'type' => 'not_moved']),
        ]);
    }

    public function download(Request $request, JobCheckSession $session, string $type)
    {
        if (!$request->user()->can('edit-employees')) {
            abort(403);
        }

        if (!in_array($type, ['moved', 'not_moved'], true)) {
            abort(404);
        }

        $relativePath = "job-check/{$session->id}/{$type}.xlsx";
        if (!Storage::disk('local')->exists($relativePath)) {
            abort(404);
        }

        $label = $type === 'moved' ? 'มีการเคลื่อนไหว' : 'ไม่มีการเคลื่อนไหว';
        $dateLabel = ($session->business_date ?? $session->created_at)->format('Y-m-d');
        $sequenceLabel = 'ครั้งที่' . ($session->sequence_in_day ?? 1);
        $filename = "{$label}-เช็คงาน-{$dateLabel}-{$sequenceLabel}.xlsx";

        return response()->download(Storage::disk('local')->path($relativePath), $filename);
    }

    /**
     * Last 7 business days of completed sessions, grouped by day, each
     * carrying its 1-based "ครั้งที่ N" order within that day.
     */
    public function history(Request $request)
    {
        if (!$request->user()->can('edit-employees')) {
            abort(403);
        }

        $cutoff = AccountingPeriodService::businessDate(now())->subDays(6);

        $sessions = JobCheckSession::where('status', 'completed')
            ->where('business_date', '>=', $cutoff->toDateString())
            ->with('user')
            ->orderByDesc('business_date')
            ->orderByDesc('sequence_in_day')
            ->get();

        $grouped = $sessions->groupBy(fn ($s) => $s->business_date->toDateString())
            ->map(fn ($group) => $group->map(fn ($s) => [
                'id' => $s->id,
                'sequence' => $s->sequence_in_day,
                'ended_at' => $s->ended_at?->format('H:i'),
                'user_name' => $s->user?->name,
                'download_moved' => route('job-check.download', ['session' => $s->id, 'type' => 'moved']),
                'download_not_moved' => route('job-check.download', ['session' => $s->id, 'type' => 'not_moved']),
            ])->values());

        return response()->json(['history' => $grouped]);
    }

    /**
     * "รายงานสรุปเชิงลึก" — takes the already-exported moved.xlsx / not_moved.xlsx
     * (either or both) and re-groups every row by employer, so a follow-up
     * report can answer "how many of this employer's workers reached which
     * step, and how many are unchanged since last time" without re-running
     * a check session. Pure file-parsing — never touches employee records.
     */
    public function summarize(Request $request)
    {
        if (!$request->user()->can('edit-employees')) {
            abort(403);
        }

        $request->validate([
            'moved_file' => 'nullable|file|mimes:xlsx',
            'not_moved_file' => 'nullable|file|mimes:xlsx',
        ]);

        if (!$request->hasFile('moved_file') && !$request->hasFile('not_moved_file')) {
            return response()->json(['success' => false, 'message' => __('Please upload at least one exported file.')], 422);
        }

        $summary = [];

        try {
            if ($request->hasFile('moved_file')) {
                $this->ingestWorkbook($request->file('moved_file')->getRealPath(), 'moved', $summary);
            }
            if ($request->hasFile('not_moved_file')) {
                $this->ingestWorkbook($request->file('not_moved_file')->getRealPath(), 'not_moved', $summary);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => __('Could not read the uploaded file. Please make sure it is an unmodified export from this feature.')], 422);
        }

        ksort($summary, SORT_NATURAL | SORT_FLAG_CASE);

        return response()->json(['success' => true, 'summary' => $summary]);
    }

    /**
     * Reads one previously-exported workbook and tallies rows per employer
     * into $summary by reference. Relies on the fixed column layout written
     * by writeWorkbook() (A=name, B=employer, D=step before, E=step after)
     * — both are owned by this same feature, so the coupling is safe.
     */
    protected function ingestWorkbook(string $path, string $source, array &$summary): void
    {
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($path);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $rows = $sheet->toArray(null, true, true, false);
            array_shift($rows); // header row

            foreach ($rows as $row) {
                // Column layout written by writeWorkbook(): 0=No., 1=name,
                // 2=employer, 3=request no., 4=step before, 5=step after, ...
                $name = trim((string) ($row[1] ?? ''));
                $employer = trim((string) ($row[2] ?? '')) ?: '-';
                if ($name === '' && $employer === '-') {
                    continue; // fully blank row
                }

                $stepBefore = trim((string) ($row[4] ?? '')) ?: '-';
                $stepAfter = trim((string) ($row[5] ?? '')) ?: '-';

                if (!isset($summary[$employer])) {
                    $summary[$employer] = [
                        'total' => 0,
                        'moved_count' => 0,
                        'moved_by_step' => [],
                        'not_moved_count' => 0,
                        'not_moved_by_step' => [],
                    ];
                }

                $summary[$employer]['total']++;

                if ($source === 'moved') {
                    $summary[$employer]['moved_count']++;
                    $summary[$employer]['moved_by_step'][$stepAfter] = ($summary[$employer]['moved_by_step'][$stepAfter] ?? 0) + 1;
                } else {
                    $summary[$employer]['not_moved_count']++;
                    $summary[$employer]['not_moved_by_step'][$stepBefore] = ($summary[$employer]['not_moved_by_step'][$stepBefore] ?? 0) + 1;
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // Snapshot
    // ------------------------------------------------------------------

    protected function takeSnapshot(JobCheckSession $session): void
    {
        $this->snapshotEmployees($session, 'registration_resolution', 'registration_pending', 'registration');
        $this->snapshotEmployees($session, 'renewal_resolution', 'renewal_pending', 'renewal');
        $this->snapshotItems($session, 'pre_production', true);
        $this->snapshotItems($session, 'workflow', false);
    }

    protected function snapshotEmployees(JobCheckSession $session, string $menu, string $status, string $tabType): void
    {
        $rows = [];
        $now = now();

        Employee::where('status', $status)
            ->whereHas('resolutionTab', fn ($q) => $q->where('type', $tabType))
            ->with('registrationSteps')
            ->chunkById(200, function ($employees) use (&$rows, $session, $menu, $now, $tabType) {
                foreach ($employees as $employee) {
                    $rows[] = [
                        'job_check_session_id' => $session->id,
                        'menu' => $menu,
                        'subject_type' => Employee::class,
                        'subject_id' => $employee->id,
                        'employer_id' => $employee->employer_id,
                        'resolution_tab_id' => $employee->resolution_tab_id,
                        'production_order_id' => null,
                        'initial_state' => json_encode($this->employeeState($employee, $tabType)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (count($rows) >= 500) {
                        JobCheckSessionSnapshot::insert($rows);
                        $rows = [];
                    }
                }
            });

        if (!empty($rows)) {
            JobCheckSessionSnapshot::insert($rows);
        }
    }

    protected function snapshotItems(JobCheckSession $session, string $menu, bool $preProduction): void
    {
        $rows = [];
        $now = now();

        ProductionItem::whereHas('order', function ($q) use ($preProduction) {
                if ($preProduction) {
                    $q->where('status', 'pre_production');
                } else {
                    $q->where('status', '!=', 'pre_production')->where('status', '!=', 'cancelled');
                }
            })
            ->where('status', '!=', 'cancelled')
            ->with(['completedWorkTypeSteps', 'order'])
            ->chunkById(200, function ($items) use (&$rows, $session, $menu, $now) {
                foreach ($items as $item) {
                    $rows[] = [
                        'job_check_session_id' => $session->id,
                        'menu' => $menu,
                        'subject_type' => ProductionItem::class,
                        'subject_id' => $item->id,
                        'employer_id' => $item->order->employer_id ?? null,
                        'resolution_tab_id' => null,
                        'production_order_id' => $item->production_order_id,
                        'initial_state' => json_encode($this->itemState($item)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (count($rows) >= 500) {
                        JobCheckSessionSnapshot::insert($rows);
                        $rows = [];
                    }
                }
            });

        if (!empty($rows)) {
            JobCheckSessionSnapshot::insert($rows);
        }
    }

    protected function employeeState(Employee $employee, string $tabType): array
    {
        $steps = $employee->registrationSteps;
        $requestNumber = $tabType === 'renewal' ? $employee->renewal_request_number : $employee->registration_request_number;
        $remarks = $tabType === 'renewal' ? $employee->renewal_remarks : $employee->registration_remarks;

        return [
            'status' => $employee->status,
            'step_ids' => $steps->pluck('id')->sort()->values()->all(),
            'highest_step_name' => $steps->sortByDesc('order')->first()?->name,
            'request_number' => $requestNumber ?: ($employee->request_number ?: '-'),
            'remarks' => $remarks ?: '-',
        ];
    }

    protected function itemState(ProductionItem $item): array
    {
        $steps = $item->completedWorkTypeSteps;

        return [
            'status' => $item->status,
            'step_ids' => $steps->pluck('id')->sort()->values()->all(),
            'highest_step_name' => $steps->sortByDesc('order')->first()?->name,
            'request_number' => $item->request_number ?: '-',
            'remarks' => $item->remarks ?: '-',
        ];
    }

    // ------------------------------------------------------------------
    // Diff
    // ------------------------------------------------------------------

    /**
     * @return array{0: array<string, array>, 1: array<string, array>} [moved rows by menu, not-moved rows by menu]
     */
    protected function diff(JobCheckSession $session, Carbon $checkedAt): array
    {
        $moved = array_fill_keys(array_keys(self::MENU_LABELS), []);
        $notMoved = array_fill_keys(array_keys(self::MENU_LABELS), []);

        $this->diffSubjectType($session, Employee::class, $checkedAt, $moved, $notMoved);
        $this->diffSubjectType($session, ProductionItem::class, $checkedAt, $moved, $notMoved);

        return [$moved, $notMoved];
    }

    /**
     * Diff all snapshots of one subject type, bulk-loading the current
     * state per chunk (whereIn) instead of querying per row — avoids N+1
     * across what can be thousands of snapshot rows.
     */
    protected function diffSubjectType(JobCheckSession $session, string $subjectType, Carbon $checkedAt, array &$moved, array &$notMoved): void
    {
        $session->snapshots()
            ->where('subject_type', $subjectType)
            ->with('employer')
            ->chunkById(200, function ($snapshots) use ($subjectType, $checkedAt, &$moved, &$notMoved) {
                $ids = $snapshots->pluck('subject_id')->all();

                if ($subjectType === Employee::class) {
                    $subjects = Employee::withTrashed()->with('registrationSteps')->whereIn('id', $ids)->get()->keyBy('id');
                } else {
                    $subjects = ProductionItem::withTrashed()->with('completedWorkTypeSteps')->whereIn('id', $ids)->get()->keyBy('id');
                }

                foreach ($snapshots as $snapshot) {
                    $this->diffOne($snapshot, $subjects->get($snapshot->subject_id), $checkedAt, $moved, $notMoved);
                }
            });
    }

    protected function diffOne(JobCheckSessionSnapshot $snapshot, $subject, Carbon $checkedAt, array &$moved, array &$notMoved): void
    {
        $initial = $snapshot->initial_state;
        $menu = $snapshot->menu;
        $employerName = $snapshot->employer?->employerNameTh ?: ($snapshot->employer?->employerNameEn ?: '-');

        if (!$subject) {
            $moved[$menu][] = $this->row('-', $employerName, $initial['request_number'] ?? '-', $initial['highest_step_name'] ?? '-', __('Deleted'), $this->statusLabel($initial['status']), __('Deleted'), $initial['remarks'] ?? '-', $checkedAt);
            return;
        }

        if ($subject instanceof Employee) {
            $tabType = $menu === 'renewal_resolution' ? 'renewal' : 'registration';
            $current = $this->employeeState($subject, $tabType);
            $nameEn = $this->titledName($subject->employeeTitleEn, $subject->employeeNameEn);
        } else {
            $current = $this->itemState($subject);
            $nameEn = $subject->employee
                ? $this->titledName($subject->employee->employeeTitleEn, $subject->employee->employeeNameEn)
                : ($subject->new_employee_data['name_en'] ?? '-');
        }

        $stepsChanged = $initial['step_ids'] !== $current['step_ids'];
        $statusChanged = $initial['status'] !== $current['status'];

        $row = $this->row(
            $nameEn,
            $employerName,
            $current['request_number'] ?? ($initial['request_number'] ?? '-'),
            $initial['highest_step_name'] ?? '-',
            $current['highest_step_name'] ?? '-',
            $this->statusLabel($initial['status']),
            $this->statusLabel($current['status']),
            // Remarks is display-only context, never part of movement
            // detection — always the CURRENT text (not the snapshot's),
            // so notes added/edited during the session still show up here
            // even for employees with no step/status movement.
            $current['remarks'] ?? ($initial['remarks'] ?? '-'),
            $checkedAt
        );

        if ($stepsChanged || $statusChanged) {
            $moved[$menu][] = $row;
        } else {
            $notMoved[$menu][] = $row;
        }
    }

    protected function row(string $nameEn, string $employer, string $requestNumber, string $stepBefore, string $stepAfter, string $statusBefore, string $statusAfter, string $remarks, Carbon $checkedAt): array
    {
        return [
            'name_en' => $nameEn,
            'employer' => $employer,
            'request_number' => $requestNumber,
            'step_before' => $stepBefore,
            'step_after' => $stepAfter,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'remarks' => $remarks,
            'checked_at' => $checkedAt->format('d/m/Y H:i'),
        ];
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'registration_pending', 'renewal_pending', 'pending' => __('Pending'),
            'registration_completed', 'renewal_completed', 'completed' => __('Completed'),
            'registration_cancelled', 'renewal_cancelled', 'cancelled' => __('Cancelled'),
            default => $status ?? '-',
        };
    }

    protected function titledName(?string $title, ?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '-';
        }

        $title = trim((string) $title);

        return $title !== '' ? "{$title} {$name}" : $name;
    }

    // ------------------------------------------------------------------
    // Excel export
    // ------------------------------------------------------------------

    protected function writeWorkbook(array $rowsByMenu, string $absolutePath): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $first = true;
        foreach (self::MENU_LABELS as $menuKey => $menuLabel) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($menuLabel);
            if ($first) {
                $spreadsheet->setActiveSheetIndex(0);
                $first = false;
            }

            $headers = [
                __('No.'),
                __('Employee Name (EN)'),
                __('Employer'),
                __('Request No.'),
                __('Step Before'),
                __('Step After'),
                __('Status Before'),
                __('Status After'),
                __('Remarks'),
                __('Checked At'),
            ];
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);
            $sheet->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
            $sheet->getStyle('A1:J1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $rowNum = 2;
            $seq = 1;
            foreach ($rowsByMenu[$menuKey] ?? [] as $row) {
                $sheet->fromArray([
                    $seq,
                    $row['name_en'],
                    $row['employer'],
                    $row['request_number'],
                    $row['step_before'],
                    $row['step_after'],
                    $row['status_before'],
                    $row['status_after'],
                    $row['remarks'],
                    $row['checked_at'],
                ], null, "A{$rowNum}");
                $rowNum++;
                $seq++;
            }

            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);
    }
}

<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\JobCheckSession;
use App\Models\JobCheckSessionSnapshot;
use App\Models\ProductionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "โหมดเช็คงาน" (Job Check Mode) core logic — snapshotting, diffing, and
 * Excel export. Extracted out of JobCheckSessionController so the same
 * "compare final state vs the snapshot taken at start" logic can be called
 * both from the HTTP finish() action AND from the scheduled
 * app:auto-finish-stale-job-check-sessions command (which force-closes any
 * session a user forgot to finish before the next 05:00 business-day
 * cutover, so history never gets stuck).
 */
class JobCheckService
{
    protected const MENU_LABELS = [
        'pre_production' => 'Pre-Production',
        'workflow' => 'Workflow',
        'registration_resolution' => 'มติลงทะเบียน',
        'renewal_resolution' => 'มติต่ออายุ',
    ];

    // ------------------------------------------------------------------
    // Snapshot
    // ------------------------------------------------------------------

    public function takeSnapshot(JobCheckSession $session): void
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
            ->with(['completedWorkTypeSteps', 'order.workType'])
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
            // Registration/Renewal Resolution aren't split by work_type
            // (they use ResolutionTab, a different grouping) — always '-'
            // so the exported column stays uniform across every menu.
            'work_type_name' => '-',
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
            'work_type_name' => $item->order?->workType?->name ?: '-',
        ];
    }

    // ------------------------------------------------------------------
    // Complete (diff + export + mark session completed)
    // ------------------------------------------------------------------

    /**
     * Diffs current state against the session's snapshot, writes both
     * workbooks, and marks the session completed. Used identically by the
     * user-triggered "Finish" action and by the scheduled auto-finish
     * command — the business_date is always derived from when the session
     * STARTED (not from $now), so a session paused across the 05:00
     * cutover still files under the day it actually covers.
     *
     * @return array{moved_count: int, not_moved_count: int}
     */
    public function completeSession(JobCheckSession $session, Carbon $now): array
    {
        [$moved, $notMoved] = $this->diff($session, $now);

        Storage::disk('local')->makeDirectory("job-check/{$session->id}");
        $this->writeWorkbook($moved, Storage::disk('local')->path("job-check/{$session->id}/moved.xlsx"));
        $this->writeWorkbook($notMoved, Storage::disk('local')->path("job-check/{$session->id}/not_moved.xlsx"));

        $businessDate = AccountingPeriodService::businessDate($session->started_at ?? $now);
        $sequence = (int) (JobCheckSession::where('business_date', $businessDate->toDateString())
            ->where('status', 'completed')
            ->max('sequence_in_day') ?? 0) + 1;

        $session->update([
            'status' => 'completed',
            'ended_at' => $now,
            'business_date' => $businessDate,
            'sequence_in_day' => $sequence,
        ]);

        return [
            'moved_count' => array_sum(array_map('count', $moved)),
            'not_moved_count' => array_sum(array_map('count', $notMoved)),
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
                    $subjects = ProductionItem::withTrashed()->with(['completedWorkTypeSteps', 'order.workType'])->whereIn('id', $ids)->get()->keyBy('id');
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
            // Deleted subject — no employee record left to pull a photo from.
            $moved[$menu][] = $this->row('-', $employerName, $initial['work_type_name'] ?? '-', $initial['request_number'] ?? '-', $initial['highest_step_name'] ?? '-', __('Deleted'), $this->statusLabel($initial['status']), __('Deleted'), $initial['remarks'] ?? '-', $checkedAt, null);
            return;
        }

        if ($subject instanceof Employee) {
            $tabType = $menu === 'renewal_resolution' ? 'renewal' : 'registration';
            $current = $this->employeeState($subject, $tabType);
            $nameEn = $this->titledName($subject->employeeTitleEn, $subject->employeeNameEn);
            $photoPath = $subject->employeePhoto;
        } else {
            $current = $this->itemState($subject);
            $nameEn = $subject->employee
                ? $this->titledName($subject->employee->employeeTitleEn, $subject->employee->employeeNameEn)
                : ($subject->new_employee_data['name_en'] ?? '-');
            // A "New from Origin" MOU-import row has no Employee record yet
            // (new_employee_data is just a plain array) — no photo to show.
            $photoPath = $subject->employee?->employeePhoto;
        }

        $stepsChanged = $initial['step_ids'] !== $current['step_ids'];
        $statusChanged = $initial['status'] !== $current['status'];

        $row = $this->row(
            $nameEn,
            $employerName,
            // Sub-tab (work type) is display-only context like remarks —
            // always the CURRENT value, since it never legitimately
            // changes for an existing item and the current lookup is more
            // reliable than trusting the snapshot for a deleted-and-gone case.
            $current['work_type_name'] ?? ($initial['work_type_name'] ?? '-'),
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
            $checkedAt,
            $photoPath
        );

        if ($stepsChanged || $statusChanged) {
            $moved[$menu][] = $row;
        } else {
            $notMoved[$menu][] = $row;
        }
    }

    protected function row(string $nameEn, string $employer, string $workTypeName, string $requestNumber, string $stepBefore, string $stepAfter, string $statusBefore, string $statusAfter, string $remarks, Carbon $checkedAt, ?string $photoPath): array
    {
        return [
            'name_en' => $nameEn,
            'employer' => $employer,
            'work_type_name' => $workTypeName,
            'request_number' => $requestNumber,
            'step_before' => $stepBefore,
            'step_after' => $stepAfter,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'remarks' => $remarks,
            'checked_at' => $checkedAt->format('d/m/Y H:i'),
            // Storage disk 'public' relative path — resolved to an absolute
            // path and embedded as an image in writeWorkbook(), same pattern
            // as EmployeeController's Advanced Export photo column.
            'photo_path' => $photoPath,
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
                __('Photo'),
                __('Employee Name (EN)'),
                __('Employer'),
                __('Sub-tab'),
                __('Request No.'),
                __('Step Before'),
                __('Step After'),
                __('Status Before'),
                __('Status After'),
                __('Remarks'),
                __('Checked At'),
            ];
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:L1')->getFont()->setBold(true);
            $sheet->getStyle('A1:L1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
            $sheet->getStyle('A1:L1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $rowNum = 2;
            $seq = 1;
            foreach ($rowsByMenu[$menuKey] ?? [] as $row) {
                $sheet->fromArray([
                    $seq,
                    null, // Photo column — filled below with an embedded image, not text
                    $row['name_en'],
                    $row['employer'],
                    $row['work_type_name'],
                    $row['request_number'],
                    $row['step_before'],
                    $row['step_after'],
                    $row['status_before'],
                    $row['status_after'],
                    $row['remarks'],
                    $row['checked_at'],
                ], null, "A{$rowNum}");

                $sheet->getRowDimension($rowNum)->setRowHeight(90); // ~120px, room for the photo

                if (!empty($row['photo_path']) && Storage::disk('public')->exists($row['photo_path'])) {
                    $drawing = new Drawing();
                    $drawing->setName('Employee Photo');
                    $drawing->setDescription('Employee Photo');
                    $drawing->setPath(Storage::disk('public')->path($row['photo_path']));
                    $drawing->setCoordinates("B{$rowNum}");
                    $drawing->setHeight(100);
                    $drawing->setOffsetX(28);
                    $drawing->setOffsetY(10);
                    $drawing->setWorksheet($sheet);
                } else {
                    $sheet->setCellValue("B{$rowNum}", __('No Photo'));
                    $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                }

                $rowNum++;
                $seq++;
            }

            $sheet->getColumnDimension('B')->setWidth(18);
            foreach (array_diff(range('A', 'L'), ['B']) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);
    }
}

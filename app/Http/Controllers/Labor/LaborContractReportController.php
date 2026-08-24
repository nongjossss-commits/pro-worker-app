<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\ProWorkerContract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "รายงานสถิติการเบิกสัญญา" — Super Admin only (see routes/labor.php,
 * same tier as labor.users.*). Same day/week/month/quarter/year
 * period-resolution pattern as FinanceReportController/LaborReportController.
 */
class LaborContractReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        $activePeriod = $request->input('period', 'month');
        $activeDate = $request->filled('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        [$byTeam, $byStaff, $total, $totalWorkers] = $this->aggregate($from, $to);

        return view('labor.contract_reports.index', compact('byTeam', 'byStaff', 'total', 'totalWorkers', 'from', 'to', 'activePeriod', 'activeDate'));
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        [$byTeam, $byStaff, $total, $totalWorkers] = $this->aggregate($from, $to);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Contract Stats');

        $sheet->setCellValue('A1', 'สถิติการเบิกสัญญา Pro Worker ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'));
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14]]);

        $row = 3;
        $sheet->setCellValue("A{$row}", 'แยกตามทีม');
        $sheet->getStyle("A{$row}")->applyFromArray(['font' => ['bold' => true]]);
        $row++;
        $sheet->fromArray(['ทีม', 'จำนวนสัญญา', 'จำนวนแรงงาน'], null, "A{$row}");
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray(['font' => ['bold' => true]]);
        $row++;
        foreach ($byTeam as $r) {
            $sheet->fromArray([$r->name, $r->total, $r->total_workers], null, "A{$row}");
            $row++;
        }

        $row += 2;
        $sheet->setCellValue("A{$row}", 'แยกตามพนักงาน');
        $sheet->getStyle("A{$row}")->applyFromArray(['font' => ['bold' => true]]);
        $row++;
        $sheet->fromArray(['พนักงาน', 'รหัส', 'จำนวนสัญญา', 'จำนวนแรงงาน'], null, "A{$row}");
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray(['font' => ['bold' => true]]);
        $row++;
        foreach ($byStaff as $r) {
            $sheet->fromArray([$r->name, $r->staff_code ?: '-', $r->total, $r->total_workers], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf('proworker-contract-stats_%s_%s.xlsx', $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function aggregate(Carbon $from, Carbon $to): array
    {
        $base = ProWorkerContract::whereBetween('issued_at', [$from, $to->copy()->endOfDay()]);

        $byTeam = (clone $base)
            ->selectRaw('labor_team_id, count(*) as total, coalesce(sum(worker_count), 0) as total_workers')
            ->groupBy('labor_team_id')
            ->with('team:id,name')
            ->get()
            ->map(fn ($r) => (object) ['name' => $r->team->name ?? __('Unassigned'), 'total' => $r->total, 'total_workers' => $r->total_workers])
            ->sortByDesc('total')
            ->values();

        $byStaff = (clone $base)
            ->selectRaw('issued_by, count(*) as total, coalesce(sum(worker_count), 0) as total_workers')
            ->groupBy('issued_by')
            ->with('issuer:id,name,staff_code')
            ->get()
            ->map(fn ($r) => (object) [
                'name' => $r->issuer->name ?? __('Unknown'),
                'staff_code' => $r->issuer->staff_code ?? null,
                'total' => $r->total,
                'total_workers' => $r->total_workers,
            ])
            ->sortByDesc('total')
            ->values();

        $total = (clone $base)->count();
        $totalWorkers = (int) (clone $base)->sum('worker_count');

        return [$byTeam, $byStaff, $total, $totalWorkers];
    }

    protected function resolveRange(Request $request): array
    {
        if ($request->filled('period') && $request->filled('date')) {
            return $this->resolvePeriodRange($request->input('period'), Carbon::parse($request->input('date')));
        }

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : Carbon::now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }

    protected function resolvePeriodRange(string $period, Carbon $date): array
    {
        return match ($period) {
            'day' => [$date->copy()->startOfDay(), $date->copy()->startOfDay()],
            'week' => [$date->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay(), $date->copy()->endOfWeek(Carbon::SATURDAY)->startOfDay()],
            'month' => [$date->copy()->startOfMonth()->startOfDay(), $date->copy()->endOfMonth()->startOfDay()],
            'quarter' => [$date->copy()->firstOfQuarter()->startOfDay(), $date->copy()->lastOfQuarter()->startOfDay()],
            'year' => [$date->copy()->startOfYear()->startOfDay(), $date->copy()->endOfYear()->startOfDay()],
            default => [$date->copy()->startOfDay(), $date->copy()->startOfDay()],
        };
    }
}

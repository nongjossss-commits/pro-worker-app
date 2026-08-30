<?php

namespace App\Http\Controllers;

use App\Models\JobCheckSession;
use App\Services\AccountingPeriodService;
use App\Services\JobCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * "โหมดเช็คงาน" (Job Check Mode) — lets an operator snapshot the current
 * step/status of every employee & item across Pre-Production, Workflow,
 * มติลงทะเบียน, and มติต่ออายุ before starting a check pass, then diff the
 * final state against that snapshot when done. Comparing only final vs
 * initial state (not an action log) means a tick-then-undo nets out to "no
 * movement" automatically — no special-case logic needed for that.
 *
 * Snapshot/diff/export logic lives in JobCheckService (shared with the
 * scheduled app:auto-finish-stale-job-check-sessions command, which force-
 * closes a session the user forgot to finish before the next 05:00
 * cutover).
 */
class JobCheckSessionController extends Controller
{
    public function __construct(protected JobCheckService $jobCheckService)
    {
        $this->middleware('auth');
    }

    public function status(Request $request)
    {
        $session = JobCheckSession::current()->where('user_id', $request->user()->id)->first();

        return response()->json([
            'active' => (bool) $session,
            'status' => $session?->status,
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

        $session = JobCheckSession::current()->where('user_id', $request->user()->id)->first();

        if (!$session) {
            $session = JobCheckSession::create([
                'user_id' => $request->user()->id,
                'status' => 'active',
                'started_at' => now(),
            ]);

            $this->jobCheckService->takeSnapshot($session);
        }

        // ?_jc=1 marks THIS tab as the one participating in Job Check Mode
        // — see EnforceJobCheckMode + job-check-widget.blade.php's marker
        // propagation script. Without it, opening a second browser tab
        // would silently get dragged into the confinement too.
        return redirect()->route('workflow.index', ['_jc' => 1])
            ->with('success', __('Job Check Mode started. You are now confined to Pre-Production, Workflow, Registration Resolution, and Renewal Resolution in this tab until you finish.'));
    }

    public function pause(Request $request)
    {
        $session = JobCheckSession::active()->where('user_id', $request->user()->id)->firstOrFail();
        $session->update(['status' => 'paused']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'status' => 'paused']);
        }

        return redirect()->route('workflow.index')
            ->with('success', __('Job Check Mode paused. You can work anywhere until you resume.'));
    }

    public function resume(Request $request)
    {
        $session = JobCheckSession::where('user_id', $request->user()->id)->where('status', 'paused')->firstOrFail();
        $session->update(['status' => 'active']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'status' => 'active']);
        }

        return redirect()->route('workflow.index', ['_jc' => 1])
            ->with('success', __('Job Check Mode resumed in this tab.'));
    }

    public function cancel(Request $request)
    {
        $session = JobCheckSession::current()->where('user_id', $request->user()->id)->firstOrFail();
        $session->snapshots()->delete();
        $session->update(['status' => 'cancelled', 'ended_at' => now()]);

        return redirect()->route('workflow.index')->with('success', __('Job Check Mode cancelled.'));
    }

    public function finish(Request $request)
    {
        $session = JobCheckSession::current()->where('user_id', $request->user()->id)->firstOrFail();

        $result = $this->jobCheckService->completeSession($session, now());

        return response()->json([
            'success' => true,
            'moved_count' => $result['moved_count'],
            'not_moved_count' => $result['not_moved_count'],
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
     * by JobCheckService::writeWorkbook() (A=No., B=name, C=employer,
     * D=sub-tab, E=request no., F=step before, G=step after, ...) — both
     * are owned by this same feature, so the coupling is safe.
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
                // 2=employer, 3=sub-tab, 4=request no., 5=step before,
                // 6=step after, ...
                $name = trim((string) ($row[1] ?? ''));
                $employer = trim((string) ($row[2] ?? '')) ?: '-';
                if ($name === '' && $employer === '-') {
                    continue; // fully blank row
                }

                $stepBefore = trim((string) ($row[5] ?? '')) ?: '-';
                $stepAfter = trim((string) ($row[6] ?? '')) ?: '-';

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
}

<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Jobs\ProcessDownload;
use App\Models\DownloadTask;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function index()
    {
        // A task still pending/processing long after any real job could run
        // was cut off (server limit / restart) — show it as failed instead of
        // "Processing…" forever.
        DownloadTask::where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'processing'])
            ->where('updated_at', '<', now()->subMinutes(45))
            ->update([
                'status' => 'failed',
                'error_message' => 'หมดเวลา — การสร้างไฟล์ถูกหยุดกลางทาง กรุณาลองใหม่ (ถ้าเลือกลูกจ้างจำนวนมาก ลองแบ่งเป็นชุดเล็กลง หรือดาวน์โหลดแบบ ZIP)',
            ]);

        $tasks = DownloadTask::where('user_id', Auth::id())
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($task) {
                // "x / y" employees done, written by ProcessDownload::progress()
                $task->progress = in_array($task->status, ['pending', 'processing'], true)
                    ? \Illuminate\Support\Facades\Cache::get('download_progress:' . $task->id)
                    : null;
                return $task;
            });

        return response()->json($tasks);
    }

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'selected_files' => 'required|array',
            'type' => 'required|in:zip,pdf,zip_single,pdf_individual',
            'stamp_employee_info' => 'nullable|boolean',
            'stamp_company_info' => 'nullable|boolean',
            'download_profile_id' => 'nullable|exists:download_profiles,id',
        ]);

        // Authorization Logic:
        // If user is NOT admin/staff (can't manage tickets), we must filter the IDs to ensure they own them.
        // Using Employee::whereIn automatically applies the 'employerTenancy' global scope for employers.
        // This means querying for IDs that don't belong to the employer will return nothing.
        $authorizedIds = Employee::whereIn('id', $validated['employee_ids'])
            ->pluck('id')
            ->toArray();

        if (empty($authorizedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid employees selected or you do not have permission to view them.'
            ], 403);
        }

        // Preserve the original order from the request (user selection order)
        $authorizedIdsMap = array_flip(array_map('intval', $authorizedIds));
        $authorizedEmployeeIds = array_values(array_filter(
            array_map('intval', $validated['employee_ids']),
            fn($id) => isset($authorizedIdsMap[$id])
        ));

        $task = DownloadTask::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'status' => 'pending',
        ]);

        $options = [
            'stamp_employee_info' => $request->boolean('stamp_employee_info'),
            'stamp_company_info' => $request->boolean('stamp_company_info'),
            'download_profile_id' => $request->input('download_profile_id'),
        ];

        // Runs right after this response is sent, in this same PHP process —
        // no queue worker needed (the reason it used to be dispatchSync), but a
        // big download no longer has to finish inside the browser's request
        // (web-server timeouts cut large jobs off). The Download Center polls
        // the task and starts the download when it's ready.
        ProcessDownload::dispatchAfterResponse($task->id, $authorizedEmployeeIds, $validated['selected_files'], $options);

        $task->refresh();

        // Log download action
        ActivityLogHelper::logAction('download', 'ดาวน์โหลดเอกสาร (' . $validated['type'] . ') จำนวน ' . count($authorizedEmployeeIds) . ' ลูกจ้าง', DownloadTask::class, $task->id, [
            'type' => $validated['type'],
            'employee_count' => count($authorizedEmployeeIds),
            'employee_ids' => array_slice($authorizedEmployeeIds, 0, 20), // store max 20 IDs
            'selected_files' => $validated['selected_files'],
        ]);

        return response()->json([
            'success' => true,
            'message' => $task->status === 'completed' ? 'Download prepared successfully.' : 'Download started.',
            'task_id' => $task->id,
            'status' => $task->status,
            'download_url' => $task->status === 'completed' ? route('admin.downloads.download', $task->id) : null
        ]);
    }

    public function download(Request $request, DownloadTask $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        if ($task->status !== 'completed' || !$task->file_path) {
            abort(404);
        }

        $path = $task->file_path;
        // Job returns 'downloads/filename.zip', stored under storage/app/private/
        // so it can only be fetched here (owner check above). Files made before
        // that change still live under storage/app/public/ until they are pruned
        // (app:prune-download-files).
        $fullPath = storage_path('app/private/' . $path);
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/public/' . $path);
        }

        if (!file_exists($fullPath)) {
            abort(404, 'File not found on server.');
        }

        $disposition = $request->input('disposition', 'attachment');

        if ($disposition === 'inline') {
            return response()->file($fullPath, [
                'Content-Type' => mime_content_type($fullPath)
            ])->setContentDisposition('inline', basename($fullPath), 'download_file');
        }

        return response()->download($fullPath, basename($fullPath));
    }

    // Helper to clean up old files (optional, can be scheduled)
    public function cleanup()
    {
        // Logic to delete tasks older than 24h
    }
}

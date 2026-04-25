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
        $tasks = DownloadTask::where('user_id', Auth::id())
            ->latest()
            ->limit(20)
            ->get();

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

        // Use dispatchSync to ensure immediate execution, avoiding stuck "pending" tasks
        // if the queue worker is not running.
        ProcessDownload::dispatchSync($task->id, $authorizedEmployeeIds, $validated['selected_files'], $options);

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
        // file_path is relative to storage/app/public/ (as returned by job)
        // We need to serve it.
        // Job returns 'downloads/filename.zip'.

        $fullPath = storage_path('app/public/' . $path);

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

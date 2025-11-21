<?php

namespace App\Http\Controllers;

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
            'type' => 'required|in:zip,pdf,zip_single',
        ]);

        // Authorization Logic:
        // If user is NOT admin/staff (can't manage tickets), we must filter the IDs to ensure they own them.
        // Using Employee::whereIn automatically applies the 'employerTenancy' global scope for employers.
        // This means querying for IDs that don't belong to the employer will return nothing.
        $authorizedEmployeeIds = Employee::whereIn('id', $validated['employee_ids'])
            ->pluck('id')
            ->toArray();

        if (empty($authorizedEmployeeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid employees selected or you do not have permission to view them.'
            ], 403);
        }

        $task = DownloadTask::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'status' => 'pending',
        ]);

        // Use dispatchSync to ensure immediate execution, avoiding stuck "pending" tasks
        // if the queue worker is not running.
        ProcessDownload::dispatchSync($task->id, $authorizedEmployeeIds, $validated['selected_files']);

        $task->refresh();

        return response()->json([
            'success' => true,
            'message' => $task->status === 'completed' ? 'Download prepared successfully.' : 'Download started.',
            'task_id' => $task->id,
            'status' => $task->status,
            'download_url' => $task->status === 'completed' ? route('admin.downloads.download', $task->id) : null
        ]);
    }

    public function download(DownloadTask $task)
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

        return response()->download($fullPath);
    }

    // Helper to clean up old files (optional, can be scheduled)
    public function cleanup()
    {
        // Logic to delete tasks older than 24h
    }
}

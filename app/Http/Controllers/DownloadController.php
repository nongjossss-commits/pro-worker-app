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
            'type' => 'required|in:zip,pdf',
        ]);

        $task = DownloadTask::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'status' => 'pending',
        ]);

        ProcessDownload::dispatch($task->id, $validated['employee_ids'], $validated['selected_files']);

        return response()->json([
            'success' => true,
            'message' => 'Download started in background.',
            'task_id' => $task->id
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

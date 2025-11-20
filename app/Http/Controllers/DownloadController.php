<?php

namespace App\Http\Controllers;

use App\Models\DownloadJob;
use App\Jobs\ProcessDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function index()
    {
        $jobs = DownloadJob::where('user_id', Auth::id())
            ->latest()
            ->get();

        // Since this is often loaded via AJAX for a menu, we might return JSON or a view partial
        // But for now let's assume we might want a full page or JSON.
        // Let's return JSON for the dropdown menu.
        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'file_types' => 'required|array',
            'mode' => 'required|in:zip,merge',
        ]);

        $job = DownloadJob::create([
            'user_id' => Auth::id(),
            'status' => 'pending',
            'type' => $request->mode,
        ]);

        ProcessDownload::dispatch($job, $request->employee_ids, $request->file_types);

        return response()->json([
            'message' => 'Download started in background.',
            'job_id' => $job->id
        ]);
    }

    public function download(DownloadJob $downloadJob)
    {
        // Authorization
        if ($downloadJob->user_id !== Auth::id()) {
            abort(403);
        }

        if ($downloadJob->status !== 'completed' || !$downloadJob->file_path) {
            abort(404, 'File not ready.');
        }

        $path = $downloadJob->file_path;

        if (!Storage::disk('public')->exists($path)) {
             abort(404, 'File not found on disk.');
        }

        return Storage::disk('public')->download($path);
    }

    public function checkStatus(DownloadJob $downloadJob) {
         if ($downloadJob->user_id !== Auth::id()) {
            abort(403);
        }
        return response()->json($downloadJob);
    }
}

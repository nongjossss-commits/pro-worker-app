<?php

namespace App\Jobs;

use App\Models\DownloadTask;
use App\Models\User;
use App\Notifications\PdfBatchCompleted;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FinalizePdfBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customBatchId;
    protected $userId;
    protected $outputType;
    protected $totalEmployees;

    public function __construct($customBatchId, $userId, $outputType, $totalEmployees)
    {
        $this->customBatchId = $customBatchId;
        $this->userId = $userId;
        $this->outputType = $outputType;
        $this->totalEmployees = $totalEmployees;
    }

    public function handle()
    {
        $user = User::find($this->userId);
        if (!$user) return;

        if ($this->outputType === 'save_to_slot') {
            // Just notify
            $user->notify(new PdfBatchCompleted('save_to_slot', $this->totalEmployees));
        } else {
            // Download Mode: Zip and Create Task
            $this->finalizeDownload($user);
        }
    }

    protected function finalizeDownload($user)
    {
        $tempDir = 'temp/batches/' . $this->customBatchId;
        $fullTempPath = storage_path('app/public/' . $tempDir);

        if (!File::exists($fullTempPath) || File::isEmptyDirectory($fullTempPath)) {
            // Nothing generated?
             \Log::warning("FinalizePdfBatch: No files found in $fullTempPath");
             return;
        }

        $zipName = 'export_' . date('Ymd_His') . '.zip';
        $zipRelativePath = 'downloads/' . $zipName;
        $zipFullPath = storage_path('app/public/' . $zipRelativePath);

        // Ensure downloads dir exists
        if (!File::exists(dirname($zipFullPath))) {
            File::makeDirectory(dirname($zipFullPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = File::files($fullTempPath);
            foreach ($files as $file) {
                $zip->addFile($file->getRealPath(), $file->getFilename());
            }
            $zip->close();
        } else {
             \Log::error("FinalizePdfBatch: Failed to create zip at $zipFullPath");
             return;
        }

        // Create Download Task
        $task = DownloadTask::create([
            'user_id' => $user->id,
            'type' => 'zip', // Generic zip type
            'status' => 'completed',
            'file_path' => $zipRelativePath // DownloadController expects relative to storage/app/public
        ]);

        // Clean up temp batch folder
        File::deleteDirectory($fullTempPath);

        // Notify
        $user->notify(new PdfBatchCompleted('download', $this->totalEmployees, $task->id));
    }
}

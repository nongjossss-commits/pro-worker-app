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
             // Notify user of total failure
             // We can't use PdfBatchCompleted with download link, so we might need a simpler message or repurpose it.
             // For now, let's create a task marked as failed so user sees something in UI?
             // Or better: Notify using a specific message if supported.
             return;
        }

        $allFiles = File::files($fullTempPath);
        $pdfFiles = [];
        $errorFiles = [];

        foreach ($allFiles as $file) {
            if ($file->getExtension() === 'pdf') {
                $pdfFiles[] = $file;
            } elseif (str_starts_with($file->getFilename(), 'error_')) {
                $errorFiles[] = $file;
            }
        }

        // Scenario 1: Total Failure (Only errors, no PDFs)
        if (empty($pdfFiles) && !empty($errorFiles)) {
             $errorSummary = "Batch Generation Failed.\n\nErrors encountered:\n";
             foreach ($errorFiles as $ef) {
                 $errorSummary .= $ef->getFilename() . ": " . file_get_contents($ef->getRealPath()) . "\n";
             }

             // Create a text file for the user to download as the "result"
             $failReportName = 'generation_failed_report_' . date('Ymd_His') . '.txt';
             $failReportPath = 'downloads/' . $failReportName;
             Storage::disk('public')->put($failReportPath, $errorSummary);

             // Create Task pointing to this text file
             $task = DownloadTask::create([
                'user_id' => $user->id,
                'type' => 'zip', // Keep type zip or change to 'log' if UI supports it, but zip is safer fallback
                'status' => 'completed',
                'file_path' => $failReportPath
            ]);

            File::deleteDirectory($fullTempPath);
            $user->notify(new PdfBatchCompleted('download', $this->totalEmployees, $task->id));
            return;
        }

        // Scenario 2: Success or Partial Success
        $zipName = 'export_' . date('Ymd_His') . '.zip';
        $zipRelativePath = 'downloads/' . $zipName;
        $zipFullPath = storage_path('app/public/' . $zipRelativePath);

        // Ensure downloads dir exists
        if (!File::exists(dirname($zipFullPath))) {
            File::makeDirectory(dirname($zipFullPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add PDFs
            foreach ($pdfFiles as $file) {
                $zip->addFile($file->getRealPath(), $file->getFilename());
            }

            // If there are errors, combine them into one report log inside the ZIP
            if (!empty($errorFiles)) {
                $errorLogContent = "Generation Report - Errors Encountered:\n\n";
                foreach ($errorFiles as $ef) {
                    $errorLogContent .= $ef->getFilename() . ": " . file_get_contents($ef->getRealPath()) . "\n";
                }
                $zip->addFromString('_Generation_Errors_Report.txt', $errorLogContent);
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

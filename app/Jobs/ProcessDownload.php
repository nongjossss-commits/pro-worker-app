<?php

namespace App\Jobs;

use App\Models\DownloadTask;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use setasign\Fpdi\Fpdi;
use Exception;

class ProcessDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taskId;
    protected $employeeIds;
    protected $selectedFiles;

    // Map frontend checkbox values to model attributes
    protected $fileMap = [
        'photo' => 'employeePhoto',
        'passport' => ['passport_file_path', 'employee_doc_1'],
        'visa' => ['visa_file_path', 'employee_doc_2'],
        'work_permit' => ['work_permit_file_path', 'employee_doc_3'],
        'pink_card' => ['pink_card_file_path', 'employee_doc_4'],
        'insurance' => ['insurance_attachment_path', 'insurance_document_path', 'insurance_document_path_private'],
        'other_docs' => [
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7',
            'employee_doc_8', 'employee_doc_9', 'employee_doc_10',
            'employee_doc_11', 'employee_doc_12'
        ],
    ];

    public function __construct($taskId, $employeeIds, $selectedFiles)
    {
        $this->taskId = $taskId;
        $this->employeeIds = $employeeIds;
        $this->selectedFiles = $selectedFiles;
    }

    public function handle()
    {
        $task = DownloadTask::find($this->taskId);
        if (!$task) return;

        $task->update(['status' => 'processing']);

        try {
            $employees = Employee::whereIn('id', $this->employeeIds)->get();
            $tempDir = storage_path('app/temp_downloads/' . $task->id);
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $outputFile = '';

            if ($task->type === 'zip') {
                $outputFile = $this->createZip($employees, $tempDir, $task);
            } else {
                $outputFile = $this->createPdf($employees, $tempDir, $task);
            }

            $task->update([
                'status' => 'completed',
                'file_path' => $outputFile
            ]);

            // Cleanup temp dir
            $this->deleteDir($tempDir);

        } catch (Exception $e) {
            $task->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }

    protected function createZip($employees, $tempDir, $task)
    {
        $zipFileName = 'download_' . $task->id . '_' . date('YmdHis') . '.zip';
        $zipPath = storage_path('app/public/downloads/' . $zipFileName);

        // Ensure download directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Cannot create zip file");
        }

        foreach ($employees as $employee) {
            $folderName = $this->sanitizeFileName($employee->employeeNameTh ?? $employee->employeeNameEn ?? 'Employee_' . $employee->id);

            foreach ($this->selectedFiles as $fileType) {
                $this->addFilesToZip($zip, $employee, $fileType, $folderName);
            }
        }

        $zip->close();
        return 'downloads/' . $zipFileName;
    }

    protected function addFilesToZip($zip, $employee, $fileType, $folderName)
    {
        $attributes = $this->fileMap[$fileType] ?? [];
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        foreach ($attributes as $attr) {
            if (!empty($employee->$attr)) {
                $filePath = $this->getFilePath($employee->$attr);
                if ($filePath && file_exists($filePath)) {
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                    $zip->addFile($filePath, $folderName . '/' . $fileType . '_' . basename($filePath));
                }
            }
        }
    }

    protected function createPdf($employees, $tempDir, $task)
    {
        if (!class_exists(Fpdi::class)) {
            throw new Exception("FPDI library not found.");
        }

        $pdf = new Fpdi();
        // Disable auto page break to handle large images manually if needed,
        // but mostly we want to control page adding.
        $pdf->SetAutoPageBreak(false);

        foreach ($employees as $employee) {
            $employeeName = $employee->employeeNameTh ?? $employee->employeeNameEn ?? 'Employee ' . $employee->id;

            // Add a separator page for the employee
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 20);
            // Note: FPDF generic font doesn't support Thai.
            // We might need to use a supported font or just use English/ID.
            // For now, using English ID fallback to avoid ????? characters if font missing.
            $displayName = iconv('UTF-8', 'cp874//TRANSLIT', $employeeName); // Try conversion or fallback
            if (!$displayName) $displayName = "Employee ID: " . $employee->id;

            $pdf->Cell(0, 10, $displayName, 0, 1, 'C');

            foreach ($this->selectedFiles as $fileType) {
                $this->addFilesToPdf($pdf, $employee, $fileType);
            }
        }

        $fileName = 'merged_' . $task->id . '_' . date('YmdHis') . '.pdf';
        $outputPath = storage_path('app/public/downloads/' . $fileName);

        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $pdf->Output('F', $outputPath);
        return 'downloads/' . $fileName;
    }

    protected function addFilesToPdf($pdf, $employee, $fileType)
    {
        $attributes = $this->fileMap[$fileType] ?? [];
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        foreach ($attributes as $attr) {
            if (!empty($employee->$attr)) {
                $filePath = $this->getFilePath($employee->$attr);
                if ($filePath && file_exists($filePath)) {
                    $mime = mime_content_type($filePath);
                    try {
                        if ($mime === 'application/pdf') {
                            $pageCount = $pdf->setSourceFile($filePath);
                            for ($i = 1; $i <= $pageCount; $i++) {
                                $tplIdx = $pdf->importPage($i);
                                $size = $pdf->getTemplateSize($tplIdx);
                                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                                $pdf->useTemplate($tplIdx);
                            }
                        } elseif (in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                            $pdf->AddPage();
                            // Scale image to fit page
                            $pdf->Image($filePath, 10, 10, 190);
                        }
                    } catch (Exception $e) {
                        // Log error but continue
                        // Log::error("Failed to merge file: " . $e->getMessage());
                    }
                }
            }
        }
    }

    protected function getFilePath($dbPath)
    {
        // Handle both 'public/...' and regular paths
        if (Storage::disk('public')->exists($dbPath)) {
             return Storage::disk('public')->path($dbPath);
        }
        if (Storage::disk('private')->exists($dbPath)) {
            return Storage::disk('private')->path($dbPath);
        }
        // Check if it's a full path already or relative
        if (file_exists($dbPath)) return $dbPath;

        return null;
    }

    protected function sanitizeFileName($filename)
    {
        // Remove illegal characters
        return preg_replace('/[^a-zA-Z0-9\-\_\p{Thai}]/u', '_', $filename);
    }

    protected function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}

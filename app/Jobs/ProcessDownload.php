<?php

namespace App\Jobs;

use App\Models\DownloadJob;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
// We will use Fpdi for PDF merging. The user must ensure setasign/fpdi is installed.
use setasign\Fpdi\Fpdi;

class ProcessDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $downloadJob;
    protected $employeeIds;
    protected $selectedFiles;

    /**
     * Create a new job instance.
     *
     * @param DownloadJob $downloadJob
     * @param array $employeeIds
     * @param array $selectedFiles
     */
    public function __construct(DownloadJob $downloadJob, array $employeeIds, array $selectedFiles)
    {
        $this->downloadJob = $downloadJob;
        $this->employeeIds = $employeeIds;
        $this->selectedFiles = $selectedFiles;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->downloadJob->update(['status' => 'processing']);

        try {
            if ($this->downloadJob->type === 'zip') {
                $this->processZip();
            } elseif ($this->downloadJob->type === 'merge') {
                $this->processMerge();
            }

            $this->downloadJob->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $this->downloadJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            // Cleanup if needed
        }
    }

    protected function processZip()
    {
        $zip = new ZipArchive();
        $fileName = 'download_' . $this->downloadJob->id . '_' . time() . '.zip';
        $zipPath = storage_path('app/public/downloads/' . $fileName);

        // Ensure directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $employees = Employee::whereIn('id', $this->employeeIds)->get();

            foreach ($employees as $employee) {
                $folderName = $this->sanitizeFileName($employee->employeeNameTh ?? $employee->employeeNameEn ?? 'Employee_' . $employee->id);

                foreach ($this->selectedFiles as $fileKey) {
                    $fileData = $this->getFileData($employee, $fileKey);
                    if ($fileData && Storage::disk('public')->exists($fileData['path'])) {
                        $zip->addFile(
                            Storage::disk('public')->path($fileData['path']),
                            $folderName . '/' . $fileData['name']
                        );
                    }
                }
            }
            $zip->close();

            $this->downloadJob->update(['file_path' => 'downloads/' . $fileName]);
        } else {
            throw new \Exception("Could not create ZIP file.");
        }
    }

    protected function processMerge()
    {
        // Requires setasign/fpdi
        if (!class_exists(Fpdi::class)) {
             throw new \Exception("FPDI library not found. Please run 'composer require setasign/fpdf setasign/fpdi'.");
        }

        $pdf = new Fpdi();
        $employees = Employee::whereIn('id', $this->employeeIds)->get();
        $filesAdded = 0;

        foreach ($employees as $employee) {
            foreach ($this->selectedFiles as $fileKey) {
                $fileData = $this->getFileData($employee, $fileKey);

                if ($fileData && Storage::disk('public')->exists($fileData['path'])) {
                    $fullPath = Storage::disk('public')->path($fileData['path']);
                    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                    try {
                        if ($extension === 'pdf') {
                            $pageCount = $pdf->setSourceFile($fullPath);
                            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                                $templateId = $pdf->importPage($pageNo);
                                $pdf->AddPage();
                                $pdf->useTemplate($templateId, ['adjustPageSize' => true]);
                            }
                            $filesAdded++;
                        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $pdf->AddPage();
                            // Fit image to page
                            $pdf->Image($fullPath, 10, 10, 190); // Simple placement
                            $filesAdded++;
                        }
                    } catch (\Exception $e) {
                        // Skip file if corrupt or incompatible, log error but continue
                        continue;
                    }
                }
            }
        }

        if ($filesAdded === 0) {
             throw new \Exception("No valid files found to merge.");
        }

        $fileName = 'merged_' . $this->downloadJob->id . '_' . time() . '.pdf';
        $filePath = storage_path('app/public/downloads/' . $fileName);

        // Ensure directory exists
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $pdf->Output('F', $filePath);
        $this->downloadJob->update(['file_path' => 'downloads/' . $fileName]);
    }

    protected function sanitizeFileName($filename) {
        return preg_replace('/[^a-zA-Z0-9ก-๙\-_]/u', '_', $filename);
    }

    protected function getFileData($employee, $key)
    {
        $path = null;
        $name = null;

        switch ($key) {
            case 'photo':
                $path = $employee->employeePhoto;
                $name = 'Photo.jpg'; // Or detect ext
                break;
            case 'passport':
                $path = $employee->employee_doc_1; // Mapped from logic
                $name = 'Passport.pdf'; // Or detect
                break;
            case 'visa':
                $path = $employee->employee_doc_2;
                $name = 'Visa.pdf';
                break;
            case 'work_permit':
                $path = $employee->employee_doc_3;
                $name = 'WorkPermit.pdf';
                break;
             case 'pink_card':
                $path = $employee->employee_doc_4;
                $name = 'PinkCard.pdf';
                break;
            case 'insurance':
                 // Determine which insurance file to use based on type
                 if ($employee->insurance_type == 'ประกันสังคม') {
                     $path = $employee->insurance_document_path_social ?? $employee->insurance_document_path;
                 } elseif ($employee->insurance_type == 'ประกันโรงพยาบาล') {
                     $path = $employee->insurance_document_path_hospital;
                 } elseif ($employee->insurance_type == 'ประกันเอกชน') {
                     $path = $employee->insurance_document_path_private;
                 }
                 $name = 'Insurance.pdf';
                 break;
            // Add other mappings as needed
            case 'tor_ror_38':
                $path = $employee->employee_doc_5;
                $name = 'TorRor38.pdf';
                break;
            case '90_day_report':
                $path = $employee->employee_doc_6;
                $name = '90DayReport.pdf';
                break;
        }

        if ($path) {
             // Try to detect extension if not hardcoded, or just trust the file
             $ext = pathinfo($path, PATHINFO_EXTENSION);
             if ($ext) {
                 $name = pathinfo($name, PATHINFO_FILENAME) . '.' . $ext;
             }
             return ['path' => $path, 'name' => $name];
        }

        return null;
    }
}

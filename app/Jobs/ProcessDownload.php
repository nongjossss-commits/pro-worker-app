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
use Illuminate\Support\Facades\Log;
use ZipArchive;
use setasign\Fpdi\Fpdi;
use Exception;
use Throwable;

class ProcessDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taskId;
    protected $employeeIds;
    protected $selectedFiles;
    protected $tempImageFiles = [];

    // Map frontend checkbox values to model attributes
    protected $fileMap = [
        'photo' => 'employeePhoto',
        'insurance' => ['insurance_attachment_path', 'insurance_document_path', 'insurance_document_path_private', 'social_security_file', 'insurance_file'],
        'passport' => ['passport_file_path', 'employee_doc_1'],
        'visa' => ['visa_file_path', 'employee_doc_2'],
        'work_permit' => ['work_permit_file_path', 'employee_doc_3'],
        'pink_card' => ['pink_card_file_path', 'employee_doc_4'],
        'tor_ror_38' => 'employee_doc_5',
        'report_90_day' => 'employee_doc_6',
        'residence_notification' => 'employee_doc_7',
        'hometown_doc' => 'employee_doc_8',
        'other_doc_1' => 'employee_doc_9',
        'other_doc_2' => 'employee_doc_10',
        'other_doc_3' => 'employee_doc_11',
        'other_doc_4' => 'employee_doc_12',
        'other_doc_5' => 'employee_doc_13',
        'other_doc_6' => 'employee_doc_14',
        'other_doc_7' => 'employee_doc_15',
        'other_doc_8' => 'employee_doc_16',
        'other_doc_9' => 'employee_doc_17',
        'other_doc_10' => 'employee_doc_18',
    ];

    public function __construct($taskId, $employeeIds, $selectedFiles)
    {
        $this->taskId = $taskId;
        $this->employeeIds = $employeeIds;
        $this->selectedFiles = $selectedFiles;
    }

    public function handle()
    {
        // Increase memory limit and execution time for large PDF merges
        ini_set('memory_limit', '512M');
        set_time_limit(600); // Increased to 10 minutes

        $task = DownloadTask::find($this->taskId);
        if (!$task) return;

        $task->update(['status' => 'processing']);

        try {
            // Check for font files and define font path if needed
            if (!defined('FPDF_FONTPATH')) {
                $fontPath = storage_path('fonts/');
                if (!file_exists($fontPath)) {
                    mkdir($fontPath, 0755, true);
                }

                // Ensure standard fonts exist in the custom font path
                // This prevents "No such file or directory" errors when FPDF tries to load core fonts
                $standardFonts = ['helvetica.php', 'helveticab.php', 'helveticai.php', 'helveticabi.php', 'courier.php', 'times.php'];
                $vendorFontPath = base_path('vendor/setasign/fpdf/font/');

                foreach ($standardFonts as $fontFile) {
                    if (!file_exists($fontPath . $fontFile) && file_exists($vendorFontPath . $fontFile)) {
                        @copy($vendorFontPath . $fontFile, $fontPath . $fontFile);
                    }
                }

                define('FPDF_FONTPATH', $fontPath);
            }

            $employees = Employee::whereIn('id', $this->employeeIds)->get();
            $tempDir = storage_path('app/temp_downloads/' . $task->id);
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $outputFile = '';

            if ($task->type === 'zip') {
                $outputFile = $this->createZip($employees, $tempDir, $task, false);
            } elseif ($task->type === 'zip_single') {
                $outputFile = $this->createZip($employees, $tempDir, $task, true);
            } else {
                $outputFile = $this->createPdf($employees, $tempDir, $task);
            }

            $task->update([
                'status' => 'completed',
                'file_path' => $outputFile
            ]);

            // Cleanup temp dir and normalized images
            $this->deleteDir($tempDir);
            $this->cleanupTempImages();

        } catch (Throwable $e) {
            Log::error("Download Task Failed: " . $e->getMessage());
            $task->update([
                'status' => 'failed',
                'error_message' => 'Error: ' . $e->getMessage()
            ]);
            // Attempt cleanup even on failure
            $this->cleanupTempImages();
        }
    }

    protected function createZip($employees, $tempDir, $task, $singleFolder = false)
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
            // Use English Title + Name if available, per user request
            if (!empty($employee->employeeNameEn)) {
                $prefix = !empty($employee->employeeTitleEn) ? $employee->employeeTitleEn . ' ' : '';
                $rawName = $prefix . $employee->employeeNameEn;
            } else {
                $rawName = $employee->employeeNameTh ?? 'Employee_' . $employee->id;
            }

            $safeName = $this->sanitizeFileName($rawName);

            // If singleFolder is true, we use an empty folder name (root),
            // but we might want to prefix the file with the employee name to avoid collisions.
            // If separated, we use the safeName as the folder.
            $folderName = $singleFolder ? '' : $safeName;
            $filePrefix = $singleFolder ? $safeName . '_' : '';

            foreach ($this->selectedFiles as $fileType) {
                $this->addFilesToZip($zip, $employee, $fileType, $folderName, $filePrefix);
            }
        }

        $zip->close();
        return 'downloads/' . $zipFileName;
    }

    protected function addFilesToZip($zip, $employee, $fileType, $folderName, $filePrefix = '')
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

                    // Construct the internal path in the zip
                    // If folderName is empty, it goes to root.
                    // format: [Folder/] [Prefix] [FileType] _ [OriginalName]

                    $internalPath = ($folderName ? $folderName . '/' : '') .
                                    $filePrefix . $fileType . '_' . basename($filePath);

                    $zip->addFile($filePath, $internalPath);
                }
            }
        }
    }

    protected function createPdf($employees, $tempDir, $task)
    {
        if (!class_exists(Fpdi::class)) {
            throw new Exception("FPDI library not found.");
        }

        try {
            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);

            // Attempt to load Thai font
            $hasThaiFont = false;
            // Check for both .php and .z files which are required by FPDF
            // We assume they are in storage/fonts/ or default font path
            // Note: FPDF_FONTPATH is defined at start of handle()

            // Try standard names
            $fontFiles = ['THSarabunNew.php', 'THSarabunNew.z'];
            $fontDir = defined('FPDF_FONTPATH') ? FPDF_FONTPATH : storage_path('fonts/');

            if (file_exists($fontDir . 'THSarabunNew.php')) {
                $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
                $pdf->AddFont('THSarabunNew', 'B', 'THSarabunNew-Bold.php'); // Assuming bold exists too if main does
                $hasThaiFont = true;
            }

            foreach ($employees as $employee) {
                $employeeNameTh = $employee->employeeNameTh;
                $employeeNameEn = $employee->employeeNameEn;

                // Add separator page
                $pdf->AddPage();

                if ($hasThaiFont && $employeeNameTh) {
                    $pdf->SetFont('THSarabunNew', 'B', 24);
                    // Convert UTF-8 to cp874 (TIS-620) for FPDF
                    $displayName = @iconv('UTF-8', 'cp874//TRANSLIT', $employeeNameTh);
                } else {
                    $pdf->SetFont('Arial', 'B', 20);
                    $displayName = $employeeNameEn ?? 'Employee ID: ' . $employee->id;
                    // Sanitize fallback
                    $displayName = preg_replace('/[^\x20-\x7E]/', '', $displayName);
                }

                // Centered Name
                $pdf->SetXY(0, 100);
                $pdf->Cell(0, 10, $displayName, 0, 1, 'C');

                // Add Subtitle (ID or Employer)
                $pdf->SetFont($hasThaiFont ? 'THSarabunNew' : 'Arial', '', 16);
                $subText = "ID: " . $employee->id;
                $pdf->Cell(0, 10, $subText, 0, 1, 'C');

                foreach ($this->selectedFiles as $fileType) {
                    try {
                        $this->addFilesToPdf($pdf, $employee, $fileType);
                    } catch (Throwable $e) {
                        Log::warning("Failed to add file type $fileType for employee {$employee->id}: " . $e->getMessage());
                    }
                }
            }

            $fileName = 'merged_' . $task->id . '_' . date('YmdHis') . '.pdf';
            $outputPath = storage_path('app/public/downloads/' . $fileName);

            if (!file_exists(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }

            $pdf->Output('F', $outputPath);
            return 'downloads/' . $fileName;

        } catch (Throwable $e) {
            throw new Exception("PDF Generation Error: " . $e->getMessage());
        }
    }

    protected function addFilesToPdf($pdf, $employee, $fileType)
    {
        $attributes = $this->fileMap[$fileType] ?? [];
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        foreach ($attributes as $attr) {
            if (!empty($employee->$attr)) {
                $originalFilePath = $this->getFilePath($employee->$attr);
                if ($originalFilePath && file_exists($originalFilePath)) {
                    try {
                        $mime = @mime_content_type($originalFilePath);

                        if ($mime === 'application/pdf') {
                            $pageCount = $pdf->setSourceFile($originalFilePath);
                            for ($i = 1; $i <= $pageCount; $i++) {
                                try {
                                    $tplIdx = $pdf->importPage($i);
                                    $size = $pdf->getTemplateSize($tplIdx);

                                    // Use original orientation
                                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                                    $pdf->useTemplate($tplIdx);
                                } catch (Throwable $e) {
                                    // Log and skip bad pages
                                    Log::warning("Failed to import page $i of $originalFilePath: " . $e->getMessage());
                                    continue;
                                }
                            }
                        } elseif (strpos($mime, 'image/') === 0) {
                            // Normalize Image: Converts all images (jpg, png, gif, webp, bmp) to a standard temporary JPEG
                            $normalizedPath = $this->normalizeImage($originalFilePath);
                            if (!$normalizedPath) continue;

                            $this->tempImageFiles[] = $normalizedPath; // Track for cleanup

                            $pdf->AddPage();

                            // A4 Dimensions in mm
                            $pageW = 210;
                            $pageH = 297;
                            $margin = 10;
                            $writableW = $pageW - ($margin * 2);
                            $writableH = $pageH - ($margin * 2);

                            // Get image dimensions
                            list($imgW, $imgH) = getimagesize($normalizedPath);

                            // Calculate aspect ratio
                            $ratio = $imgW / $imgH;

                            // Determine new dimensions fitting within margins
                            if ($writableW / $writableH > $ratio) {
                               $newH = $writableH;
                               $newW = $writableH * $ratio;
                            } else {
                               $newW = $writableW;
                               $newH = $writableW / $ratio;
                            }

                            // Center the image
                            $x = ($pageW - $newW) / 2;
                            $y = ($pageH - $newH) / 2;

                            $pdf->Image($normalizedPath, $x, $y, $newW, $newH);
                        }
                    } catch (Throwable $e) {
                        Log::error("Failed to merge file $originalFilePath: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Converts various image formats to a standard JPEG compatible with FPDF.
     * Handles transparency (PNG/GIF) by adding a white background.
     */
    protected function normalizeImage($filePath)
    {
        try {
            if (!function_exists('imagecreatefromstring')) {
                throw new Exception("GD library not installed.");
            }

            $data = file_get_contents($filePath);
            if (!$data) return null;

            $srcImg = @imagecreatefromstring($data);
            if (!$srcImg) return null;

            $width = imagesx($srcImg);
            $height = imagesy($srcImg);

            // Create a new true color image
            $dstImg = imagecreatetruecolor($width, $height);

            // Fill with white background (handles transparency)
            $white = imagecolorallocate($dstImg, 255, 255, 255);
            imagefilledrectangle($dstImg, 0, 0, $width, $height, $white);

            // Copy and merge
            imagecopy($dstImg, $srcImg, 0, 0, 0, 0, $width, $height);

            // Create temp file
            $tempPath = tempnam(sys_get_temp_dir(), 'img_norm_') . '.jpg';

            // Save as JPEG with high quality
            imagejpeg($dstImg, $tempPath, 90);

            // Free memory
            imagedestroy($srcImg);
            imagedestroy($dstImg);

            return $tempPath;

        } catch (Throwable $e) {
            Log::error("Image normalization failed for $filePath: " . $e->getMessage());
            return null;
        }
    }

    protected function cleanupTempImages()
    {
        foreach ($this->tempImageFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->tempImageFiles = [];
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

        // Sometimes path is stored as 'images/...' but it's in storage/app/public/images
        $publicPath = storage_path('app/public/' . $dbPath);
        if (file_exists($publicPath)) return $publicPath;

        return null;
    }

    protected function sanitizeFileName($filename)
    {
        // Remove illegal characters
        return preg_replace('/[^a-zA-Z0-9\-\_\p{Thai}]/u', '_', $filename);
    }

    protected function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            // Check if it exists as a file just in case
            if (file_exists($dirPath)) unlink($dirPath);
            return;
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

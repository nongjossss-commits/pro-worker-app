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
    protected $options;
    protected $downloadProfile;
    protected $tempImageFiles = [];

    // Map frontend checkbox values to model attributes
    protected $fileMap = [
        'photo' => 'employeePhoto',
        'insurance' => ['insurance_document_path', 'insurance_document_path_private', 'social_security_file', 'insurance_file'],
        'passport' => ['passport_file_path', 'employee_doc_1'],
        'visa' => ['visa_file_path', 'employee_doc_2'],
        'work_permit' => ['work_permit_file_path', 'employee_doc_3'],
        'pink_card' => ['pink_card_file_path', 'employee_doc_4'],
        'tor_ror_38' => 'employee_doc_5',
        'medical_certificate' => 'medical_certificate_path',
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

    public function __construct($taskId, $employeeIds, $selectedFiles, $options = [])
    {
        $this->taskId = $taskId;
        $this->employeeIds = $employeeIds;
        $this->selectedFiles = $selectedFiles;
        $this->options = $options;
    }

    public function handle()
    {
        // Increase memory limit and execution time for large PDF merges
        ini_set('memory_limit', '512M');
        set_time_limit(600); // Increased to 10 minutes

        $task = DownloadTask::find($this->taskId);
        if (!$task) return;

        $task->update(['status' => 'processing']);

        if (!empty($this->options['stamp_company_info']) && !empty($this->options['download_profile_id'])) {
            $this->downloadProfile = \App\Models\DownloadProfile::find($this->options['download_profile_id']);
        }

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

                // Ensure Thai fonts exist
                $thaiFonts = ['THSarabunNew.php', 'THSarabunNew.z', 'THSarabunNew.ttf'];
                $publicFontPath = public_path('fonts/');

                foreach ($thaiFonts as $fontFile) {
                    if (!file_exists($fontPath . $fontFile) && file_exists($publicFontPath . $fontFile)) {
                        @copy($publicFontPath . $fontFile, $fontPath . $fontFile);
                    }
                }

                define('FPDF_FONTPATH', $fontPath);
            }

            $employeesUnsorted = Employee::whereIn('id', $this->employeeIds)->get();
            // Sort employees by the order of IDs passed from frontend (preserves user selection order)
            $idOrder = array_flip(array_map('intval', $this->employeeIds));
            $employees = $employeesUnsorted->sort(function ($a, $b) use ($idOrder) {
                return ($idOrder[$a->id] ?? PHP_INT_MAX) - ($idOrder[$b->id] ?? PHP_INT_MAX);
            })->values();
            $tempDir = storage_path('app/temp_downloads/' . $task->id);
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $outputFile = '';

            if ($task->type === 'zip') {
                $outputFile = $this->createZip($employees, $tempDir, $task, false);
            } elseif ($task->type === 'zip_single') {
                $outputFile = $this->createZip($employees, $tempDir, $task, true);
            } elseif ($task->type === 'pdf_individual') {
                $outputFile = $this->createIndividualPdfsZip($employees, $tempDir, $task);
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

        // Setup font for header text if needed
        $hasThaiFont = false;
        $fontDir = defined('FPDF_FONTPATH') ? FPDF_FONTPATH : storage_path('fonts/');
        if (file_exists($fontDir . 'THSarabunNew.php')) {
            $hasThaiFont = true;
        }

        $sequenceNumber = 1;
        foreach ($employees as $employee) {
            // Use English Title + Name if available, per user request
            if (!empty($employee->employeeNameEn)) {
                $prefix = !empty($employee->employeeTitleEn) ? $employee->employeeTitleEn . ' ' : '';
                $rawName = $prefix . $employee->employeeNameEn;
            } else {
                $rawName = $employee->employeeNameTh ?? 'Employee';
            }

            $safeName = $sequenceNumber . '. ' . $this->sanitizeFileName($rawName) . '_' . $employee->id;

            // If singleFolder is true, we use an empty folder name (root),
            // but we might want to prefix the file with the employee name to avoid collisions.
            // If separated, we use the safeName as the folder.
            $folderName = $singleFolder ? '' : $safeName;
            $filePrefix = $singleFolder ? $safeName . '_' : '';

            foreach ($this->selectedFiles as $fileType) {
                $this->addFilesToZip($zip, $employee, $fileType, $folderName, $filePrefix, $hasThaiFont);
            }
            $sequenceNumber++;
        }

        $zip->close();
        return 'downloads/' . $zipFileName;
    }

    protected function addFilesToZip($zip, $employee, $fileType, $folderName, $filePrefix = '', $hasThaiFont = false)
    {
        $attributes = $this->fileMap[$fileType] ?? [];
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        foreach ($attributes as $attr) {
            if (!empty($employee->$attr)) {
                $filePath = $this->getFilePath($employee->$attr);
                if ($filePath && file_exists($filePath)) {
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                    // Construct the internal path in the zip
                    // If folderName is empty, it goes to root.
                    // format: [Folder/] [Prefix] [FileType] _ [OriginalName]

                    $internalPath = ($folderName ? $folderName . '/' : '') .
                                    $filePrefix . $fileType . '_' . basename($filePath);

                    $shouldStamp = (!empty($this->options['stamp_company_info']) || !empty($this->options['stamp_employee_info']));

                    if ($shouldStamp && $fileType !== 'photo' && in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        // Stamp the file and add the stamped version. Note that it returns a PDF.
                        $stampedFilePath = $this->stampFileForZip($filePath, $employee, $hasThaiFont);
                        if ($stampedFilePath) {
                            // The stamped file is now a PDF, so we should change the extension in the zip path
                            // if it was an image. If it was already a PDF, it just overwrites.
                            if ($ext !== 'pdf') {
                                $internalPath = preg_replace('/\.[^.]+$/', '.pdf', $internalPath);
                            }
                            $zip->addFile($stampedFilePath, $internalPath);
                            // Track for cleanup
                            $this->tempImageFiles[] = $stampedFilePath;
                        } else {
                            // Fallback to original if stamping fails
                            $zip->addFile($filePath, $internalPath);
                        }
                    } else {
                        $zip->addFile($filePath, $internalPath);
                    }
                }
            }
        }
    }

    protected function createIndividualPdfsZip($employees, $tempDir, $task)
    {
        if (!class_exists(Fpdi::class)) {
            throw new Exception("FPDI library not found.");
        }

        $zipFileName = 'download_individual_pdfs_' . $task->id . '_' . date('YmdHis') . '.zip';
        $zipPath = storage_path('app/public/downloads/' . $zipFileName);

        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Cannot create zip file");
        }

        $hasThaiFont = false;
        $fontDir = defined('FPDF_FONTPATH') ? FPDF_FONTPATH : storage_path('fonts/');

        if (file_exists($fontDir . 'THSarabunNew.php')) {
            $hasThaiFont = true;
        }

        $sequenceNumber = 1;
        foreach ($employees as $employee) {
            try {
                $pdf = new Fpdi();
                $pdf->SetAutoPageBreak(false);

                if ($hasThaiFont) {
                    $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
                }

                $hasPages = false;

                foreach ($this->selectedFiles as $fileType) {
                    try {
                        $pageCountBefore = $pdf->PageNo();
                        $this->addFilesToPdf($pdf, $employee, $fileType, $hasThaiFont);
                        if ($pdf->PageNo() > $pageCountBefore) {
                            $hasPages = true;
                        }
                    } catch (Throwable $e) {
                        Log::warning("Failed to add file type $fileType for employee {$employee->id}: " . $e->getMessage());
                    }
                }

                if ($hasPages) {
                    if (!empty($employee->employeeNameEn)) {
                        $prefix = !empty($employee->employeeTitleEn) ? $employee->employeeTitleEn . ' ' : '';
                        $rawName = $prefix . $employee->employeeNameEn;
                    } else {
                        $rawName = $employee->employeeNameTh ?? 'Employee';
                    }

                    $safeName = $sequenceNumber . '. ' . $this->sanitizeFileName($rawName) . '_' . $employee->id;
                    $pdfPath = $tempDir . '/' . $safeName . '.pdf';

                    $pdf->Output('F', $pdfPath);
                    $zip->addFile($pdfPath, $safeName . '.pdf');
                }
            } catch (Throwable $e) {
                Log::warning("Failed to create individual PDF for employee {$employee->id}: " . $e->getMessage());
            }
            $sequenceNumber++;
        }

        $zip->close();
        return 'downloads/' . $zipFileName;
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
                $hasThaiFont = true;
            }

            foreach ($employees as $employee) {
                foreach ($this->selectedFiles as $fileType) {
                    try {
                        $this->addFilesToPdf($pdf, $employee, $fileType, $hasThaiFont);
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

    protected function addFilesToPdf($pdf, $employee, $fileType, $hasThaiFont)
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

                                    // Add Header
                                    $this->drawHeader($pdf, $employee, $hasThaiFont);

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
                            // Add extra top margin for header so image doesn't overlap
                            $topMargin = 15;

                            $writableW = $pageW - ($margin * 2);
                            $writableH = $pageH - ($margin + $topMargin);

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

                            // Center the image, respecting top margin
                            $x = ($pageW - $newW) / 2;
                            $y = $topMargin + ($writableH - $newH) / 2;

                            $pdf->Image($normalizedPath, $x, $y, $newW, $newH);

                            // Add Header
                            $this->drawHeader($pdf, $employee, $hasThaiFont);
                        }
                    } catch (Throwable $e) {
                        Log::error("Failed to merge file $originalFilePath: " . $e->getMessage());
                    }
                }
            }
        }
    }

    protected function drawHeader($pdf, $employee, $hasThaiFont)
    {
        // Save current position
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pageWidth = $pdf->GetPageWidth();
        $margin = 10;
        // Total available width minus margins
        $totalAvailableW = $pageWidth - ($margin * 2);
        // Half of the page width for left/right split
        $halfW = $totalAvailableW / 2;

        // Always try to use THSarabunNew if possible
        $baseFontSize = 14;
        try {
            $pdf->SetFont('THSarabunNew', '', $baseFontSize);
            $hasThaiFont = true;
        } catch (Throwable $e) {
            $pdf->SetFont($hasThaiFont ? 'THSarabunNew' : 'Arial', '', $baseFontSize);
        }

        $topY = 2; // Fixed top Y position

        // 1. Stamp Company Info (Left Half)
        if (!empty($this->options['stamp_company_info']) && $this->downloadProfile) {
            $currentX = $margin;
            $logoW = 0;

            // Draw Logo
            if ($this->downloadProfile->logo_path) {
                $logoPath = Storage::disk('public')->path($this->downloadProfile->logo_path);
                if (file_exists($logoPath)) {
                    try {
                        $pdf->Image($logoPath, $currentX, $topY, 0, 10);
                        list($imgW, $imgH) = getimagesize($logoPath);
                        if ($imgH > 0) {
                            $ratio = $imgW / $imgH;
                            $renderedW = 10 * $ratio;
                            $logoW = $renderedW + 3; // Add padding
                            $currentX += $logoW;
                        } else {
                            $logoW = 30; // Fallback
                            $currentX += $logoW;
                        }
                    } catch (Throwable $e) {
                        Log::warning("Failed to stamp logo for profile {$this->downloadProfile->id}: " . $e->getMessage());
                    }
                }
            }

            // Company Text
            $companyTextRaw = $this->downloadProfile->name;
            if ($this->downloadProfile->phone_number) {
                $companyTextRaw .= ' โทร. ' . $this->downloadProfile->phone_number;
            }

            $companyTextDisplay = @iconv('UTF-8', 'cp874//IGNORE', $companyTextRaw);
            $availableCompanyW = $halfW - $logoW;

            // Auto-size font for Company Info
            $fontSize = $baseFontSize;
            $pdf->SetFont($hasThaiFont ? 'THSarabunNew' : 'Arial', '', $fontSize);
            while ($fontSize > 6 && $pdf->GetStringWidth($companyTextDisplay) > $availableCompanyW - 2) {
                $fontSize -= 0.5;
                $pdf->SetFont($hasThaiFont ? 'THSarabunNew' : 'Arial', '', $fontSize);
            }

            $textY = $topY + 1;
            $pdf->SetXY($currentX, $textY);
            $pdf->Cell($availableCompanyW, 10, $companyTextDisplay, 0, 0, 'L');
        }

        // 2. Stamp Employee Info (Right Half)
        if (!empty($this->options['stamp_employee_info']) || !isset($this->options['stamp_employee_info'])) {
            // Restore base font for measuring
            $pdf->SetFont($hasThaiFont ? 'THSarabunNew' : 'Arial', '', $baseFontSize);

            $employeeNameTh = $employee->employeeNameTh;
            $employeeNameEn = $employee->employeeNameEn;
            $employeeTitleEn = $employee->employeeTitleEn ?? '';

            // Combine ID and Name directly as raw UTF-8 string first
            $employeeNameStr = '';
            if (!empty($employeeNameEn)) {
                $prefix = !empty($employeeTitleEn) ? $employeeTitleEn . ' ' : '';
                $employeeNameStr = $prefix . preg_replace('/[^\x20-\x7E\p{Thai}]/u', '', $employeeNameEn);
            } elseif ($hasThaiFont && !empty($employeeNameTh)) {
                $employeeNameStr = $employeeNameTh;
            } else {
                $employeeNameStr = 'Employee';
            }
            $headerTextRaw = $employeeNameStr . "   ID: " . $employee->id;

            // Convert the ENTIRE right-side string using iconv correctly
            $headerTextDisplay = @iconv('UTF-8', 'cp874//IGNORE', $headerTextRaw);

            // Auto-size font for Employee Info
            $fontSize = $baseFontSize;
            $pdf->SetFont($hasThaiFont ? 'THSarabunNew' : 'Arial', '', $fontSize);
            while ($fontSize > 6 && $pdf->GetStringWidth($headerTextDisplay) > $halfW - 2) {
                $fontSize -= 0.5;
                $pdf->SetFont($hasThaiFont ? 'THSarabunNew' : 'Arial', '', $fontSize);
            }

            // Draw in the right half
            $pdf->SetXY($margin + $halfW, $topY + 1);
            $pdf->Cell($halfW, 10, $headerTextDisplay, 0, 0, 'R');
        }

        // Restore position
        $pdf->SetXY($x, $y);
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

    /**
     * Creates a temporary PDF version of an image or modifies an existing PDF to include stamps for ZIP downloads.
     */
    protected function stampFileForZip($filePath, $employee, $hasThaiFont)
    {
        try {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            if (!class_exists(Fpdi::class)) {
                return null;
            }

            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);

            $fontDir = defined('FPDF_FONTPATH') ? FPDF_FONTPATH : storage_path('fonts/');
            if (file_exists($fontDir . 'THSarabunNew.php')) {
                $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
            }

            if ($ext === 'pdf') {
                $pageCount = $pdf->setSourceFile($filePath);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplIdx = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tplIdx);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplIdx);
                    $this->drawHeader($pdf, $employee, $hasThaiFont);
                }
            } else {
                // It's an image. Normalize it first
                $normalizedPath = $this->normalizeImage($filePath);
                if (!$normalizedPath) return null;

                $this->tempImageFiles[] = $normalizedPath;

                $pdf->AddPage();
                $pageW = 210;
                $pageH = 297;
                $margin = 10;
                $topMargin = 15;
                $writableW = $pageW - ($margin * 2);
                $writableH = $pageH - ($margin + $topMargin);

                list($imgW, $imgH) = getimagesize($normalizedPath);
                $ratio = $imgW / $imgH;

                if ($writableW / $writableH > $ratio) {
                   $newH = $writableH;
                   $newW = $writableH * $ratio;
                } else {
                   $newW = $writableW;
                   $newH = $writableW / $ratio;
                }

                $x = ($pageW - $newW) / 2;
                $y = $topMargin + ($writableH - $newH) / 2;

                $pdf->Image($normalizedPath, $x, $y, $newW, $newH);
                $this->drawHeader($pdf, $employee, $hasThaiFont);
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'stamped_') . '.pdf';
            $pdf->Output('F', $tempPath);
            return $tempPath;

        } catch (Throwable $e) {
            Log::error("File stamping for ZIP failed ($filePath): " . $e->getMessage());
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

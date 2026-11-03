<?php

namespace App\Jobs;

use App\Models\DownloadTask;
use App\Models\Employee;
use App\Support\DownloadFileName;
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

    // Tracks file types that were requested but couldn't be added for a given
    // employee, keyed by "{employee_id}|{fileType}" — surfaced to the admin
    // via DownloadTask::error_message (kept even on a 'completed' task, not
    // just 'failed' ones) so a silently-skipped file is visible instead of
    // just producing a smaller-than-expected zip with no explanation.
    protected $missingFiles = [];

    // PDFs that exist but couldn't be merged even after conversion (see
    // openPdfSource()) — reported in the summary; in "one PDF per person"
    // mode the original file is added to the ZIP instead.
    protected $unreadablePdfs = [];
    protected $task;

    // Files of which only some pages went into the PDF (a page failed to import).
    protected $partialPdfs = [];

    // Selected employees that could not be loaded (e.g. moved to the trash meanwhile).
    protected $notFoundIds = [];

    // Per-job caches: PDFs already converted (path => converted path) and
    // names already used in the ZIP being built.
    protected $normalizedPdfs = [];
    protected $zipNames = [];

    // "Merge into one PDF" that had to be split to stay within memory (createPdf()).
    protected $splitIntoParts = 0;

    /** Employees per employer, first-picked employer first — for file / folder names. */
    protected $employerGroups;

    // Thai labels matching the checkboxes in download-modals.blade.php —
    // used only for the missing-files summary message.
    protected $fileTypeLabels = [
        'photo' => 'รูปถ่าย',
        'insurance' => 'ไฟล์แนบประกัน',
        'passport' => 'พาสปอร์ต',
        'visa' => 'วีซ่า',
        'work_permit' => 'ใบอนุญาตทำงาน',
        'pink_card' => 'บัตรชมพู',
        'tor_ror_38' => 'ทร. 38',
        'medical_certificate' => 'ใบรับรองแพทย์',
        'report_90_day' => 'รายงานตัว 90 วัน',
        'residence_notification' => 'ใบแจ้งที่พักอาศัย',
        'hometown_doc' => 'เอกสารบ้านเกิด',
        'other_doc_1' => 'เอกสารอื่นๆ 1',
        'other_doc_2' => 'เอกสารอื่นๆ 2',
        'other_doc_3' => 'เอกสารอื่นๆ 3',
        'other_doc_4' => 'เอกสารอื่นๆ 4',
        'other_doc_5' => 'เอกสารอื่นๆ 5',
        'other_doc_6' => 'เอกสารอื่นๆ 6',
        'other_doc_7' => 'เอกสารอื่นๆ 7',
        'other_doc_8' => 'เอกสารอื่นๆ 8',
        'other_doc_9' => 'เอกสารอื่นๆ 9',
        'other_doc_10' => 'เอกสารอื่นๆ 10',
    ];

    /** Label of a file type; "Other Document N" uses the name Super Admin set (OtherDocumentNames). */
    protected function fileTypeLabel(string $type): string
    {
        if (preg_match('/^other_doc_(\d+)$/', $type, $m)) {
            return \App\Support\OtherDocumentNames::employee((int) $m[1]);
        }

        return $this->fileTypeLabels[$type] ?? $type;
    }

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
        // Large downloads (many employees / big files): more memory and time,
        // and keep going even if the browser tab that started it is closed —
        // the job runs after the response (DownloadController::initiate()).
        $this->raiseMemoryLimit('1024M');
        @set_time_limit(1800);
        @ignore_user_abort(true);

        $task = DownloadTask::find($this->taskId);
        if (!$task) return;
        $this->task = $task;

        $task->update(['status' => 'processing']);
        $this->progress(0);

        // A fatal error (e.g. out of memory) can't be caught — without this the
        // task would show "Processing…" forever.
        register_shutdown_function(function () use ($task) {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true)
                && DownloadTask::whereKey($task->id)->where('status', 'processing')->exists()) {
                DownloadTask::whereKey($task->id)->update([
                    'status' => 'failed',
                    'error_message' => 'ระบบหยุดทำงานระหว่างสร้างไฟล์ (หน่วยความจำหรือเวลาไม่พอ) — ลองแบ่งเลือกลูกจ้างเป็นชุดเล็กลง หรือดาวน์โหลดแบบ ZIP: ' . $err['message'],
                ]);
            }
        });

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

            // In batches — a single IN (…) with thousands of ids breaks on some databases.
            $employeesUnsorted = collect();
            foreach (array_chunk(array_values(array_unique(array_map('intval', $this->employeeIds))), 500) as $chunk) {
                $employeesUnsorted = $employeesUnsorted->concat(Employee::with('employer')->whereIn('id', $chunk)->get());
            }
            // Sort employees by the order of IDs passed from frontend (preserves user selection order)
            $idOrder = array_flip(array_map('intval', $this->employeeIds));
            $this->notFoundIds = array_values(array_diff(array_unique(array_map('intval', $this->employeeIds)), $employeesUnsorted->pluck('id')->map(fn ($id) => (int) $id)->all()));
            $employees = $employeesUnsorted->sort(function ($a, $b) use ($idOrder) {
                return ($idOrder[$a->id] ?? PHP_INT_MAX) - ($idOrder[$b->id] ?? PHP_INT_MAX);
            })->values();
            $this->employerGroups = $employees->groupBy(fn ($e) => (int) $e->employer_id);
            // Several employers + the user chose "a folder per employer" (download popup).
            $byEmployer = !empty($this->options['employer_folders']) && $this->employerGroups->count() > 1;
            $tempDir = storage_path('app/temp_downloads/' . $task->id);
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $outputFile = '';

            if ($task->type === 'zip') {
                $outputFile = $this->createZip($employees, $tempDir, $task, false, $byEmployer);
            } elseif ($task->type === 'zip_single') {
                $outputFile = $this->createZip($employees, $tempDir, $task, true, $byEmployer);
            } elseif ($task->type === 'pdf_individual') {
                $outputFile = $this->createIndividualPdfsZip($employees, $tempDir, $task, $byEmployer);
            } elseif ($byEmployer) {
                $outputFile = $this->createMergedPdfsByEmployer($employees, $tempDir, $task);
            } else {
                $outputFile = $this->createPdf($employees, $tempDir, $task);
            }

            $task->update([
                'status' => 'completed',
                'file_path' => $outputFile,
                'error_message' => $this->buildMissingFilesSummary(),
            ]);
            \Illuminate\Support\Facades\Cache::forget('download_progress:' . $task->id);

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

    /**
     * Where the finished file goes: storage/app/private/downloads/{task id}__{name}
     * — the task id keeps it unique on disk; DownloadController hands it to the
     * browser as just "{name}", e.g. "นายจ้าง ก (5 คน) 2026-10-30 15.36.12.zip"
     * (one employer) or "Employee documents (12 คน) …" (several).
     */
    protected function outputPath(string $ext): string
    {
        $dir = storage_path('app/private/downloads');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $groups = $this->employerGroups ?? collect();
        $name = $groups->count() === 1 ? $this->employerName($groups->first()->first()) : __('Employee documents');
        $count = $groups->sum(fn ($g) => $g->count());

        return 'downloads/' . $this->task->id . '__' . DownloadFileName::make($name, $count, $ext, $this->task->user_id);
    }

    protected function employerName($employee): string
    {
        $employer = $employee->employer ?? null;

        return $employer ? ($employer->getRawOriginal('employerNameTh') ?: ($employer->employerNameEn ?: '-')) : __('No employer');
    }

    /** "นายจ้าง ก (5 คน)" — one folder per employer when the user asked for it. */
    protected function employerFolder($group): string
    {
        return DownloadFileName::withCount($this->employerName($group->first()), $group->count());
    }

    /**
     * The employees as [folder => employees]: one entry per employer (folder
     * "นายจ้าง ก (5 คน)") when $byEmployer, otherwise one entry with no folder.
     */
    protected function employeeSets($employees, bool $byEmployer): array
    {
        if (!$byEmployer) {
            return [['', $employees]];
        }

        return $this->employerGroups->map(fn ($group) => [$this->employerFolder($group), $group->values()])->values()->all();
    }

    protected function employeeFileName($employee, int $sequenceNumber): string
    {
        // Use English Title + Name if available, per user request
        if (!empty($employee->employeeNameEn)) {
            $prefix = !empty($employee->employeeTitleEn) ? $employee->employeeTitleEn . ' ' : '';
            $rawName = $prefix . $employee->employeeNameEn;
        } else {
            $rawName = $employee->employeeNameTh ?? 'Employee';
        }

        return $sequenceNumber . '. ' . $this->sanitizeFileName($rawName) . '_' . $employee->id;
    }

    protected function createZip($employees, $tempDir, $task, $singleFolder = false, bool $byEmployer = false)
    {
        $zipPath = storage_path('app/private/' . ($output = $this->outputPath('zip')));

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

        $done = 0;
        foreach ($this->employeeSets($employees, $byEmployer) as [$employerFolder, $set]) {
            $sequenceNumber = 1; // numbering starts again in each employer folder
            foreach ($set as $employee) {
                $safeName = $this->employeeFileName($employee, $sequenceNumber);

                // Single folder: every file in the root (or the employer's folder),
                // prefixed with the employee name to avoid collisions.
                // Separate: a folder per employee (inside the employer's folder).
                $base = $employerFolder !== '' ? $employerFolder . '/' : '';
                $folderName = $singleFolder ? rtrim($base, '/') : $base . $safeName;
                $filePrefix = $singleFolder ? $safeName . '_' : '';

                foreach ($this->selectedFiles as $fileType) {
                    $this->addFilesToZip($zip, $employee, $fileType, $folderName, $filePrefix, $hasThaiFont);
                }
                $this->progress(++$done);
                $sequenceNumber++;
            }
        }

        $zip->close();
        return $output;
    }

    protected function addFilesToZip($zip, $employee, $fileType, $folderName, $filePrefix = '', $hasThaiFont = false)
    {
        $attributes = $this->fileMap[$fileType] ?? [];
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        $hadValue = false;
        $added = false;

        foreach ($attributes as $attr) {
            if (!empty($employee->$attr)) {
                $hadValue = true;
                $filePath = $this->getFilePath($employee->$attr);
                if ($filePath && file_exists($filePath)) {
                    $added = true;
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
                            $zip->addFile($stampedFilePath, $this->uniqueZipName($internalPath));
                            // Track for cleanup
                            $this->tempImageFiles[] = $stampedFilePath;
                        } else {
                            // Fallback to original if stamping fails
                            $zip->addFile($filePath, $this->uniqueZipName($internalPath));
                        }
                    } else {
                        $zip->addFile($filePath, $this->uniqueZipName($internalPath));
                    }
                }
            }
        }

        if (!$added) {
            $this->recordMissing($employee, $fileType, $hadValue);
        }
    }

    protected function createIndividualPdfsZip($employees, $tempDir, $task, bool $byEmployer = false)
    {
        if (!class_exists(Fpdi::class)) {
            throw new Exception("FPDI library not found.");
        }

        $zipPath = storage_path('app/private/' . ($output = $this->outputPath('zip')));

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Cannot create zip file");
        }

        $hasThaiFont = false;
        $fontDir = defined('FPDF_FONTPATH') ? FPDF_FONTPATH : storage_path('fonts/');

        if (file_exists($fontDir . 'THSarabunNew.php')) {
            $hasThaiFont = true;
        }

        $done = 0;
        foreach ($this->employeeSets($employees, $byEmployer) as $setIndex => [$employerFolder, $set]) {
            $inFolder = $employerFolder !== '' ? $employerFolder . '/' : '';
            $sequenceNumber = 1; // numbering starts again in each employer folder
            foreach ($set as $employee) {
                try {
                    $pdf = new Fpdi();
                    $pdf->SetAutoPageBreak(false);

                    if ($hasThaiFont) {
                        $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
                    }

                    $hasPages = false;
                    $unreadableBefore = count($this->unreadablePdfs);

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

                    $safeName = $this->employeeFileName($employee, $sequenceNumber);

                    if ($hasPages) {
                        $pdfPath = $tempDir . '/' . $setIndex . '_' . $safeName . '.pdf';

                        $pdf->Output('F', $pdfPath);
                        $zip->addFile($pdfPath, $this->uniqueZipName($inFolder . $safeName . '.pdf'));
                    }

                    // PDFs that couldn't be merged: ship the original next to this person's PDF.
                    foreach (array_slice($this->unreadablePdfs, $unreadableBefore) as $entry) {
                        $zip->addFile($entry['path'], $this->uniqueZipName($inFolder . $safeName . '_' . $entry['fileType'] . '_' . basename($entry['path'])));
                    }
                } catch (Throwable $e) {
                    Log::warning("Failed to create individual PDF for employee {$employee->id}: " . $e->getMessage());
                }
                // One person's PDF is on disk now — don't keep it in memory while building the next.
                unset($pdf);
                gc_collect_cycles();
                $this->progress(++$done);
                $sequenceNumber++;
            }
        }

        $zip->close();
        return $output;
    }

    /**
     * All the employees' files merged into PDF(s) written in $tempDir. FPDF
     * keeps the whole document in memory until it is written, and writing it
     * needs about twice that. If a very large merge gets close to PHP's memory
     * limit, finish this part and start another instead of crashing — normal
     * sizes stay one part.
     *
     * @return string[] part files, in order (at least one)
     */
    protected function buildMergedPdfParts($employees, string $tempDir, bool $hasThaiFont, string $partPrefix, int &$done): array
    {
        $newPdf = function () use ($hasThaiFont) {
            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);
            if ($hasThaiFont) {
                $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
            }
            return $pdf;
        };

        $partLimit = (int) ($this->memoryLimitBytes() * 0.4);
        $parts = [];
        $pdf = $newPdf();
        $pdfHasPages = false;
        $total = count($employees);

        foreach (array_values(is_array($employees) ? $employees : $employees->all()) as $index => $employee) {
            foreach ($this->selectedFiles as $fileType) {
                try {
                    $before = $pdf->PageNo();
                    $this->addFilesToPdf($pdf, $employee, $fileType, $hasThaiFont);
                    $pdfHasPages = $pdfHasPages || $pdf->PageNo() > $before;
                } catch (Throwable $e) {
                    Log::warning("Failed to add file type $fileType for employee {$employee->id}: " . $e->getMessage());
                }
            }
            $this->progress(++$done);

            if ($pdfHasPages && $index + 1 < $total && memory_get_usage(true) > $partLimit) {
                $partPath = $tempDir . '/' . $partPrefix . 'part_' . (count($parts) + 1) . '.pdf';
                $pdf->Output('F', $partPath);
                $parts[] = $partPath;
                unset($pdf);
                gc_collect_cycles();
                $pdf = $newPdf();
                $pdfHasPages = false;
            }
        }

        // The last (or only) part — always written, as before.
        if (!$parts || $pdfHasPages) {
            $partPath = $tempDir . '/' . $partPrefix . 'part_' . (count($parts) + 1) . '.pdf';
            $pdf->Output('F', $partPath);
            $parts[] = $partPath;
        }
        unset($pdf);
        gc_collect_cycles();

        return $parts;
    }

    protected function createPdf($employees, $tempDir, $task)
    {
        if (!class_exists(Fpdi::class)) {
            throw new Exception("FPDI library not found.");
        }

        try {
            // Thai font (FPDF_FONTPATH is defined at the start of handle())
            $fontDir = defined('FPDF_FONTPATH') ? FPDF_FONTPATH : storage_path('fonts/');
            $hasThaiFont = file_exists($fontDir . 'THSarabunNew.php');

            $done = 0;
            $parts = $this->buildMergedPdfParts($employees, $tempDir, $hasThaiFont, '', $done);

            if (count($parts) === 1 && empty($this->unreadablePdfs)) {
                $output = $this->outputPath('pdf');
                rename($parts[0], storage_path('app/private/' . $output));
                return $output;
            }

            // Too big for one PDF: the parts together in a ZIP (same order as the employees).
            $output = $this->outputPath('zip');
            $zip = new ZipArchive;
            if ($zip->open(storage_path('app/private/' . $output), ZipArchive::CREATE) !== true) {
                throw new Exception('Cannot create zip file');
            }
            foreach ($parts as $i => $partPath) {
                $zip->addFile($partPath, count($parts) === 1
                    ? DownloadFileName::withCount(__('Employee documents'), count($employees)) . '.pdf'
                    : sprintf('merged_part_%d_of_%d.pdf', $i + 1, count($parts)));
            }
            // Files that couldn't be merged go in as originals — nothing is left out.
            $this->addUnmergedOriginals($zip, $this->unreadablePdfs);
            $zip->close();
            $this->splitIntoParts = count($parts);

            return $output;

        } catch (Throwable $e) {
            throw new Exception("PDF Generation Error: " . $e->getMessage());
        }
    }

    /**
     * Merged PDF, one per employer: a ZIP with a folder per employer
     * ("นายจ้าง ก (5 คน)/") holding that employer's employees merged into
     * "นายจ้าง ก (5 คน).pdf" (in parts only if it is very large).
     */
    protected function createMergedPdfsByEmployer($employees, $tempDir, $task)
    {
        if (!class_exists(Fpdi::class)) {
            throw new Exception("FPDI library not found.");
        }

        try {
            $fontDir = defined('FPDF_FONTPATH') ? FPDF_FONTPATH : storage_path('fonts/');
            $hasThaiFont = file_exists($fontDir . 'THSarabunNew.php');

            $output = $this->outputPath('zip');
            $zip = new ZipArchive;
            if ($zip->open(storage_path('app/private/' . $output), ZipArchive::CREATE) !== true) {
                throw new Exception('Cannot create zip file');
            }

            $done = 0;
            foreach ($this->employeeSets($employees, true) as $setIndex => [$employerFolder, $set]) {
                $unmergedBefore = count($this->unreadablePdfs);
                $parts = $this->buildMergedPdfParts($set, $tempDir, $hasThaiFont, $setIndex . '_', $done);
                foreach ($parts as $i => $partPath) {
                    $name = count($parts) === 1
                        ? $employerFolder . '.pdf'
                        : sprintf('%s part %d of %d.pdf', $employerFolder, $i + 1, count($parts));
                    $zip->addFile($partPath, $this->uniqueZipName($employerFolder . '/' . $name));
                }
                $this->splitIntoParts = max($this->splitIntoParts, count($parts));
                // this employer's files that couldn't be merged, as originals
                $this->addUnmergedOriginals($zip, array_slice($this->unreadablePdfs, $unmergedBefore), $employerFolder . '/');
            }
            $zip->close();

            return $output;

        } catch (Throwable $e) {
            throw new Exception("PDF Generation Error: " . $e->getMessage());
        }
    }

    /** PHP's memory limit in bytes (unlimited → a large number). */
    protected function memoryLimitBytes(): int
    {
        $v = trim((string) ini_get('memory_limit'));
        if ($v === '' || $v === '-1') {
            return 8 * 1073741824;
        }
        $n = (int) $v;
        return match (strtolower(substr($v, -1))) {
            'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n,
        };
    }

    protected function addFilesToPdf($pdf, $employee, $fileType, $hasThaiFont)
    {
        $attributes = $this->fileMap[$fileType] ?? [];
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        $hadValue = false;
        $added = false;

        $foundOnDisk = false;

        foreach ($attributes as $attr) {
            if (!empty($employee->$attr)) {
                $hadValue = true;
                $originalFilePath = $this->getFilePath($employee->$attr);
                if ($originalFilePath && file_exists($originalFilePath)) {
                    $foundOnDisk = true;
                    // "Added" only when pages really went into the PDF (2026-11-03):
                    // before, a file was counted as added as soon as it existed, so a
                    // file that then failed to merge disappeared without a trace.
                    $pagesBefore = $pdf->PageNo();
                    $mergedOk = false;
                    try {
                        $kind = $this->fileKind($originalFilePath);

                        if ($kind === 'pdf') {
                            $pageCount = $this->openPdfSource($pdf, $originalFilePath);
                            if ($pageCount === null) {
                                // Unreadable even after conversion — handled below (original kept).
                                $this->cannotMerge($pdf, $employee, $fileType, $originalFilePath, $hasThaiFont);
                                continue;
                            }
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
                                    $this->partialPdfs[] = ['employee' => $employee, 'fileType' => $fileType, 'page' => $i, 'of' => $pageCount];
                                    continue;
                                }
                            }
                            $mergedOk = $pdf->PageNo() > $pagesBefore;
                        } elseif ($kind === 'image') {
                            // Normalize Image: Converts all images (jpg, png, gif, webp, bmp) to a standard temporary JPEG
                            $normalizedPath = $this->normalizeImage($originalFilePath);
                            if (!$normalizedPath) {
                                // e.g. HEIC from an iPhone — the server can't draw it
                                $this->cannotMerge($pdf, $employee, $fileType, $originalFilePath, $hasThaiFont);
                                continue;
                            }

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
                            $mergedOk = true;
                        }
                    } catch (Throwable $e) {
                        Log::error("Failed to merge file $originalFilePath: " . $e->getMessage());
                    }

                    if ($mergedOk) {
                        $added = true;
                    } elseif ($pdf->PageNo() === $pagesBefore) {
                        // Not a PDF / image the server can read.
                        $this->cannotMerge($pdf, $employee, $fileType, $originalFilePath, $hasThaiFont);
                    } else {
                        // Stopped part-way: some pages are in, the rest are not.
                        $added = true;
                        $this->partialPdfs[] = ['employee' => $employee, 'fileType' => $fileType, 'page' => null, 'of' => null];
                    }
                }
            }
        }

        if (!$added && !$foundOnDisk) {
            $this->recordMissing($employee, $fileType, $hadValue);
        }
    }

    /**
     * What a stored file really is: 'pdf', 'image' or null. Looks at the
     * content first — a PDF is any file with "%PDF" near the start, even with
     * a few junk bytes in front (some scanners / web downloads do that and
     * the strict check said "octet-stream", so the merge skipped the file
     * while the preview — which falls back to the extension — showed it).
     */
    protected function fileKind(string $path): ?string
    {
        $head = @file_get_contents($path, false, null, 0, 1024);
        if ($head !== false && strpos($head, '%PDF') !== false) {
            return 'pdf';
        }
        if (@getimagesize($path)) {
            return 'image';
        }
        if (function_exists('mime_content_type')) {
            $mime = (string) @mime_content_type($path);
            if ($mime === 'application/pdf') return 'pdf';
            if (str_starts_with($mime, 'image/')) return 'image';
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'pdf') return 'pdf';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif', 'tif', 'tiff'], true)) return 'image';

        return null;
    }

    /**
     * A file that exists but can't go into the merged PDF: remembered so the
     * ORIGINAL is shipped in the download (see addUnmergedOriginals()), listed
     * in the summary, and a notice page marks its place in the PDF — so nobody
     * prints the set believing it is complete.
     */
    protected function cannotMerge($pdf, $employee, string $fileType, string $path, bool $hasThaiFont): void
    {
        foreach ($this->unreadablePdfs as $entry) {
            if ($entry['path'] === $path && $entry['employee']->id === $employee->id) {
                return;
            }
        }
        $this->unreadablePdfs[] = ['employee' => $employee, 'fileType' => $fileType, 'path' => $path];

        try {
            $pdf->AddPage();
            $name = trim(($employee->employeeTitleEn ? $employee->employeeTitleEn . ' ' : '') . ($employee->employeeNameEn ?: $employee->employeeNameTh ?: ('#' . $employee->id)));
            if ($hasThaiFont) {
                $pdf->SetFont('THSarabunNew', '', 22);
                $toPdf = fn ($s) => iconv('UTF-8', 'cp874//IGNORE', $s);
                $lines = [
                    'เอกสาร "' . $this->fileTypeLabel($fileType) . '" ของ ' . $name,
                    'รวมเข้า PDF นี้ไม่ได้ (รูปแบบไฟล์ที่ระบบอ่านไม่ได้)',
                    'ไฟล์ต้นฉบับอยู่ในโฟลเดอร์ "' . self::UNMERGED_FOLDER . '" ใน ZIP ที่ดาวน์โหลด',
                ];
            } else {
                $pdf->SetFont('Helvetica', '', 14);
                $toPdf = fn ($s) => $s;
                $lines = [
                    'Document "' . $fileType . '" of ' . $name,
                    'could not be merged into this PDF (unreadable file format).',
                    'The original file is in the "unmerged files" folder of the downloaded ZIP.',
                ];
            }
            $pdf->SetXY(20, 110);
            foreach ($lines as $line) {
                $pdf->Cell(170, 12, $toPdf($line), 0, 1, 'C');
                $pdf->SetX(20);
            }
            $this->drawHeader($pdf, $employee, $hasThaiFont);
        } catch (Throwable $e) {
            Log::warning('ProcessDownload: notice page failed: ' . $e->getMessage());
        }
    }

    /** Folder in the ZIP holding originals that couldn't be merged into the PDF. */
    public const UNMERGED_FOLDER = 'ไฟล์ที่รวมเข้า PDF ไม่ได้';

    /** Put the originals of the files that couldn't be merged into $zip (under $prefix). */
    protected function addUnmergedOriginals(ZipArchive $zip, array $entries, string $prefix = ''): void
    {
        foreach ($entries as $entry) {
            $emp = $entry['employee'];
            $who = $this->sanitizeFileName(($emp->employeeNameEn ?: $emp->employeeNameTh ?: 'Employee') . '_' . $emp->id);
            $zip->addFile($entry['path'], $this->uniqueZipName($prefix . self::UNMERGED_FOLDER . '/' . $who . '_' . $entry['fileType'] . '_' . basename($entry['path'])));
        }
    }

    /** "x / y employees done" for the Download Center (DownloadController::index()). */
    protected function progress(int $done): void
    {
        if (!$this->task) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put('download_progress:' . $this->task->id, [
            'done' => $done,
            'total' => count(array_unique($this->employeeIds)),
        ], now()->addHours(2));
    }

    /** Raise (never lower) PHP's memory limit. */
    protected function raiseMemoryLimit(string $limit): void
    {
        $toBytes = function ($v) {
            $v = trim((string) $v);
            if ($v === '-1') {
                return PHP_INT_MAX;
            }
            $n = (int) $v;
            return match (strtolower(substr($v, -1))) {
                'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n,
            };
        };
        if ($toBytes(ini_get('memory_limit')) < $toBytes($limit)) {
            @ini_set('memory_limit', $limit);
        }
    }

    /** Unique name inside a ZIP — two files with the same name in one folder used to overwrite each other. */
    protected function uniqueZipName(string $name): string
    {
        if (!isset($this->zipNames[$name])) {
            $this->zipNames[$name] = true;
            return $name;
        }
        $info = pathinfo($name);
        $dir = ($info['dirname'] ?? '.') !== '.' ? $info['dirname'] . '/' : '';
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
        for ($i = 2; ; $i++) {
            $candidate = $dir . $info['filename'] . " ({$i})" . $ext;
            if (!isset($this->zipNames[$candidate])) {
                $this->zipNames[$candidate] = true;
                return $candidate;
            }
        }
    }

    /**
     * setSourceFile() with a fallback. FPDI's free parser can't read PDF 1.5+
     * files that use compressed object streams — what most scanners and phone
     * apps produce — so passports / visas / work permits saved that way were
     * silently left out of the "merge into one PDF" / "one PDF per person"
     * downloads while photos (images) came through. Retry on a copy rewritten
     * by PdfGeneratorService::tryNormalizePdf() (Node pdf-lib → Ghostscript →
     * Python), the same repair PDF templates already use.
     *
     * @return int|null page count, or null if the file can't be read at all
     */
    protected function openPdfSource($pdf, string $path): ?int
    {
        try {
            return $pdf->setSourceFile($path);
        } catch (Throwable $e) {
            Log::info("ProcessDownload: PDF needs conversion ({$path}): " . $e->getMessage());
        }

        // Junk bytes before "%PDF" (some scanners / web downloads): a copy that
        // starts at the header usually reads fine — browsers ignore the junk,
        // which is why the preview showed these files (2026-11-03).
        try {
            $head = (string) @file_get_contents($path, false, null, 0, 1024);
            $at = strpos($head, '%PDF');
            if ($at !== false && $at > 0) {
                $trimmed = tempnam(sys_get_temp_dir(), 'pdf_trim_') . '.pdf';
                $in = fopen($path, 'rb');
                $out = fopen($trimmed, 'wb');
                fseek($in, $at);
                stream_copy_to_stream($in, $out);
                fclose($in);
                fclose($out);
                $this->tempImageFiles[] = $trimmed; // removed by cleanupTempImages()
                try {
                    return $pdf->setSourceFile($trimmed);
                } catch (Throwable $e) {
                    $path = $trimmed; // let the conversion below work on the cleaned copy
                }
            }
        } catch (Throwable $e) {
            // fall through to the conversion
        }

        try {
            // Convert each file once per job, even if it's used again.
            if (!isset($this->normalizedPdfs[$path])) {
                $this->normalizedPdfs[$path] = app(\App\Services\PdfGeneratorService::class)->tryNormalizePdf($path);
                $this->tempImageFiles[] = $this->normalizedPdfs[$path]; // removed by cleanupTempImages()
            }
            return $pdf->setSourceFile($this->normalizedPdfs[$path]);
        } catch (Throwable $e) {
            Log::warning("ProcessDownload: PDF could not be read even after conversion ({$path}): " . $e->getMessage());
            return null;
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

            // Phone photos are often stored sideways with an EXIF "rotate" tag;
            // browsers (the preview) apply it, GD does not — turn it upright here.
            $srcImg = $this->applyExifOrientation($srcImg, $this->jpegOrientation($data));

            $width = imagesx($srcImg);
            $height = imagesy($srcImg);
            unset($data);

            // Images only ever fill one A4 page here: 2480 px on the long side
            // is ~300 dpi on A4 — the same on screen and on paper, but a 12 MP
            // phone photo no longer makes the PDF (and PHP's memory) huge.
            // ZIP downloads always keep the original file.
            $maxSide = 2480;
            $scale = min(1, $maxSide / max($width, $height));
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));

            // Create a new true color image
            $dstImg = imagecreatetruecolor($newW, $newH);

            // Fill with white background (handles transparency)
            $white = imagecolorallocate($dstImg, 255, 255, 255);
            imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $white);

            // Copy and merge (resampled when shrinking)
            if ($scale < 1) {
                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $width, $height);
            } else {
                imagecopy($dstImg, $srcImg, 0, 0, 0, 0, $width, $height);
            }
            imagedestroy($srcImg);
            $srcImg = null;

            // Create temp file (tempnam() also creates an empty file without
            // the .jpg suffix — remove it so it isn't left behind)
            $base = tempnam(sys_get_temp_dir(), 'img_norm_');
            $tempPath = $base . '.jpg';
            @unlink($base);

            // Save as JPEG with high quality
            imagejpeg($dstImg, $tempPath, 90);

            // Free memory (source image already destroyed above)
            imagedestroy($dstImg);

            return $tempPath;

        } catch (Throwable $e) {
            Log::error("Image normalization failed for $filePath: " . $e->getMessage());
            return null;
        }
    }

    /**
     * EXIF Orientation (1–8) of a JPEG, read straight from its APP1 segment —
     * works without PHP's exif extension. 1 (= as stored) when absent.
     */
    protected function jpegOrientation(string $data): int
    {
        if (strncmp($data, "\xFF\xD8", 2) !== 0) {
            return 1;
        }
        $pos = 2;
        $len = strlen($data);
        while ($pos + 4 <= $len && $data[$pos] === "\xFF") {
            $marker = ord($data[$pos + 1]);
            $size = unpack('n', substr($data, $pos + 2, 2))[1];
            if ($marker === 0xDA || $size < 2) {
                break; // image data starts — no EXIF before it
            }
            if ($marker === 0xE1 && substr($data, $pos + 4, 6) === "Exif\0\0") {
                $tiff = substr($data, $pos + 10, $size - 8);
                $le = substr($tiff, 0, 2) === 'II';
                $u16 = fn ($o) => unpack($le ? 'v' : 'n', substr($tiff, $o, 2))[1] ?? 0;
                $u32 = fn ($o) => unpack($le ? 'V' : 'N', substr($tiff, $o, 4))[1] ?? 0;
                if (strlen($tiff) < 8) {
                    return 1;
                }
                $ifd = $u32(4);
                $count = $ifd + 2 <= strlen($tiff) ? $u16($ifd) : 0;
                for ($i = 0; $i < $count; $i++) {
                    $entry = $ifd + 2 + $i * 12;
                    if ($entry + 12 > strlen($tiff)) {
                        break;
                    }
                    if ($u16($entry) === 0x0112) {
                        $o = $u16($entry + 8);
                        return ($o >= 1 && $o <= 8) ? $o : 1;
                    }
                }
                return 1;
            }
            $pos += 2 + $size;
        }

        return 1;
    }

    /** Turn a GD image as EXIF Orientation says (2–8); 1 = unchanged. */
    protected function applyExifOrientation($img, int $orientation)
    {
        if ($orientation <= 1 || $orientation > 8) {
            return $img;
        }
        try {
            // flips first (2, 4, 5, 7), then rotation (imagerotate turns counter-clockwise)
            if (in_array($orientation, [2, 5, 7], true)) imageflip($img, IMG_FLIP_HORIZONTAL);
            if ($orientation === 4) imageflip($img, IMG_FLIP_VERTICAL);
            $angle = match ($orientation) { 3 => 180, 5, 6 => 270, 7, 8 => 90, default => 0 };
            if ($angle) {
                $rotated = imagerotate($img, $angle, 0);
                if ($rotated) {
                    imagedestroy($img);
                    $img = $rotated;
                }
            }
        } catch (Throwable $e) {
            Log::warning('ProcessDownload: EXIF orientation not applied: ' . $e->getMessage());
        }

        return $img;
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
                $pageCount = $this->openPdfSource($pdf, $filePath);
                if ($pageCount === null) {
                    return null; // caller adds the original, unstamped file
                }
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

    /**
     * Record that $fileType was requested for $employee but nothing could be
     * added to the output — either the field was never uploaded at all, or
     * it has a stored path whose file is no longer on disk. Deduplicated per
     * employee+fileType so a multi-page PDF doesn't log/report the same gap
     * repeatedly.
     */
    protected function recordMissing($employee, string $fileType, bool $hadValue): void
    {
        $key = $employee->id . '|' . $fileType;
        if (isset($this->missingFiles[$key])) {
            return;
        }

        $reason = $hadValue
            ? 'มีข้อมูล path ในระบบแต่ไม่พบไฟล์จริงในเซิร์ฟเวอร์ (ไฟล์อาจถูกลบหรือย้ายหาย)'
            : 'ยังไม่เคยอัปโหลดไฟล์นี้ให้ลูกจ้างรายนี้';

        $this->missingFiles[$key] = [
            'employee_id' => $employee->id,
            'employee_name' => $employee->employeeNameTh ?: $employee->employeeNameEn ?: ('ลูกจ้าง #' . $employee->id),
            'file_type' => $fileType,
            'had_value' => $hadValue,
        ];

        Log::warning("ProcessDownload: missing '{$fileType}' for employee #{$employee->id} — {$reason}");
    }

    /**
     * Human-readable summary of every recorded gap, grouped by employee —
     * written to DownloadTask::error_message even when the task otherwise
     * completes successfully, so the admin sees exactly who/what was
     * skipped instead of just getting a smaller-than-expected file.
     */
    protected function buildMissingFilesSummary(): ?string
    {
        $parts = [];

        if (!empty($this->missingFiles)) {
            $byEmployee = [];
            foreach ($this->missingFiles as $entry) {
                $byEmployee[$entry['employee_id']]['name'] = $entry['employee_name'];
                $label = $this->fileTypeLabel($entry['file_type']);
                $byEmployee[$entry['employee_id']]['items'][] = $label . ($entry['had_value'] ? ' (ไฟล์หาย)' : ' (ไม่เคยอัปโหลด)');
            }

            $lines = [];
            foreach ($byEmployee as $empId => $data) {
                $lines[] = $data['name'] . " (#{$empId}): " . implode(', ', $data['items']);
            }
            $parts[] = 'บางไฟล์ที่เลือกไม่มีอยู่จริง จึงถูกข้ามไป — ' . implode(' | ', $lines);
        }

        if (!empty($this->unreadablePdfs)) {
            $lines = [];
            foreach ($this->unreadablePdfs as $entry) {
                $emp = $entry['employee'];
                $lines[] = ($emp->employeeNameEn ?: $emp->employeeNameTh) . " (#{$emp->id}): " . $this->fileTypeLabel($entry['fileType']);
            }
            $parts[] = ($this->task && $this->task->type === 'pdf_individual')
                ? 'ไฟล์บางไฟล์รวมเข้า PDF ไม่ได้ จึงใส่ไฟล์ต้นฉบับไว้ใน ZIP แยกให้แทน — ' . implode(' | ', array_unique($lines))
                : 'ไฟล์บางไฟล์รวมเข้า PDF ไม่ได้ (รูปแบบไฟล์ที่ระบบอ่านไม่ได้) — ได้เป็น ZIP: ไฟล์ PDF รวม + ไฟล์ต้นฉบับในโฟลเดอร์ "' . self::UNMERGED_FOLDER . '" (ใน PDF มีหน้าแจ้งไว้ตรงตำแหน่งเอกสารนั้น): ' . implode(' | ', array_unique($lines));
        }

        if (!empty($this->partialPdfs)) {
            $lines = [];
            foreach ($this->partialPdfs as $entry) {
                $emp = $entry['employee'];
                $lines[] = ($emp->employeeNameEn ?: $emp->employeeNameTh) . " (#{$emp->id}): " . $this->fileTypeLabel($entry['fileType'])
                    . ($entry['page'] ? " หน้า {$entry['page']}/{$entry['of']}" : '');
            }
            $parts[] = 'บางหน้าของไฟล์ต่อไปนี้รวมเข้า PDF ไม่ได้ — กรุณาเปิดไฟล์ต้นฉบับ (ดาวน์โหลดแบบ ZIP): ' . implode(' | ', array_unique($lines));
        }

        if (!empty($this->notFoundIds)) {
            $parts[] = 'ไม่พบลูกจ้างบางคนที่เลือก (อาจถูกย้ายไปถังขยะหรือไม่มีสิทธิ์เห็น) — รหัส: ' . implode(', ', array_map(fn ($id) => '#' . $id, $this->notFoundIds));
        }

        if ($this->splitIntoParts > 1) {
            $parts[] = "ไฟล์รวมใหญ่มาก ระบบจึงแบ่งเป็น {$this->splitIntoParts} ไฟล์ PDF (ตามลำดับลูกจ้างเดิม) ไว้ใน ZIP";
        }

        return $parts ? implode(' || ', $parts) : null;
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

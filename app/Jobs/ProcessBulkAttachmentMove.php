<?php

namespace App\Jobs;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * Super Admin's "Move Attachment Files" bulk tool — operates ONLY on the
 * employee IDs the operator explicitly checkbox-selected (never "all
 * employees system-wide" like the old settings-page tool it replaces),
 * since different resolution groups/tabs can have their attachments in
 * different positions and a blanket move would corrupt unrelated groups.
 *
 * Dispatched via dispatchSync() from the controller, matching the
 * ProcessDownload job's precedent — this deployment has no guarantee a
 * queue worker is running, so a real ShouldQueue dispatch risks the
 * operation getting stuck "pending" forever. Implementing ShouldQueue
 * still keeps the door open for a real queue later without code changes.
 */
class ProcessBulkAttachmentMove implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int[] $employeeIds
     * @param string $fromField employee_doc_1..18
     * @param string $toField employee_doc_1..18
     * @param string $mode 'swap' | 'move_delete' | 'merge_append'
     */
    public function __construct(
        protected array $employeeIds,
        protected string $fromField,
        protected string $toField,
        protected string $mode,
    ) {
    }

    /**
     * @return array{moved: int, skipped: int, failed: int}
     */
    public function handle(): array
    {
        // Merging can involve real PDF work across a large batch — same
        // headroom ProcessDownload gives itself for the same reason.
        ini_set('memory_limit', '512M');
        set_time_limit(600);

        $moved = 0;
        $skipped = 0;
        $failed = 0;

        Employee::whereIn('id', $this->employeeIds)
            ->chunkById(100, function ($employees) use (&$moved, &$skipped, &$failed) {
                foreach ($employees as $employee) {
                    try {
                        $result = DB::transaction(fn () => $this->processOne($employee));
                        if ($result === 'moved') {
                            $moved++;
                        } else {
                            $skipped++;
                        }
                    } catch (Throwable $e) {
                        Log::error("Bulk attachment move failed for employee {$employee->id}: " . $e->getMessage());
                        $failed++;
                    }
                }
            });

        return ['moved' => $moved, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * @return string 'moved' or 'skipped' (nothing to do for this employee)
     */
    protected function processOne(Employee $employee): string
    {
        $fromPath = $employee->{$this->fromField};
        $toPath = $employee->{$this->toField};

        if ($this->mode === 'swap') {
            if (!$fromPath && !$toPath) {
                return 'skipped';
            }
            $employee->{$this->fromField} = $toPath;
            $employee->{$this->toField} = $fromPath;
            $employee->saveQuietly();
            return 'moved';
        }

        if (!$fromPath) {
            // Nothing to move for this employee — not an error, just N/A.
            return 'skipped';
        }

        if ($this->mode === 'move_delete') {
            if ($toPath) {
                Storage::disk('public')->delete($toPath);
            }
            $employee->{$this->toField} = $fromPath;
            $employee->{$this->fromField} = null;
            $employee->saveQuietly();
            return 'moved';
        }

        // merge_append — combine into one PDF: $toField's existing pages
        // first, then $fromField's pages appended after. If $toField is
        // currently empty, there's nothing to merge with, so it's just a
        // plain move (identical to move_delete in that case).
        if (!$toPath) {
            $employee->{$this->toField} = $fromPath;
            $employee->{$this->fromField} = null;
            $employee->saveQuietly();
            return 'moved';
        }

        $mergedPath = $this->mergeIntoOnePdf($toPath, $fromPath, $employee->id);
        if ($mergedPath === null) {
            throw new \RuntimeException("Could not merge files for employee {$employee->id} (unreadable source file).");
        }

        Storage::disk('public')->delete($toPath);
        Storage::disk('public')->delete($fromPath);
        $employee->{$this->toField} = $mergedPath;
        $employee->{$this->fromField} = null;
        $employee->saveQuietly();

        return 'moved';
    }

    /**
     * Builds one combined PDF (disk-relative path returned) containing
     * $toPath's pages followed by $fromPath's pages. Either input may be a
     * PDF or an image — images are embedded as a single full page, same
     * approach ProcessDownload already uses for its merged exports.
     */
    protected function mergeIntoOnePdf(string $toPath, string $fromPath, int $employeeId): ?string
    {
        $toAbsolute = $this->resolveAbsolutePath($toPath);
        $fromAbsolute = $this->resolveAbsolutePath($fromPath);

        if (!$toAbsolute || !$fromAbsolute) {
            return null;
        }

        $tempImages = [];

        try {
            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);

            $this->appendFileToPdf($pdf, $toAbsolute, $tempImages);
            $this->appendFileToPdf($pdf, $fromAbsolute, $tempImages);

            if ($pdf->PageNo() === 0) {
                return null;
            }

            $relativeDir = 'employee_files/' . $employeeId;
            Storage::disk('public')->makeDirectory($relativeDir);
            $filename = 'merged_' . now()->format('YmdHis') . '_' . \Illuminate\Support\Str::random(6) . '.pdf';
            $relativePath = $relativeDir . '/' . $filename;

            $pdf->Output('F', Storage::disk('public')->path($relativePath));

            return $relativePath;
        } catch (Throwable $e) {
            Log::error("PDF merge failed for employee {$employeeId}: " . $e->getMessage());
            return null;
        } finally {
            foreach ($tempImages as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Appends every page of one PDF, or one full page for an image, to the
     * FPDI document already in progress.
     */
    protected function appendFileToPdf(Fpdi $pdf, string $absolutePath, array &$tempImages): void
    {
        $mime = @mime_content_type($absolutePath);

        if ($mime === 'application/pdf') {
            $pageCount = $pdf->setSourceFile($absolutePath);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplIdx = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplIdx);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplIdx);
            }
            return;
        }

        if (str_starts_with((string) $mime, 'image/')) {
            $normalized = $this->normalizeImage($absolutePath);
            if (!$normalized) {
                return;
            }
            $tempImages[] = $normalized;

            $pdf->AddPage();
            $pageW = 210;
            $pageH = 297;
            $margin = 10;
            $writableW = $pageW - ($margin * 2);
            $writableH = $pageH - ($margin * 2);

            [$imgW, $imgH] = getimagesize($normalized);
            $ratio = $imgW / $imgH;

            if ($writableW / $writableH > $ratio) {
                $newH = $writableH;
                $newW = $writableH * $ratio;
            } else {
                $newW = $writableW;
                $newH = $writableW / $ratio;
            }

            $x = ($pageW - $newW) / 2;
            $y = ($pageH - $newH) / 2;
            $pdf->Image($normalized, $x, $y, $newW, $newH);
        }
    }

    /**
     * Same transparency-safe JPEG normalization ProcessDownload uses, so
     * PNG/GIF alpha channels don't render black in the merged PDF.
     */
    protected function normalizeImage(string $filePath): ?string
    {
        try {
            $data = file_get_contents($filePath);
            if (!$data) {
                return null;
            }

            $srcImg = @imagecreatefromstring($data);
            if (!$srcImg) {
                return null;
            }

            $width = imagesx($srcImg);
            $height = imagesy($srcImg);
            $dstImg = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($dstImg, 255, 255, 255);
            imagefilledrectangle($dstImg, 0, 0, $width, $height, $white);
            imagecopy($dstImg, $srcImg, 0, 0, 0, 0, $width, $height);

            $tempPath = tempnam(sys_get_temp_dir(), 'attach_merge_') . '.jpg';
            imagejpeg($dstImg, $tempPath, 90);

            imagedestroy($srcImg);
            imagedestroy($dstImg);

            return $tempPath;
        } catch (Throwable $e) {
            Log::error("Image normalization failed for {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    protected function resolveAbsolutePath(string $dbPath): ?string
    {
        if (Storage::disk('public')->exists($dbPath)) {
            return Storage::disk('public')->path($dbPath);
        }
        if (file_exists($dbPath)) {
            return $dbPath;
        }
        $publicPath = storage_path('app/public/' . $dbPath);
        if (file_exists($publicPath)) {
            return $publicPath;
        }
        return null;
    }
}

<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class PdfHelper
{
    /**
     * Convert an image to PDF or return PDF content directly.
     *
     * @param string $disk
     * @param string $filePath
     * @param string $disposition 'attachment' or 'inline'
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function streamFile($disk, $filePath, $disposition = 'attachment', $customFilename = null)
    {
        if (!Storage::disk($disk)->exists($filePath)) {
            abort(404, 'File not found.');
        }

        // Log PDF access (download or view/print)
        try {
            $action = ($disposition === 'inline') ? 'print' : 'download';
            $desc = ($disposition === 'inline' ? 'เปิดดู/พิมพ์' : 'ดาวน์โหลด') . ' PDF: ' . ($customFilename ?: basename($filePath));
            ActivityLogHelper::logAction($action, $desc, null, null, [
                'file' => $filePath,
                'disposition' => $disposition,
            ]);
        } catch (\Throwable $e) {
            // Don't let logging failure break file serving
        }

        $filename = $customFilename ?: basename($filePath);
        // Ensure extension
        if (!str_ends_with(strtolower($filename), '.pdf')) {
             $filename .= '.pdf';
        }
        // Sanitize filename to ensure ASCII compatibility as requested by user.
        // This prevents header encoding issues in some browsers/servers.
        // Also fix regex delimiter issue.
        $filename = preg_replace('/[^\x20-\x7E]/', '', $filename); // Remove non-ASCII
        $filename = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $filename); // Remove reserved chars
        $filename = trim($filename);
        if (empty($filename) || $filename === '.pdf') {
            $filename = 'document_' . time() . '.pdf';
        }

        $mimeType = Storage::disk($disk)->mimeType($filePath);

        // If it's already a PDF
        if ($mimeType === 'application/pdf') {
            $path = Storage::disk($disk)->path($filePath);

            if ($disposition === 'inline') {
                return response()->file($path, [
                    'Content-Type' => 'application/pdf'
                ])->setContentDisposition('inline', $filename, 'document.pdf');
            }

            // Use response()->download() instead of Storage::download() to explicitly control header encoding if needed,
            // but Storage::download() generally handles it. The issue is likely in custom headers or manual overrides.
            return response()->download($path, $filename);
        }

        // If it's an image, convert to PDF
        if (str_starts_with($mimeType, 'image/')) {
            $fullPath = Storage::disk($disk)->path($filePath);

            $pdf = new \FPDF();
            $pdf->AddPage();

            // Get image dimensions
            list($width, $height) = getimagesize($fullPath);

            // A4 size in mm
            $pdfWidth = 210;
            $pdfHeight = 297;
            $margin = 10;
            $maxWidth = $pdfWidth - (2 * $margin);
            $maxHeight = $pdfHeight - (2 * $margin);

            // Calculate Fit
            $aspectRatio = $width / $height;
            $finalW = 0;
            $finalH = 0;

            if ($maxWidth / $maxHeight > $aspectRatio) {
                // Limited by height
                $finalH = $maxHeight;
                $finalW = $maxHeight * $aspectRatio;
            } else {
                // Limited by width
                $finalW = $maxWidth;
                $finalH = $maxWidth / $aspectRatio;
            }

            $pdf->Image($fullPath, $margin, $margin, $finalW, $finalH);

            $response = response($pdf->Output('S', $filename))
                ->header('Content-Type', 'application/pdf');

            // Manually set Content-Disposition for standard Response object (Image converted to PDF)
            // Use safe ASCII filename
            $headerValue = $disposition . '; filename="' . $filename . '"';
            return $response->header('Content-Disposition', $headerValue);
        }

        // Fallback
        return response()->download(Storage::disk($disk)->path($filePath), $filename);
    }

    /**
     * Get the PDF version from the file header.
     *
     * @param string $filePath
     * @return float|null
     */
    public static function getVersion(string $filePath): ?float
    {
        if (!File::exists($filePath)) {
            return null;
        }

        // Read the first line (or first few bytes)
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return null;
        }

        $header = fgets($handle); // e.g., %PDF-1.4
        fclose($handle);

        if (preg_match('/%PDF-(\d\.\d)/', $header, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Check if the PDF version is compatible.
     *
     * @param string $filePath
     * @param float $maxVersion
     * @return bool
     */
    public static function isCompatible(string $filePath, float $maxVersion = 1.4): bool
    {
        $version = self::getVersion($filePath);
        return $version !== null && $version <= $maxVersion;
    }
}

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
     * @return \Illuminate\Http\Response
     */
    public static function streamFile($disk, $filePath)
    {
        if (!Storage::disk($disk)->exists($filePath)) {
            abort(404, 'File not found.');
        }

        $mimeType = Storage::disk($disk)->mimeType($filePath);

        // If it's already a PDF, download it directly
        if ($mimeType === 'application/pdf') {
            return Storage::disk($disk)->download($filePath);
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

            return response($pdf->Output('S', basename($filePath) . '.pdf'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . basename($filePath) . '.pdf"');
        }

        // Fallback
        return Storage::disk($disk)->download($filePath);
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

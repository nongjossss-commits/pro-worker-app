<?php
require 'vendor/autoload.php';
use setasign\Fpdi\Fpdi;

// Simulate the logic in ProcessDownload
$fontPath = __DIR__ . '/storage/fonts_test_fixed/';
if (file_exists($fontPath)) {
    // clean up first
    $files = glob($fontPath . '/*');
    foreach($files as $file) unlink($file);
    rmdir($fontPath);
}
mkdir($fontPath, 0755, true);

$standardFonts = ['helvetica.php', 'helveticab.php', 'helveticai.php', 'helveticabi.php', 'courier.php', 'times.php'];
$vendorFontPath = __DIR__ . '/vendor/setasign/fpdf/font/';

foreach ($standardFonts as $fontFile) {
    if (!file_exists($fontPath . $fontFile) && file_exists($vendorFontPath . $fontFile)) {
        echo "Copying $fontFile...\n";
        copy($vendorFontPath . $fontFile, $fontPath . $fontFile);
    }
}

// Define the constant to force FPDF to look here
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', $fontPath);
}

echo "Testing with fixed font directory: $fontPath\n";

try {
    $pdf = new Fpdi();
    $pdf->AddPage();
    // This maps to helveticab.php
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(40, 10, 'Hello World!');
    echo "Success! The fix works.\n";

    // cleanup
    $files = glob($fontPath . '/*');
    foreach($files as $file) unlink($file);
    rmdir($fontPath);

} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

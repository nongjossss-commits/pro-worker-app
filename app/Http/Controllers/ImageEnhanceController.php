<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageEnhanceController extends Controller
{
    public function enhance(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240',
            'mode' => 'nullable|in:auto,face,general',
            'upscale' => 'nullable|in:2,4',
        ]);

        $mode = $request->input('mode', 'auto');
        $upscale = $request->input('upscale', 2);

        // Save uploaded file temporarily
        $file = $request->file('image');
        $inputName = 'enhance_input_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $inputPath = $file->storeAs('temp', $inputName, 'public');
        $inputFull = Storage::disk('public')->path($inputPath);

        // Output path
        $outputName = 'enhance_output_' . Str::random(10) . '.jpg';
        $outputPath = 'temp/' . $outputName;
        $outputFull = Storage::disk('public')->path($outputPath);

        // Allow long execution for AI processing (CPU can take 2-5 minutes)
        set_time_limit(600);
        ini_set('max_execution_time', '600');

        // Resize input if too large (max 800px on longest side for CPU speed)
        $this->resizeIfNeeded($inputFull, 800);

        // Find Python executable
        $python = $this->findPython();
        if (!$python) {
            Storage::disk('public')->delete($inputPath);
            return response()->json([
                'success' => false,
                'message' => 'Python not found. Please install Python 3.8+.'
            ], 500);
        }

        // Run enhancement script
        $scriptPath = base_path('scripts/enhance_image.py');
        $command = sprintf(
            '%s %s %s %s --mode %s --upscale %d 2>&1',
            escapeshellarg($python),
            escapeshellarg($scriptPath),
            escapeshellarg($inputFull),
            escapeshellarg($outputFull),
            escapeshellarg($mode),
            (int) $upscale
        );

        $startTime = microtime(true);
        exec($command, $output, $returnCode);
        $elapsed = round(microtime(true) - $startTime, 1);

        // Clean up input
        Storage::disk('public')->delete($inputPath);

        if ($returnCode !== 0 || !file_exists($outputFull)) {
            // Clean up output if exists
            if (file_exists($outputFull)) unlink($outputFull);

            $errorMsg = implode("\n", $output);

            return response()->json([
                'success' => false,
                'message' => 'Enhancement failed.',
                'details' => $errorMsg,
            ], 500);
        }

        // Read result and return as base64
        $resultData = file_get_contents($outputFull);
        $base64 = 'data:image/jpeg;base64,' . base64_encode($resultData);

        // Clean up output
        Storage::disk('public')->delete($outputPath);

        return response()->json([
            'success' => true,
            'image' => $base64,
            'time' => $elapsed . 's',
            'log' => implode("\n", $output),
        ]);
    }

    private function findPython(): ?string
    {
        // Try common Python paths
        $candidates = ['python', 'python3', 'py'];

        // Windows-specific paths
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = array_merge($candidates, [
                getenv('LOCALAPPDATA') . '\\Programs\\Python\\Python310\\python.exe',
                getenv('LOCALAPPDATA') . '\\Programs\\Python\\Python311\\python.exe',
                getenv('LOCALAPPDATA') . '\\Programs\\Python\\Python312\\python.exe',
                'C:\\Python310\\python.exe',
                'C:\\Python311\\python.exe',
            ]);
        }

        foreach ($candidates as $cmd) {
            $check = PHP_OS_FAMILY === 'Windows'
                ? "where " . escapeshellarg($cmd) . " 2>NUL"
                : "which " . escapeshellarg($cmd) . " 2>/dev/null";

            exec($check, $out, $code);

            if ($code === 0) {
                // Verify it's Python 3
                exec(escapeshellarg($cmd) . " --version 2>&1", $verOut, $verCode);
                if ($verCode === 0 && isset($verOut[0]) && str_contains($verOut[0], 'Python 3')) {
                    return $cmd;
                }
            }
            $out = [];
        }

        return null;
    }

    private function resizeIfNeeded(string $filePath, int $maxSide): void
    {
        $info = getimagesize($filePath);
        if (!$info) return;

        [$w, $h] = $info;
        if ($w <= $maxSide && $h <= $maxSide) return;

        $scale = $maxSide / max($w, $h);
        $newW = (int) round($w * $scale);
        $newH = (int) round($h * $scale);

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => imagecreatefrompng($filePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($filePath),
            default => null,
        };

        if (!$src) return;

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagejpeg($dst, $filePath, 95);
        imagedestroy($src);
        imagedestroy($dst);
    }
}

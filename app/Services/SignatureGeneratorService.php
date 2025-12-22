<?php

namespace App\Services;

class SignatureGeneratorService
{
    /**
     * Generate a unique, consistent signature image for a given seed (Employee ID).
     *
     * @param string $seed Unique identifier (e.g., 'EMP-123')
     * @param int $width Width of the signature canvas
     * @param int $height Height of the signature canvas
     * @return string Raw image data (PNG format)
     */
    public function generate(string $seed, int $width = 300, int $height = 150): string
    {
        // Create a transparent image
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        // Seed the random number generator for consistency
        // crc32 converts string to int for seeding
        mt_srand(crc32($seed));

        // Signature Color (Dark Blue/Black simulating ink)
        // Add slight variation to color to look real
        $r = mt_rand(0, 20);
        $g = mt_rand(0, 20);
        $b = mt_rand(50, 100); // Slightly blueish like ballpoint
        $inkColor = imagecolorallocate($image, $r, $g, $b);

        // Algorithm: "Random Bezier Scribble"
        // We generate 3-5 smooth curves connected to each other to simulate cursive writing.

        $numSegments = mt_rand(3, 5);
        $points = [];

        // Start near the left-middle
        $startX = mt_rand(20, 50);
        $startY = mt_rand($height / 2 - 20, $height / 2 + 20);
        $points[] = [$startX, $startY];

        $currentX = $startX;
        $currentY = $startY;

        for ($i = 0; $i < $numSegments; $i++) {
            // Control points for Bezier
            $cp1X = $currentX + mt_rand(10, 50);
            $cp1Y = $currentY + mt_rand(-60, 60);

            $cp2X = $currentX + mt_rand(30, 70);
            $cp2Y = $currentY + mt_rand(-60, 60);

            // End point for this segment
            $endX = $currentX + mt_rand(40, 80);
            $endY = mt_rand($height / 2 - 30, $height / 2 + 30); // Keep vertically centered-ish

            // Use GD's imagefilledellipse to draw 'thick' points or implement a bezier function
            // Since GD doesn't have native bezier, we approximate it with short line segments
            $this->drawBezier($image, $inkColor, $currentX, $currentY, $cp1X, $cp1Y, $cp2X, $cp2Y, $endX, $endY);

            // Update for next segment
            $currentX = $endX;
            $currentY = $endY;
        }

        // Add a "Line" underneath (common in signatures)
        if (mt_rand(0, 1)) {
            $lineY = $currentY + mt_rand(10, 20);
            imageline($image, $startX, $lineY, $currentX, $lineY - mt_rand(0, 5), $inkColor);
        }

        // Output to buffer
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();

        imagedestroy($image);

        return $imageData;
    }

    /**
     * Draw a cubic bezier curve on the image
     */
    private function drawBezier($image, $color, $x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3)
    {
        // Resolution: how many segments to draw
        $steps = 20;

        $prevX = $x0;
        $prevY = $y0;

        for ($i = 1; $i <= $steps; $i++) {
            $t = $i / $steps;
            $u = 1 - $t;
            $tt = $t * $t;
            $uu = $u * $u;
            $uuu = $uu * $u;
            $ttt = $tt * $t;

            // Cubic Bezier Formula
            $x = $uuu * $x0 + 3 * $uu * $t * $x1 + 3 * $u * $tt * $x2 + $ttt * $x3;
            $y = $uuu * $y0 + 3 * $uu * $t * $y1 + 3 * $u * $tt * $y2 + $ttt * $y3;

            // Draw thick line by drawing adjacent lines
            imageline($image, (int)$prevX, (int)$prevY, (int)$x, (int)$y, $color);
            imageline($image, (int)$prevX, (int)$prevY+1, (int)$x, (int)$y+1, $color); // Thickness

            $prevX = $x;
            $prevY = $y;
        }
    }
}

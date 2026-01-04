<?php

namespace App\Services;

class SignatureGeneratorService
{
    /**
     * Generate a unique, consistent signature image for a given seed.
     *
     * @param string $seed Unique identifier (e.g., 'EMP-123')
     * @param int $width Width of the signature canvas
     * @param int $height Height of the signature canvas
     * @return string Raw image data (PNG format)
     */
    public function generate(string $seed, int $width = 300, int $height = 150): string
    {
        // 1. Setup Canvas
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        // 2. Seed RNG
        mt_srand(crc32($seed));

        // 3. Ink Color (Variations of Dark Blue/Black)
        $r = mt_rand(0, 30);
        $g = mt_rand(0, 30);
        $b = mt_rand(50, 120);
        $inkColor = imagecolorallocate($image, $r, $g, $b);

        // 4. Styles (To create variety across different people)
        // Style 0: Flowing (Wide curves)
        // Style 1: Compact (Tight loops)
        // Style 2: Sharp (More erratic angles)
        $style = mt_rand(0, 2);

        // Base thickness (1 to 3)
        $thickness = mt_rand(2, 3);

        // 5. Generate Points
        $points = [];

        // Start Point (Left side, random vertical offset)
        $startX = mt_rand(10, 40);
        $startY = mt_rand($height / 2 - 10, $height / 2 + 30);
        $points[] = ['x' => $startX, 'y' => $startY];

        // Number of main strokes/segments
        $numSegments = mt_rand(4, 7);

        $currentX = $startX;
        $currentY = $startY;

        for ($i = 0; $i < $numSegments; $i++) {
            // Target X moves right
            $segmentWidth = ($width - 50) / $numSegments;
            $endX = $currentX + mt_rand($segmentWidth * 0.8, $segmentWidth * 1.5);
            $endY = mt_rand($height / 2 - 40, $height / 2 + 40);

            // Control Points (Bezier) - Vary based on style
            $cp1X = $currentX + mt_rand(10, 50);
            $cp2X = $endX - mt_rand(10, 50);

            if ($style === 1) { // Compact loops
                $cp1Y = $currentY + mt_rand(-100, 100);
                $cp2Y = $endY + mt_rand(-100, 100);
                // Occasionally loop back
                if (mt_rand(0, 2) === 0) {
                   $cp1X -= 30;
                }
            } elseif ($style === 2) { // Sharp
                 $cp1Y = $currentY + mt_rand(-60, 60);
                 $cp2Y = $endY + mt_rand(-60, 60);
            } else { // Flowing
                 $cp1Y = $currentY + mt_rand(-30, 30);
                 $cp2Y = $endY + mt_rand(-30, 30);
            }

            // Draw Segment
            $this->drawBezier($image, $inkColor, $currentX, $currentY, $cp1X, $cp1Y, $cp2X, $cp2Y, $endX, $endY, $thickness);

            $currentX = $endX;
            $currentY = $endY;
        }

        // 6. Optional: Initial Loop (The "Circle" start)
        if (mt_rand(0, 1)) {
            $this->drawOval($image, $inkColor, $startX, $startY, mt_rand(15, 30), mt_rand(10, 25));
        }

        // 7. Optional: Underline or Strike-through
        if (mt_rand(0, 3) > 0) { // 75% chance
            $lineY = $currentY + mt_rand(10, 30);
            $lineXEnd = $currentX + mt_rand(-20, 20);
            // Simple swooping underline
             $this->drawBezier($image, $inkColor,
                $startX, $lineY,
                $startX + 50, $lineY + 10,
                $lineXEnd - 50, $lineY + 5,
                $lineXEnd, $lineY,
                $thickness - 1
            );
        }

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }

    private function drawBezier($image, $color, $x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $thickness = 1)
    {
        $steps = 40; // Smoother
        $prevX = $x0;
        $prevY = $y0;

        for ($i = 1; $i <= $steps; $i++) {
            $t = $i / $steps;
            $u = 1 - $t;
            $tt = $t * $t;
            $uu = $u * $u;
            $uuu = $uu * $u;
            $ttt = $tt * $t;

            $x = $uuu * $x0 + 3 * $uu * $t * $x1 + 3 * $u * $tt * $x2 + $ttt * $x3;
            $y = $uuu * $y0 + 3 * $uu * $t * $y1 + 3 * $u * $tt * $y2 + $ttt * $y3;

            // Draw with thickness
            $this->drawThickLine($image, $prevX, $prevY, $x, $y, $color, $thickness);

            $prevX = $x;
            $prevY = $y;
        }
    }

    private function drawThickLine($image, $x1, $y1, $x2, $y2, $color, $thickness) {
        if ($thickness <= 1) {
            imageline($image, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $color);
            return;
        }

        // Simple thickness by offsetting
        // ideally we would calculate perpendicular vector, but for signatures simple offsets work ok
        for ($i = -floor($thickness/2); $i <= floor($thickness/2); $i++) {
             for ($j = -floor($thickness/2); $j <= floor($thickness/2); $j++) {
                imageline($image, (int)$x1+$i, (int)$y1+$j, (int)$x2+$i, (int)$y2+$j, $color);
             }
        }
    }

    private function drawOval($image, $color, $cx, $cy, $w, $h) {
        // Approximate oval with 4 bezier curves or just use imageellipse if available (it is)
        // But we want it 'hand drawn' look, so maybe slightly broken oval
        imageellipse($image, $cx, $cy, $w, $h, $color);
        // Retrace with slight offset for thickness
        imageellipse($image, $cx, $cy, $w+1, $h+1, $color);
    }
}

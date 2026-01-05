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
        $r = mt_rand(0, 40);
        $g = mt_rand(0, 40);
        $b = mt_rand(60, 140);
        $inkColor = imagecolorallocate($image, $r, $g, $b);

        // 4. Styles Selection
        // Expanded styles to mimic human variation more closely
        // 0: Flowing (Standard curves)
        // 1: Compact (Tight loops)
        // 2: Sharp (Erratic/Doctor style)
        // 3: Cursive-like (Simulate handwriting with connected loops)
        // 4: Big Loops (Large, expressive circular motions)
        // 5: Horizontal Dash (Lazy signer)
        $style = mt_rand(0, 5);

        // Base thickness (Varied slightly more)
        $thickness = mt_rand(2, 4);

        // 5. Generate Content based on Style
        switch ($style) {
            case 3: // Cursive-like
                $this->generateCursiveStyle($image, $inkColor, $width, $height, $thickness);
                break;
            case 4: // Big Loops
                $this->generateBigLoopsStyle($image, $inkColor, $width, $height, $thickness);
                break;
            case 5: // Horizontal Dash / Lazy
                $this->generateLazyStyle($image, $inkColor, $width, $height, $thickness);
                break;
            case 2: // Sharp
                $this->generateSharpStyle($image, $inkColor, $width, $height, $thickness);
                break;
            default: // 0 & 1 & fallback
                $this->generateStandardStyle($image, $inkColor, $width, $height, $thickness, $style);
                break;
        }

        // 6. Common Embellishments (Underline / Strike / Dots)
        $this->addEmbellishments($image, $inkColor, $width, $height, $thickness);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }

    /**
     * Style: Cursive-like (Simulates connected handwriting)
     */
    private function generateCursiveStyle($image, $color, $width, $height, $thickness)
    {
        $startX = mt_rand(20, 50);
        $startY = mt_rand($height / 2, $height / 2 + 20);

        $currentX = $startX;
        $currentY = $startY;

        // Number of "letters" or humps
        $letters = mt_rand(5, 12);

        for ($i = 0; $i < $letters; $i++) {
            // Determine "letter" type: 0=hump(m/n), 1=loop_high(l/h), 2=loop_low(g/y/j), 3=sharp(t/i)
            $type = mt_rand(0, 3);
            $widthStep = mt_rand(15, 30);

            $endX = $currentX + $widthStep;
            $endY = $startY + mt_rand(-5, 5); // Return mostly to baseline

            // Control points depend on letter type
            $cp1X = $currentX + 5;
            $cp2X = $endX - 5;

            if ($type == 0) { // Hump (m/n)
                $cp1Y = $currentY - mt_rand(15, 25);
                $cp2Y = $endY - mt_rand(15, 25);
            } elseif ($type == 1) { // High loop (l/k/h)
                $cp1Y = $currentY - mt_rand(40, 60);
                $cp2Y = $endY - mt_rand(10, 30);
                // Shift CP1 back to create loop
                $cp1X -= 10;
            } elseif ($type == 2) { // Low loop (g/y)
                $cp1Y = $currentY + mt_rand(30, 60);
                $cp2Y = $endY + mt_rand(30, 60);
                $cp1X += 10; // Wide bottom
            } else { // Sharp/Small (i/e)
                $cp1Y = $currentY - mt_rand(10, 15);
                $cp2Y = $endY - mt_rand(10, 15);
            }

            $this->drawBezier($image, $color, $currentX, $currentY, $cp1X, $cp1Y, $cp2X, $cp2Y, $endX, $endY, $thickness);

            $currentX = $endX;
            $currentY = $endY;
        }
    }

    /**
     * Style: Big Loops (Large expressive circles)
     */
    private function generateBigLoopsStyle($image, $color, $width, $height, $thickness)
    {
        $cx = mt_rand(40, 60);
        $cy = $height / 2;

        $numLoops = mt_rand(3, 5);

        $currentX = $cx;
        $currentY = $cy;

        for ($i = 0; $i < $numLoops; $i++) {
            $nextX = $currentX + mt_rand(40, 70);
            $nextY = $cy + mt_rand(-10, 10);

            // Large circular control points
            $cp1X = $currentX + mt_rand(10, 30);
            $cp1Y = $currentY - mt_rand(40, 80); // Go high

            $cp2X = $nextX - mt_rand(10, 30);
            $cp2Y = $nextY + mt_rand(40, 80); // Go low (creating spiral effect)

            if ($i % 2 == 0) {
                 // Swap Y polarity for alternating loops
                 $temp = $cp1Y;
                 $cp1Y = $currentY + mt_rand(40, 80);
                 $cp2Y = $nextY - mt_rand(40, 80);
            }

            $this->drawBezier($image, $color, $currentX, $currentY, $cp1X, $cp1Y, $cp2X, $cp2Y, $nextX, $nextY, $thickness);

            $currentX = $nextX;
            $currentY = $nextY;
        }
    }

    /**
     * Style: Sharp / Jagged
     */
    private function generateSharpStyle($image, $color, $width, $height, $thickness)
    {
        $points = [];
        $startX = mt_rand(20, 40);
        $startY = mt_rand($height/2 - 10, $height/2 + 10);

        $currentX = $startX;
        $currentY = $startY;

        $segments = mt_rand(8, 15);

        for ($i = 0; $i < $segments; $i++) {
            $nextX = $currentX + mt_rand(10, 30);
            $nextY = $startY + mt_rand(-30, 30); // Wild vertical variance

            // Almost straight lines (control points near the line)
            $this->drawBezier($image, $color,
                $currentX, $currentY,
                $currentX + 5, $currentY + mt_rand(-5, 5),
                $nextX - 5, $nextY + mt_rand(-5, 5),
                $nextX, $nextY,
                $thickness
            );

            $currentX = $nextX;
            $currentY = $nextY;
        }
    }

    /**
     * Style: Lazy / Horizontal Dash
     */
    private function generateLazyStyle($image, $color, $width, $height, $thickness)
    {
        $startX = mt_rand(30, 50);
        $startY = $height / 2 + mt_rand(-10, 10);
        $endX = $width - mt_rand(30, 60);
        $endY = $startY + mt_rand(-10, 10);

        // Just one or two long swoops
        $midX = ($startX + $endX) / 2;

        $this->drawBezier($image, $color,
            $startX, $startY,
            $midX, $startY - mt_rand(10, 30), // slight arc up
            $midX, $startY + mt_rand(10, 30), // or down
            $endX, $endY,
            $thickness
        );

        // Maybe a small dot or dash at the end
        if (mt_rand(0, 1)) {
             $this->drawOval($image, $color, $endX + 10, $endY, 3, 3);
        }
    }

    /**
     * Style: Standard / Flowing / Compact (Refined Original)
     */
    private function generateStandardStyle($image, $color, $width, $height, $thickness, $subStyle)
    {
        $startX = mt_rand(10, 40);
        $startY = mt_rand($height / 2 - 10, $height / 2 + 30);

        $numSegments = mt_rand(4, 7);
        $currentX = $startX;
        $currentY = $startY;

        for ($i = 0; $i < $numSegments; $i++) {
            $segmentWidth = ($width - 50) / $numSegments;
            $endX = $currentX + mt_rand($segmentWidth * 0.8, $segmentWidth * 1.5);
            $endY = mt_rand($height / 2 - 40, $height / 2 + 40);

            $cp1X = $currentX + mt_rand(10, 50);
            $cp2X = $endX - mt_rand(10, 50);

            if ($subStyle === 1) { // Compact loops
                $cp1Y = $currentY + mt_rand(-100, 100);
                $cp2Y = $endY + mt_rand(-100, 100);
                if (mt_rand(0, 2) === 0) $cp1X -= 30;
            } else { // Flowing
                 $cp1Y = $currentY + mt_rand(-40, 40);
                 $cp2Y = $endY + mt_rand(-40, 40);
            }

            $this->drawBezier($image, $color, $currentX, $currentY, $cp1X, $cp1Y, $cp2X, $cp2Y, $endX, $endY, $thickness);

            $currentX = $endX;
            $currentY = $endY;
        }
    }

    /**
     * Add underlines, strikethroughs, or dots
     */
    private function addEmbellishments($image, $color, $width, $height, $thickness)
    {
        // 1. Initial Loop (The "Circle" start) - 40% chance
        if (mt_rand(0, 10) < 4) {
            $this->drawOval($image, $color, mt_rand(30, 60), mt_rand($height/2 - 20, $height/2 + 20), mt_rand(15, 30), mt_rand(15, 30));
        }

        // 2. Underline - 60% chance
        if (mt_rand(0, 10) < 6) {
            $lineY = mt_rand($height/2 + 20, $height - 20);
            $startX = mt_rand(20, 50);
            $endX = $width - mt_rand(20, 60);

            $this->drawBezier($image, $color,
                $startX, $lineY,
                $startX + 50, $lineY + 10,
                $endX - 50, $lineY + 5,
                $endX, $lineY - 5,
                max(1, $thickness - 1)
            );
        }
    }

    private function drawBezier($image, $color, $x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $thickness = 1)
    {
        $steps = 50;
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

        // Simulated thickness by drawing multiple lines
        for ($i = -floor($thickness/2); $i <= floor($thickness/2); $i++) {
             imageline($image, (int)$x1+$i, (int)$y1+$i, (int)$x2+$i, (int)$y2+$i, $color);
             imageline($image, (int)$x1+$i, (int)$y1-$i, (int)$x2+$i, (int)$y2-$i, $color);
        }
    }

    private function drawOval($image, $color, $cx, $cy, $w, $h) {
        imageellipse($image, (int)$cx, (int)$cy, (int)$w, (int)$h, $color);
        imageellipse($image, (int)$cx, (int)$cy, (int)$w+1, (int)$h+1, $color);
    }
}

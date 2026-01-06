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

        // 2. Seed RNG for consistency
        // Using a combination of crc32 and basic string hashing to ensure good distribution
        mt_srand(crc32($seed) + strlen($seed));

        // 3. Ink Color (Variations of Dark Blue/Black/Grey)
        // More human-like ink colors
        $colorType = mt_rand(0, 10);
        if ($colorType < 6) {
            // Standard Blue Ballpoint
            $r = mt_rand(0, 20);
            $g = mt_rand(0, 20);
            $b = mt_rand(100, 180);
        } elseif ($colorType < 9) {
            // Black Ink
            $r = mt_rand(0, 40);
            $g = mt_rand(0, 40);
            $b = mt_rand(0, 40);
        } else {
            // Faded Black/Grey
            $val = mt_rand(40, 80);
            $r = $val; $g = $val; $b = $val + mt_rand(0, 10);
        }
        $inkColor = imagecolorallocate($image, $r, $g, $b);

        // 4. Base Parameters
        $thickness = mt_rand(2, 4);
        // Slant factor: -0.2 (left) to 0.8 (right)
        $slant = (mt_rand(-20, 80) / 100);

        // 5. Select Base Algorithm (Expanded to ~20 logical variations via params)
        // We define 5 "Core Engines" and feed them different parameters to create 20+ distinct styles.
        $engine = mt_rand(0, 4);

        switch ($engine) {
            case 0: // The "Looper" (Cursive, Circular, Big loops)
                $this->engineLooper($image, $inkColor, $width, $height, $thickness, $slant);
                break;
            case 1: // The "Spike" (Sharp, Aggressive, Doctor-like)
                $this->engineSpike($image, $inkColor, $width, $height, $thickness, $slant);
                break;
            case 2: // The "Block" (Disconnected, Initials-heavy)
                $this->engineBlock($image, $inkColor, $width, $height, $thickness, $slant);
                break;
            case 3: // The "Wave" (Lazy, Horizontal, Flowing)
                $this->engineWave($image, $inkColor, $width, $height, $thickness, $slant);
                break;
            case 4: // The "Abstract" (Scribble, Density)
                $this->engineAbstract($image, $inkColor, $width, $height, $thickness, $slant);
                break;
        }

        // 6. Global Embellishments (Underlines, Dots, Strike-throughs)
        $this->addEmbellishments($image, $inkColor, $width, $height, $thickness);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }

    // ==========================================
    // ENGINE 0: THE LOOPER (Cursive / Circular)
    // ==========================================
    private function engineLooper($image, $color, $width, $height, $thickness, $slant)
    {
        // Sub-styles via parameters
        // 0: Tight Cursive
        // 1: Big Circular (John Hancock style)
        // 2: Vertical Loops
        // 3: Messy Loops
        $subStyle = mt_rand(0, 3);

        $startX = mt_rand(20, 60);
        $startY = $height / 2 + mt_rand(-10, 10);

        $segments = mt_rand(6, 14);
        $currentX = $startX;
        $currentY = $startY;

        for ($i = 0; $i < $segments; $i++) {
            $stepW = mt_rand(15, 35);
            $endX = $currentX + $stepW;
            $endY = $startY + mt_rand(-5, 5); // Return to baseline mostly

            // Control Points define the loop shape
            $cp1X = $currentX + ($stepW * 0.2) + ($slant * 20);
            $cp2X = $endX - ($stepW * 0.2) + ($slant * 20);

            if ($subStyle === 1) { // Big Circular
                $amp = mt_rand(40, 90);
                $cp1Y = $currentY - $amp;
                $cp2Y = $endY - $amp;
                // Occasional downward loop
                if (mt_rand(0,3) === 0) { $cp1Y = $currentY + $amp; $cp2Y = $endY + $amp; }
            } elseif ($subStyle === 2) { // Vertical Loops
                $amp = mt_rand(30, 60);
                $cp1Y = $currentY - $amp;
                $cp2Y = $endY + $amp; // S-shape
            } else { // Standard Cursive
                $amp = mt_rand(15, 40);
                $cp1Y = $currentY - $amp;
                $cp2Y = $endY - $amp;
            }

            // Jitter for "Messy" substyle
            if ($subStyle === 3) {
                $cp1X += mt_rand(-10, 10);
                $cp2Y += mt_rand(-10, 10);
            }

            $this->drawBezier($image, $color, $currentX, $currentY, $cp1X, $cp1Y, $cp2X, $cp2Y, $endX, $endY, $thickness);

            $currentX = $endX;
            $currentY = $endY;
        }
    }

    // ==========================================
    // ENGINE 1: THE SPIKE (Sharp / Jagged)
    // ==========================================
    private function engineSpike($image, $color, $width, $height, $thickness, $slant)
    {
        // Sub-styles
        // 0: Seismograph (Up/Down constant)
        // 1: Sawtooth (Ramp up, drop down)
        // 2: Nervous (Small jitters)
        $subStyle = mt_rand(0, 2);

        $startX = mt_rand(20, 50);
        $startY = $height / 2;
        $currentX = $startX;
        $currentY = $startY;

        $segments = mt_rand(10, 20);

        for ($i = 0; $i < $segments; $i++) {
            $stepW = mt_rand(10, 25);
            $nextX = $currentX + $stepW;

            if ($subStyle === 0) {
                $nextY = $startY + mt_rand(-30, 30);
            } elseif ($subStyle === 1) {
                $nextY = $startY - mt_rand(0, 40); // Only goes up
            } else {
                $nextY = $startY + mt_rand(-10, 10); // Small jitter
            }

            // Slant affects X coordinate of peak
            $peakX = $nextX + ($slant * 10);

            // Draw line
            $this->drawThickLine($image, $currentX, $currentY, $nextX, $nextY, $color, $thickness);

            // If Sawtooth, drop back down immediately
            if ($subStyle === 1) {
                $this->drawThickLine($image, $nextX, $nextY, $nextX + 2, $startY, $color, $thickness);
                $currentX = $nextX + 2;
                $currentY = $startY;
            } else {
                $currentX = $nextX;
                $currentY = $nextY;
            }
        }
    }

    // ==========================================
    // ENGINE 2: THE BLOCK (Initials / Disconnected)
    // ==========================================
    private function engineBlock($image, $color, $width, $height, $thickness, $slant)
    {
        $startX = mt_rand(30, 60);
        $centerY = $height / 2;
        $currentX = $startX;

        // Number of "Letters"
        $count = mt_rand(2, 4); // Usually fewer, distinct shapes

        for ($i = 0; $i < $count; $i++) {
            // Draw a "Glyph"
            $w = mt_rand(20, 40);
            $h = mt_rand(30, 60);

            // Top/Bottom bounds
            $top = $centerY - ($h/2);
            $bottom = $centerY + ($h/2);
            $left = $currentX;
            $right = $currentX + $w;

            // Apply slant
            $topX = $left + ($slant * 10);
            $bottomX = $left - ($slant * 10);

            // Randomize Glyph shape (Lines, Arcs, Boxes)
            $shape = mt_rand(0, 4);

            if ($shape === 0) { // 'L' shape
                $this->drawThickLine($image, $topX, $top, $bottomX, $bottom, $color, $thickness);
                $this->drawThickLine($image, $bottomX, $bottom, $right, $bottom, $color, $thickness);
            } elseif ($shape === 1) { // 'O' shape (approx)
                $this->drawOval($image, $color, $left + $w/2, $centerY, $w, $h);
            } elseif ($shape === 2) { // 'X' shape
                $this->drawThickLine($image, $topX, $top, $right, $bottom, $color, $thickness);
                $this->drawThickLine($image, $topX, $bottom, $right, $top, $color, $thickness);
            } elseif ($shape === 3) { // 'I' shape
                $this->drawThickLine($image, $left + $w/2, $top, $left + $w/2, $bottom, $color, $thickness);
            } else { // ZigZag
                 $this->drawThickLine($image, $left, $bottom, $left + $w/2, $top, $color, $thickness);
                 $this->drawThickLine($image, $left + $w/2, $top, $right, $bottom, $color, $thickness);
            }

            $currentX += $w + mt_rand(10, 20); // Gap between blocks
        }
    }

    // ==========================================
    // ENGINE 3: THE WAVE (Lazy / Horizontal)
    // ==========================================
    private function engineWave($image, $color, $width, $height, $thickness, $slant)
    {
        $startX = mt_rand(20, 50);
        $startY = $height / 2;
        $endX = $width - mt_rand(30, 60);

        // Single complex bezier or multi-connected shallow curves

        $midX1 = $startX + ($endX - $startX) * 0.33;
        $midX2 = $startX + ($endX - $startX) * 0.66;

        $amp = mt_rand(10, 30);

        // Control points
        $cp1y = $startY - $amp;
        $cp2y = $startY + $amp;
        $cp3y = $startY - $amp;
        $cp4y = $startY + $amp;

        // Draw in 2 stages
        $this->drawBezier($image, $color,
            $startX, $startY,
            $startX + 20, $cp1y,
            $midX1 - 20, $cp2y,
            $midX1, $startY,
            $thickness
        );

        $this->drawBezier($image, $color,
            $midX1, $startY,
            $midX1 + 20, $cp3y,
            $endX - 20, $cp4y,
            $endX, $startY,
            $thickness
        );

        // Optional trailing line
        if (mt_rand(0, 1)) {
             $this->drawThickLine($image, $endX, $startY, $endX + mt_rand(20, 40), $startY - mt_rand(5, 10), $color, 1);
        }
    }

    // ==========================================
    // ENGINE 4: THE ABSTRACT (Scribble)
    // ==========================================
    private function engineAbstract($image, $color, $width, $height, $thickness, $slant)
    {
        $cx = $width / 2;
        $cy = $height / 2;

        // Density
        $lines = mt_rand(5, 12);

        $prevX = $cx - 50;
        $prevY = $cy;

        for ($i = 0; $i < $lines; $i++) {
            $dx = mt_rand(10, 60);
            $newX = $prevX + $dx;

            // Random Y mostly centered but chaotic
            $newY = $cy + mt_rand(-30, 30);

            // Random loop or line?
            if (mt_rand(0, 1)) {
                // Draw Loop
                $cpX = ($prevX + $newX) / 2;
                $cpY = $cy + mt_rand(-60, 60); // High loop

                $this->drawBezier($image, $color, $prevX, $prevY, $prevX + 10, $cpY, $newX - 10, $cpY, $newX, $newY, $thickness);
            } else {
                // Straight Scratch
                $this->drawThickLine($image, $prevX, $prevY, $newX, $newY, $color, $thickness);
            }

            $prevX = $newX;
            $prevY = $newY;

            // Backtrack check (scribble back)
            if (mt_rand(0, 3) === 0) {
                $backX = $prevX - mt_rand(10, 30);
                $this->drawThickLine($image, $prevX, $prevY, $backX, $prevY + mt_rand(-10, 10), $color, 1);
                $prevX = $backX; // Continue from back
            }
        }
    }

    // ==========================================
    // HELPERS & EMBELLISHMENTS
    // ==========================================

    private function addEmbellishments($image, $color, $width, $height, $thickness)
    {
        // 1. Underline (70% chance)
        if (mt_rand(0, 100) < 70) {
            $lineY = $height - mt_rand(20, 40);
            $startX = mt_rand(20, 50);
            $endX = $width - mt_rand(30, 60);

            // Single or Double?
            $double = (mt_rand(0, 10) > 8);

            $this->drawBezier($image, $color,
                $startX, $lineY,
                $startX + 50, $lineY + 5,
                $endX - 50, $lineY + 5,
                $endX, $lineY - 5,
                $thickness
            );

            if ($double) {
                 $this->drawBezier($image, $color,
                    $startX + 10, $lineY + 8,
                    $startX + 60, $lineY + 13,
                    $endX - 40, $lineY + 13,
                    $endX - 10, $lineY + 3,
                    max(1, $thickness - 1)
                );
            }
        }

        // 2. Dots (20% chance)
        if (mt_rand(0, 100) < 20) {
             $this->drawOval($image, $color, $width - mt_rand(20, 50), $height/2 + mt_rand(-10, 20), 4, 4);
             if (mt_rand(0, 1)) {
                 $this->drawOval($image, $color, $width - mt_rand(50, 80), $height/2 + mt_rand(-10, 20), 4, 4);
             }
        }

        // 3. Strikethrough (5% chance - very rare)
        if (mt_rand(0, 100) < 5) {
             $this->drawThickLine($image, 40, $height/2, $width-40, $height/2, $color, 1);
        }
    }

    private function drawBezier($image, $color, $x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $thickness = 1)
    {
        $steps = 40; // Reduced slightly for performance
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

        // Simple parallel line simulation
        // Calculates perpendicular vector for better thickness would be ideal,
        // but for signatures, simple offset works and looks more "inky"
        for ($i = -floor($thickness/2); $i <= floor($thickness/2); $i++) {
             // Offset X and Y slightly
             imageline($image, (int)$x1+$i, (int)$y1+$i, (int)$x2+$i, (int)$y2+$i, $color);
             imageline($image, (int)$x1+$i, (int)$y1, (int)$x2+$i, (int)$y2, $color); // Fill holes
        }
    }

    private function drawOval($image, $color, $cx, $cy, $w, $h) {
        imageellipse($image, (int)$cx, (int)$cy, (int)$w, (int)$h, $color);
        // Fill it slightly
        imageellipse($image, (int)$cx, (int)$cy, (int)$w-1, (int)$h-1, $color);
        imagefilledellipse($image, (int)$cx, (int)$cy, (int)$w/2, (int)$h/2, $color);
    }
}

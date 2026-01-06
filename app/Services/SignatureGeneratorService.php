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
            $r = mt_rand(0, 30);
            $g = mt_rand(0, 30);
            $b = mt_rand(100, 180);
        } elseif ($colorType < 9) {
            // Black Ink
            $r = mt_rand(0, 50);
            $g = mt_rand(0, 50);
            $b = mt_rand(0, 50);
        } else {
            // Faded Black/Grey (Old pen)
            $val = mt_rand(60, 100);
            $r = $val; $g = $val; $b = $val + mt_rand(0, 10);
        }
        $inkColor = imagecolorallocate($image, $r, $g, $b);

        // 4. Base Parameters
        $thickness = mt_rand(2, 4);
        // Slant factor: -0.2 (left) to 0.8 (right)
        $slant = (mt_rand(-20, 80) / 100);

        // 5. Select Base Algorithm (Expanded to 10 Engines with ~10 sub-variations each = ~100 styles)
        $engine = mt_rand(0, 9);

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
            case 5: // The "Minimalist" (Short, Simple, Few strokes)
                $this->engineMinimalist($image, $inkColor, $width, $height, $thickness, $slant);
                break;
            case 6: // The "Architect" (Geometric, Straight, Structured)
                $this->engineArchitect($image, $inkColor, $width, $height, $thickness, $slant);
                break;
            case 7: // The "Childish" (Wobbly, Large, Unsteady)
                $this->engineChildish($image, $inkColor, $width, $height, $thickness, $slant);
                break;
            case 8: // The "Compact" (Tight, Dense, Small)
                $this->engineCompact($image, $inkColor, $width, $height, $thickness, $slant);
                break;
            case 9: // The "Wild" (Big range, crossing lines)
                $this->engineWild($image, $inkColor, $width, $height, $thickness, $slant);
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
        $subStyle = mt_rand(0, 5); // Expanded sub-styles
        $startX = mt_rand(20, 60);
        $startY = $height / 2 + mt_rand(-10, 10);

        $segments = mt_rand(6, 14);
        $currentX = $startX;
        $currentY = $startY;

        for ($i = 0; $i < $segments; $i++) {
            $stepW = mt_rand(15, 35);
            $endX = $currentX + $stepW;
            $endY = $startY + mt_rand(-5, 5);

            $cp1X = $currentX + ($stepW * 0.2) + ($slant * 20);
            $cp2X = $endX - ($stepW * 0.2) + ($slant * 20);

            if ($subStyle === 0) { // Tight Cursive
                 $amp = mt_rand(15, 40);
                 $cp1Y = $currentY - $amp;
                 $cp2Y = $endY - $amp;
            } elseif ($subStyle === 1) { // Big Circular
                $amp = mt_rand(40, 90);
                $cp1Y = $currentY - $amp;
                $cp2Y = $endY - $amp;
                if (mt_rand(0,3) === 0) { $cp1Y = $currentY + $amp; $cp2Y = $endY + $amp; }
            } elseif ($subStyle === 2) { // Vertical Loops
                $amp = mt_rand(30, 60);
                $cp1Y = $currentY - $amp;
                $cp2Y = $endY + $amp;
            } elseif ($subStyle === 3) { // Messy
                $amp = mt_rand(20, 50);
                $cp1Y = $currentY - $amp + mt_rand(-10, 10);
                $cp2Y = $endY - $amp + mt_rand(-10, 10);
            } elseif ($subStyle === 4) { // Flat top
                $amp = mt_rand(10, 20);
                $cp1Y = $currentY - $amp;
                $cp2Y = $endY - $amp;
            } else { // Rollercoaster
                $amp = mt_rand(20, 60);
                $dir = ($i % 2 === 0) ? -1 : 1;
                $cp1Y = $currentY + ($amp * $dir);
                $cp2Y = $endY + ($amp * $dir);
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
        $subStyle = mt_rand(0, 4);
        $startX = mt_rand(20, 50);
        $startY = $height / 2;
        $currentX = $startX;
        $currentY = $startY;

        $segments = mt_rand(10, 20);

        for ($i = 0; $i < $segments; $i++) {
            $stepW = mt_rand(10, 25);
            $nextX = $currentX + $stepW;

            if ($subStyle === 0) { // Seismograph
                $nextY = $startY + mt_rand(-30, 30);
            } elseif ($subStyle === 1) { // Sawtooth (Up only)
                $nextY = $startY - mt_rand(10, 50);
            } elseif ($subStyle === 2) { // Nervous (Small jitter)
                $nextY = $startY + mt_rand(-10, 10);
            } elseif ($subStyle === 3) { // Stalactite (Down only)
                $nextY = $startY + mt_rand(10, 50);
            } else { // Mixed extreme
                $nextY = $startY + mt_rand(-50, 50);
            }

            $peakX = $nextX + ($slant * 10);

            $this->drawThickLine($image, $currentX, $currentY, $nextX, $nextY, $color, $thickness);

            if ($subStyle === 1 || $subStyle === 3) {
                // Drop/Raise back to base logic
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
        $count = mt_rand(2, 4);

        for ($i = 0; $i < $count; $i++) {
            $w = mt_rand(20, 40);
            $h = mt_rand(30, 60);
            $top = $centerY - ($h/2);
            $bottom = $centerY + ($h/2);
            $left = $currentX;
            $right = $currentX + $w;
            $topX = $left + ($slant * 10);
            $bottomX = $left - ($slant * 10);

            $shape = mt_rand(0, 6); // More shapes

            if ($shape === 0) { // L
                $this->drawThickLine($image, $topX, $top, $bottomX, $bottom, $color, $thickness);
                $this->drawThickLine($image, $bottomX, $bottom, $right, $bottom, $color, $thickness);
            } elseif ($shape === 1) { // O
                $this->drawOval($image, $color, $left + $w/2, $centerY, $w, $h);
            } elseif ($shape === 2) { // X
                $this->drawThickLine($image, $topX, $top, $right, $bottom, $color, $thickness);
                $this->drawThickLine($image, $topX, $bottom, $right, $top, $color, $thickness);
            } elseif ($shape === 3) { // I
                $this->drawThickLine($image, $left + $w/2, $top, $left + $w/2, $bottom, $color, $thickness);
            } elseif ($shape === 4) { // Z
                 $this->drawThickLine($image, $left, $top, $right, $top, $color, $thickness);
                 $this->drawThickLine($image, $right, $top, $left, $bottom, $color, $thickness);
                 $this->drawThickLine($image, $left, $bottom, $right, $bottom, $color, $thickness);
            } elseif ($shape === 5) { // V
                 $this->drawThickLine($image, $left, $top, $left + $w/2, $bottom, $color, $thickness);
                 $this->drawThickLine($image, $left + $w/2, $bottom, $right, $top, $color, $thickness);
            } else { // ZigZag
                 $this->drawThickLine($image, $left, $bottom, $left + $w/2, $top, $color, $thickness);
                 $this->drawThickLine($image, $left + $w/2, $top, $right, $bottom, $color, $thickness);
            }
            $currentX += $w + mt_rand(10, 20);
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
        $midX1 = $startX + ($endX - $startX) * 0.33;
        $midX2 = $startX + ($endX - $startX) * 0.66;
        $amp = mt_rand(10, 30);

        $style = mt_rand(0, 2);

        if ($style === 0) { // Classic Sine
            $cp1y = $startY - $amp; $cp2y = $startY + $amp;
            $cp3y = $startY - $amp; $cp4y = $startY + $amp;
        } elseif ($style === 1) { // M-shape
            $cp1y = $startY - $amp * 2; $cp2y = $startY - $amp * 2;
            $cp3y = $startY - $amp * 2; $cp4y = $startY - $amp * 2;
        } else { // U-shape
            $cp1y = $startY + $amp * 2; $cp2y = $startY + $amp * 2;
            $cp3y = $startY + $amp * 2; $cp4y = $startY + $amp * 2;
        }

        $this->drawBezier($image, $color, $startX, $startY, $startX + 20, $cp1y, $midX1 - 20, $cp2y, $midX1, $startY, $thickness);
        $this->drawBezier($image, $color, $midX1, $startY, $midX1 + 20, $cp3y, $endX - 20, $cp4y, $endX, $startY, $thickness);

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
        $lines = mt_rand(5, 15); // Varied density
        $prevX = $cx - mt_rand(40, 60);
        $prevY = $cy;

        for ($i = 0; $i < $lines; $i++) {
            $dx = mt_rand(10, 60);
            $newX = $prevX + $dx;
            $newY = $cy + mt_rand(-30, 30);

            if (mt_rand(0, 2) === 0) { // Loop
                $cpX = ($prevX + $newX) / 2;
                $cpY = $cy + mt_rand(-60, 60);
                $this->drawBezier($image, $color, $prevX, $prevY, $prevX + 10, $cpY, $newX - 10, $cpY, $newX, $newY, $thickness);
            } else { // Line
                $this->drawThickLine($image, $prevX, $prevY, $newX, $newY, $color, $thickness);
            }

            $prevX = $newX;
            $prevY = $newY;

            if (mt_rand(0, 3) === 0) { // Backtrack
                $backX = $prevX - mt_rand(10, 40);
                $this->drawThickLine($image, $prevX, $prevY, $backX, $prevY + mt_rand(-10, 10), $color, 1);
                $prevX = $backX;
            }
        }
    }

    // ==========================================
    // ENGINE 5: THE MINIMALIST (Simple, Few Strokes)
    // ==========================================
    private function engineMinimalist($image, $color, $width, $height, $thickness, $slant)
    {
        $startX = mt_rand(40, 60);
        $startY = $height / 2;

        // Just 2 or 3 strokes
        $strokes = mt_rand(2, 3);

        $currX = $startX;
        for ($i = 0; $i < $strokes; $i++) {
            $len = mt_rand(30, 60);
            $endX = $currX + $len;
            $endY = $startY + mt_rand(-10, 10);

            // 50% chance of curve vs line
            if (mt_rand(0,1)) {
                $cpY = $startY - mt_rand(20, 50);
                $this->drawBezier($image, $color, $currX, $startY, $currX + 10, $cpY, $endX - 10, $cpY, $endX, $endY, $thickness);
            } else {
                $this->drawThickLine($image, $currX, $startY, $endX, $endY, $color, $thickness);
            }
            $currX = $endX + mt_rand(5, 15);
        }
    }

    // ==========================================
    // ENGINE 6: THE ARCHITECT (Geometric)
    // ==========================================
    private function engineArchitect($image, $color, $width, $height, $thickness, $slant)
    {
        $startX = mt_rand(20, 40);
        $currX = $startX;
        $baseY = $height / 2 + 10;
        $capH = 40;

        $chars = mt_rand(4, 8);

        for ($i = 0; $i < $chars; $i++) {
            $w = mt_rand(15, 25);
            $nextX = $currX + $w;

            // Strict lines
            $type = mt_rand(0, 3);
            if ($type == 0) { // Vertical + Horizontal (L / T like)
                $this->drawThickLine($image, $currX + $w/2, $baseY, $currX + $w/2, $baseY - $capH, $color, $thickness);
                $this->drawThickLine($image, $currX, $baseY - $capH, $nextX, $baseY - $capH, $color, $thickness);
            } elseif ($type == 1) { // Diagonal (A / V like)
                $this->drawThickLine($image, $currX, $baseY, $currX + $w/2, $baseY - $capH, $color, $thickness);
                $this->drawThickLine($image, $currX + $w/2, $baseY - $capH, $nextX, $baseY, $color, $thickness);
            } elseif ($type == 2) { // Boxy
                $this->drawThickLine($image, $currX, $baseY, $currX, $baseY - $capH, $color, $thickness);
                $this->drawThickLine($image, $currX, $baseY - $capH, $nextX, $baseY - $capH, $color, $thickness);
                $this->drawThickLine($image, $nextX, $baseY - $capH, $nextX, $baseY, $color, $thickness);
            } else { // Dash
                 $this->drawThickLine($image, $currX, $baseY - $capH/2, $nextX, $baseY - $capH/2, $color, $thickness);
            }

            $currX = $nextX + mt_rand(5, 10);
        }
    }

    // ==========================================
    // ENGINE 7: THE CHILDISH (Wobbly)
    // ==========================================
    private function engineChildish($image, $color, $width, $height, $thickness, $slant)
    {
        $startX = mt_rand(30, 50);
        $startY = $height / 2;
        $currX = $startX;

        $letters = mt_rand(3, 6);

        for ($i = 0; $i < $letters; $i++) {
            $w = mt_rand(20, 30);
            $h = mt_rand(20, 40);

            // Draw wobbly oval or line
            if (mt_rand(0,1)) {
                // Wobbly Oval
                $cx = $currX + $w/2;
                $cy = $startY;
                // Deformity
                $d = mt_rand(-5, 5);
                $this->drawOval($image, $color, $cx, $cy + $d, $w + $d, $h + $d);
            } else {
                // Wobbly line
                $pts = 5;
                $segX = $currX;
                for ($k = 0; $k < $pts; $k++) {
                    $nextSegX = $segX + ($w / $pts);
                    $this->drawThickLine($image, $segX, $startY + mt_rand(-5, 5), $nextSegX, $startY + mt_rand(-5, 5), $color, $thickness);
                    $segX = $nextSegX;
                }
            }
            $currX += $w + mt_rand(5, 15);
        }
    }

    // ==========================================
    // ENGINE 8: THE COMPACT (Dense)
    // ==========================================
    private function engineCompact($image, $color, $width, $height, $thickness, $slant)
    {
        $startX = $width/2 - 40;
        $startY = $height/2;

        // High density of small strokes in small area
        $count = mt_rand(10, 20);
        $lastX = $startX;
        $lastY = $startY;

        for ($i = 0; $i < $count; $i++) {
            $dx = mt_rand(5, 15);
            $dy = mt_rand(-15, 15);

            $this->drawBezier($image, $color,
                $lastX, $lastY,
                $lastX + 5, $lastY - 20,
                $lastX + $dx - 5, $lastY + 20,
                $lastX + $dx, $lastY + $dy,
                $thickness
            );

            $lastX += $dx * 0.5; // Overlap
            $lastY += $dy * 0.2;
        }
    }

    // ==========================================
    // ENGINE 9: THE WILD (Big Range)
    // ==========================================
    private function engineWild($image, $color, $width, $height, $thickness, $slant)
    {
        $cx = $width / 2;
        $cy = $height / 2;

        // Big sweeping strokes
        $strokes = mt_rand(3, 5);

        for ($i = 0; $i < $strokes; $i++) {
             $x1 = mt_rand(20, $width - 20);
             $y1 = mt_rand(20, $height - 20);
             $x2 = mt_rand(20, $width - 20);
             $y2 = mt_rand(20, $height - 20);

             $cp1x = mt_rand(0, $width);
             $cp1y = mt_rand(0, $height);
             $cp2x = mt_rand(0, $width);
             $cp2y = mt_rand(0, $height);

             $this->drawBezier($image, $color, $x1, $y1, $cp1x, $cp1y, $cp2x, $cp2y, $x2, $y2, $thickness);
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
        $steps = 40;
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

        for ($i = -floor($thickness/2); $i <= floor($thickness/2); $i++) {
             imageline($image, (int)$x1+$i, (int)$y1+$i, (int)$x2+$i, (int)$y2+$i, $color);
             imageline($image, (int)$x1+$i, (int)$y1, (int)$x2+$i, (int)$y2, $color);
        }
    }

    private function drawOval($image, $color, $cx, $cy, $w, $h) {
        imageellipse($image, (int)$cx, (int)$cy, (int)$w, (int)$h, $color);
        imageellipse($image, (int)$cx, (int)$cy, (int)$w-1, (int)$h-1, $color);
        imagefilledellipse($image, (int)$cx, (int)$cy, (int)$w/2, (int)$h/2, $color);
    }
}

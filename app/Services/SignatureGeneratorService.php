<?php

namespace App\Services;

class SignatureGeneratorService
{
    /**
     * Generate a unique, highly diverse signature image.
     * Expands to >1,000 distinct variations by mixing engines, modifiers, and embellishments.
     *
     * @param string $seed Unique identifier (e.g., 'EMP-123-TIMESTAMP')
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

        // 2. Seed RNG for controlled randomness (consistency if seed is static, random if seed varies)
        mt_srand(crc32($seed) + strlen($seed));

        // 3. Selection of "DNA" components (Mixing & Matching)
        // Total Combinations = Engines(100) * Colors(10) * Thickness(5) * Slant(5) * Embellishments(10) > 250,000
        $engineId = mt_rand(0, 99);
        $colorId = mt_rand(0, 9);
        $thicknessId = mt_rand(0, 4);
        $slantId = mt_rand(0, 4);
        $embellishId = mt_rand(0, 9);

        // 4. Resolve Parameters
        $inkColor = $this->resolveColor($image, $colorId);
        $thickness = $this->resolveThickness($thicknessId);
        $slant = $this->resolveSlant($slantId);

        // 5. Execute Engine
        // Dispatch to Engine Families based on first digit (0-9)
        $family = (int)($engineId / 10);
        $subType = $engineId % 10;

        switch ($family) {
            case 0: $this->familyClassic($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 1: $this->familyGeometric($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 2: $this->familyThai($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 3: $this->familyMyanmar($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 4: $this->familyOrganic($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 5: $this->familyRising($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 6: $this->familyChaos($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 7: $this->familyStructured($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 8: $this->familyMixed($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
            case 9: $this->familyGrand($image, $inkColor, $width, $height, $thickness, $slant, $subType); break;
        }

        // 6. Apply Global Embellishments
        $this->applyEmbellishments($image, $inkColor, $width, $height, $thickness, $embellishId);

        // 7. Output
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }

    // ==========================================
    // PARAMETER RESOLVERS
    // ==========================================

    private function resolveColor($image, $id)
    {
        // 10 Distinct Color Palettes
        switch ($id) {
            case 0: return imagecolorallocate($image, 0, 0, 139); // Dark Blue
            case 1: return imagecolorallocate($image, 0, 0, 0);   // Pure Black
            case 2: return imagecolorallocate($image, 25, 25, 112); // Midnight Blue
            case 3: return imagecolorallocate($image, 60, 60, 60); // Dark Grey
            case 4: return imagecolorallocate($image, 0, 0, 205); // Medium Blue
            case 5: return imagecolorallocate($image, 30, 30, 30); // Soft Black
            case 6: return imagecolorallocate($image, 70, 70, 90); // Blue Grey
            case 7: return imagecolorallocate($image, 0, 51, 102); // Navy
            case 8: return imagecolorallocate($image, 50, 0, 0);   // Dark Red/Brown ink
            case 9: return imagecolorallocate($image, 0, 50, 0);   // Dark Green ink
            default: return imagecolorallocate($image, 0, 0, 0);
        }
    }

    private function resolveThickness($id)
    {
        // 0=Fine, 4=Heavy
        return $id + 1; // Returns 1 to 5
    }

    private function resolveSlant($id)
    {
        // Slant factor for engines that support it
        switch ($id) {
            case 0: return -0.3; // Back slant
            case 1: return 0.0;  // Upright
            case 2: return 0.3;  // Slight forward
            case 3: return 0.6;  // Heavy forward
            case 4: return (mt_rand(-5, 5) / 10); // Random
            default: return 0;
        }
    }

    private function applyEmbellishments($image, $color, $width, $height, $thickness, $type)
    {
        $yBase = $height - mt_rand(10, 30);
        $thick = max(1, $thickness - 1);

        switch ($type) {
            case 0: // Single Underline
                $this->drawThickLine($image, 40, $yBase, $width-40, $yBase, $color, $thick);
                break;
            case 1: // Double Underline
                $this->drawThickLine($image, 40, $yBase, $width-40, $yBase, $color, $thick);
                $this->drawThickLine($image, 50, $yBase+5, $width-50, $yBase+5, $color, $thick);
                break;
            case 2: // Dots at end
                $this->drawOval($image, $color, $width-40, $height/2, 4, 4);
                $this->drawOval($image, $color, $width-25, $height/2, 4, 4);
                break;
            case 3: // Strike-through (Subtle)
                $mid = $height/2;
                $this->drawThickLine($image, 60, $mid, $width-60, $mid, $color, 1);
                break;
            case 4: // Circle Frame (Rough)
                $this->drawOval($image, $color, $width/2, $height/2, $width-20, $height-20);
                break;
            case 5: // ZigZag Underline
                $currX = 40;
                $currY = $yBase;
                while($currX < $width-40) {
                    $this->drawThickLine($image, $currX, $currY, $currX+10, $currY-5, $color, $thick);
                    $this->drawThickLine($image, $currX+10, $currY-5, $currX+20, $currY, $color, $thick);
                    $currX += 20;
                }
                break;
            case 6: // Cross X at start
                $this->drawThickLine($image, 20, $height/2-10, 40, $height/2+10, $color, $thick);
                $this->drawThickLine($image, 40, $height/2-10, 20, $height/2+10, $color, $thick);
                break;
            case 7: // Top Line
                $this->drawThickLine($image, 40, 20, $width-40, 20, $color, $thick);
                break;
            case 8: // Messy Dots Everywhere
                for($i=0; $i<5; $i++) {
                    $this->drawOval($image, $color, mt_rand(20, $width-20), mt_rand(20, $height-20), 2, 2);
                }
                break;
            case 9: // None (Clean)
                // Do nothing
                break;
        }
    }

    // ==========================================
    // FAMILY 1: CLASSIC (0-9) - Preserving Original Logic
    // ==========================================
    private function familyClassic($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        switch ($subType) {
            case 0: $this->engineLooper($image, $color, $width, $height, $thickness, $slant); break;
            case 1: $this->engineSpike($image, $color, $width, $height, $thickness, $slant); break;
            case 2: $this->engineBlock($image, $color, $width, $height, $thickness, $slant); break;
            case 3: $this->engineWave($image, $color, $width, $height, $thickness, $slant); break;
            case 4: $this->engineAbstract($image, $color, $width, $height, $thickness, $slant); break;
            case 5: $this->engineMinimalist($image, $color, $width, $height, $thickness, $slant); break;
            case 6: $this->engineArchitect($image, $color, $width, $height, $thickness, $slant); break;
            case 7: $this->engineChildish($image, $color, $width, $height, $thickness, $slant); break;
            case 8: $this->engineCompact($image, $color, $width, $height, $thickness, $slant); break;
            case 9: $this->engineWild($image, $color, $width, $height, $thickness, $slant); break;
        }
    }

    // ==========================================
    // FAMILY 2: GEOMETRIC (10-19)
    // Shapes: Circles, Triangles, Squares, Stretched, Squashed
    // ==========================================
    private function familyGeometric($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        $cx = $width / 2 + mt_rand(-20, 20);
        $cy = $height / 2;
        $baseSize = mt_rand(30, 50);

        for ($i = 0; $i < mt_rand(3, 6); $i++) {
            $w = $baseSize + mt_rand(-10, 20);
            $h = $baseSize + mt_rand(-10, 20);
            $x = $cx + ($i * 20) - 40;
            $y = $cy + mt_rand(-10, 10);

            // Apply slant offset
            $x += $y * ($slant * 0.1);

            switch ($subType) {
                case 0: // Circles Only
                    $this->drawOval($image, $color, $x, $y, $w, $w); // Perfect circle
                    break;
                case 1: // Flat Ovals (Pressed)
                    $this->drawOval($image, $color, $x, $y, $w * 1.5, $h * 0.5);
                    break;
                case 2: // Tall Ovals (Stretched)
                    $this->drawOval($image, $color, $x, $y, $w * 0.5, $h * 1.5);
                    break;
                case 3: // Triangles (Up)
                    $this->drawPoly($image, $color, [$x, $y-$h, $x-$w/2, $y+$h/2, $x+$w/2, $y+$h/2], $thickness);
                    break;
                case 4: // Triangles (Down/Mixed)
                    $dir = ($i % 2 == 0) ? 1 : -1;
                    $this->drawPoly($image, $color, [$x, $y-($h*$dir), $x-$w/2, $y+($h*$dir)/2, $x+$w/2, $y+($h*$dir)/2], $thickness);
                    break;
                case 5: // Rectangles (Boxy)
                    $this->drawThickLine($image, $x-$w/2, $y-$h/2, $x+$w/2, $y-$h/2, $color, $thickness);
                    $this->drawThickLine($image, $x+$w/2, $y-$h/2, $x+$w/2, $y+$h/2, $color, $thickness);
                    $this->drawThickLine($image, $x+$w/2, $y+$h/2, $x-$w/2, $y+$h/2, $color, $thickness);
                    $this->drawThickLine($image, $x-$w/2, $y+$h/2, $x-$w/2, $y-$h/2, $color, $thickness);
                    break;
                case 6: // Diamonds
                    $this->drawPoly($image, $color, [$x, $y-$h, $x+$w, $y, $x, $y+$h, $x-$w, $y], $thickness);
                    break;
                case 7: // Spirals (Approximated)
                    $this->drawSpiral($image, $color, $x, $y, $w, $thickness);
                    break;
                case 8: // Concentric Rings
                    $this->drawOval($image, $color, $x, $y, $w, $h);
                    $this->drawOval($image, $color, $x, $y, $w/2, $h/2);
                    break;
                case 9: // Mixed Geometry
                    if ($i % 3 == 0) $this->drawOval($image, $color, $x, $y, $w, $h);
                    elseif ($i % 3 == 1) $this->drawPoly($image, $color, [$x, $y-$h, $x-$w/2, $y+$h/2, $x+$w/2, $y+$h/2], $thickness);
                    else $this->drawThickLine($image, $x-$w/2, $y, $x+$w/2, $y, $color, $thickness);
                    break;
            }
        }
    }

    // ==========================================
    // FAMILY 3: THAI STYLE (20-29)
    // Features: Loops (Heads), Zigzags, Arches, Complex Curves
    // ==========================================
    private function familyThai($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        $startX = mt_rand(20, 50);
        $currX = $startX;
        $baseY = $height / 2;

        $chars = mt_rand(4, 8);

        for ($i = 0; $i < $chars; $i++) {
            $w = mt_rand(20, 35);
            $h = mt_rand(30, 50);
            $headSize = mt_rand(5, 10);

            // Apply Slant
            $skew = ($slant * 5);

            // Draw "Head" (Loop)
            $headX = $currX + $headSize + $skew;
            $headY = $baseY + ($subType % 2 == 0 ? -$h/2 : $h/2); // Top or bottom loop

            // Draw the loop
            $this->drawOval($image, $color, $headX, $headY, $headSize*2, $headSize*2);

            // Body stroke based on subtype
            switch ($subType) {
                case 0: // Kor Kai style (No head, arch)
                    $this->drawBezier($image, $color, $currX, $baseY+$h, $currX, $baseY-$h, $currX+$w, $baseY-$h, $currX+$w, $baseY+$h, $thickness);
                    break;
                case 1: // Standard Loop + Vertical
                    $this->drawThickLine($image, $headX + $headSize, $headY, $headX + $headSize + $skew, $baseY + $h/2, $color, $thickness);
                    break;
                case 2: // Loop + Zigzag
                    $this->drawThickLine($image, $headX, $headY, $currX + $w/2, $baseY - $h, $color, $thickness);
                    $this->drawThickLine($image, $currX + $w/2, $baseY - $h, $currX + $w, $baseY, $color, $thickness);
                    break;
                case 3: // Rolling (Mhor Maa)
                    $this->drawOval($image, $color, $currX+$skew, $baseY, $w, $w);
                    $this->drawOval($image, $color, $currX+$w/2+$skew, $baseY, $w, $w);
                    break;
                case 4: // Sharp Peak (Tor Tao)
                    $this->drawBezier($image, $color, $currX, $baseY, $currX+$w/2, $baseY-$h*1.5, $currX+$w/2, $baseY-$h*0.5, $currX+$w, $baseY, $thickness);
                    break;
                case 5: // Long Tail (Phor Pla)
                    $this->drawOval($image, $color, $currX+$skew, $baseY, $headSize*2, $headSize*2);
                    $this->drawThickLine($image, $currX+$headSize, $baseY, $currX+$headSize+$skew*2, $baseY-$h*1.2, $color, $thickness);
                    break;
                case 6: // Complex Knot
                    $this->drawSpiral($image, $color, $currX, $baseY, $w, $thickness);
                    break;
                case 7: // Wide Arch (Sor Sala)
                     $this->drawBezier($image, $color, $currX, $baseY, $currX+$w, $baseY-$h*2, $currX+$w*2, $baseY-$h*2, $currX+$w, $baseY, $thickness);
                    break;
                case 8: // Dropping Tail (Ror Rua)
                    $this->drawOval($image, $color, $currX, $baseY-$h/2, $headSize*2, $headSize*2);
                    $this->drawBezier($image, $color, $currX+$headSize, $baseY-$h/2, $currX+$w, $baseY-$h/2, $currX, $baseY, $currX+$w, $baseY, $thickness);
                    break;
                case 9: // Mixed Messy Thai
                    $this->drawOval($image, $color, $currX, $baseY+mt_rand(-20,20), $headSize*2, $headSize*2);
                    $this->drawThickLine($image, $currX, $baseY, $currX+$w, $baseY+mt_rand(-20,20), $color, $thickness);
                    break;
            }
            $currX += $w + mt_rand(-5, 5);
        }
    }

    // ==========================================
    // FAMILY 4: MYANMAR STYLE (30-39)
    // Features: Circular, Bubble-like, Stacked Circles, C-shapes
    // ==========================================
    private function familyMyanmar($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        $startX = mt_rand(30, 60);
        $currX = $startX;
        $baseY = $height / 2;
        $count = mt_rand(5, 10);

        for ($i = 0; $i < $count; $i++) {
            $r = mt_rand(10, 20); // Radius

            switch ($subType) {
                case 0: // Full Circles Chain
                    $this->drawOval($image, $color, $currX, $baseY, $r*2, $r*2);
                    break;
                case 1: // Open C (Left)
                    $this->drawArc($image, $color, $currX, $baseY, $r*2, $r*2, 45, 315, $thickness);
                    break;
                case 2: // Open C (Right)
                    $this->drawArc($image, $color, $currX, $baseY, $r*2, $r*2, 225, 135, $thickness);
                    break;
                case 3: // Open C (Top/Bottom Mixed)
                    if ($i%2==0) $this->drawArc($image, $color, $currX, $baseY, $r*2, $r*2, 0, 180, $thickness);
                    else $this->drawArc($image, $color, $currX, $baseY, $r*2, $r*2, 180, 0, $thickness);
                    break;
                case 4: // Stacked Circles (Double height)
                    $this->drawOval($image, $color, $currX, $baseY-$r, $r*1.5, $r*1.5);
                    $this->drawOval($image, $color, $currX, $baseY+$r, $r*1.5, $r*1.5);
                    break;
                case 5: // Overlapping Circles
                    $this->drawOval($image, $color, $currX, $baseY, $r*2.5, $r*2.5);
                    $currX -= $r; // Backtrack to overlap
                    break;
                case 6: // "m" shape (Two bumps)
                     $this->drawBezier($image, $color, $currX-$r, $baseY, $currX-$r, $baseY-$r*2, $currX, $baseY-$r*2, $currX, $baseY, $thickness);
                     $this->drawBezier($image, $color, $currX, $baseY, $currX, $baseY-$r*2, $currX+$r, $baseY-$r*2, $currX+$r, $baseY, $thickness);
                    break;
                case 7: // Large Bubbles
                    $this->drawOval($image, $color, $currX, $baseY + mt_rand(-10,10), $r*3, $r*3);
                    break;
                case 8: // Dots and Circles
                    $this->drawOval($image, $color, $currX, $baseY, $r*2, $r*2);
                    $this->drawOval($image, $color, $currX+$r, $baseY-$r, 5, 5); // Dot
                    break;
                case 9: // Spiral Chains
                    $this->drawSpiral($image, $color, $currX, $baseY, $r*1.5, $thickness);
                    break;
            }
            $currX += $r * 2 + mt_rand(0, 5);
        }
    }

    // ==========================================
    // FAMILY 5: ORGANIC / ANIMAL (40-49)
    // Features: Flowing, Tail-like, Wings, Snake, Fish
    // ==========================================
    private function familyOrganic($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        $cx = $width / 2;
        $cy = $height / 2;

        switch ($subType) {
            case 0: // The Snake (Sinusoidal long line)
                $this->drawBezier($image, $color, 20, $cy, $width/3, $cy-50, $width*2/3, $cy+50, $width-20, $cy, $thickness);
                // Tongue
                $this->drawThickLine($image, $width-20, $cy, $width-10, $cy-5, $color, 1);
                $this->drawThickLine($image, $width-20, $cy, $width-10, $cy+5, $color, 1);
                break;
            case 1: // The Bird (Wing shapes)
                $this->drawBezier($image, $color, 40, $cy, 60, $cy-60, 100, $cy-60, 120, $cy, $thickness); // Left wing
                $this->drawBezier($image, $color, 120, $cy, 140, $cy-60, 180, $cy-60, 200, $cy, $thickness); // Right wing
                break;
            case 2: // The Fish (Loop tail + body)
                $this->drawOval($image, $color, $cx, $cy, 80, 40); // Body
                $this->drawPoly($image, $color, [$cx-40, $cy, $cx-60, $cy-20, $cx-60, $cy+20], $thickness); // Tail
                break;
            case 3: // The Spiral Shell
                $this->drawSpiral($image, $color, $cx, $cy, 60, $thickness);
                break;
            case 4: // Grass / Spikes
                for($i=0; $i<10; $i++) {
                    $x = 40 + $i*20;
                    $h = mt_rand(20, 50);
                    $this->drawBezier($image, $color, $x, $cy+20, $x+5, $cy-$h, $x+15, $cy-$h, $x+20, $cy+20, $thickness);
                }
                break;
            case 5: // Waves / Water
                $this->engineWave($image, $color, $width, $height, $thickness, $slant); // Reuse wave
                $this->engineWave($image, $color, $width, $height+20, $thickness, $slant); // Double wave
                break;
            case 6: // Cat Ears (M shape repeated)
                 $currX = 50;
                 for($i=0; $i<5; $i++) {
                     $this->drawPoly($image, $color, [$currX, $cy, $currX+10, $cy-30, $currX+20, $cy], $thickness);
                     $currX += 30;
                 }
                break;
            case 7: // Bug (Central body + legs)
                $this->drawOval($image, $color, $cx, $cy, 50, 20);
                $this->drawThickLine($image, $cx, $cy-10, $cx-20, $cy-30, $color, 1);
                $this->drawThickLine($image, $cx, $cy-10, $cx+20, $cy-30, $color, 1);
                $this->drawThickLine($image, $cx, $cy+10, $cx-20, $cy+30, $color, 1);
                $this->drawThickLine($image, $cx, $cy+10, $cx+20, $cy+30, $color, 1);
                break;
            case 8: // Lightning
                $this->drawPoly($image, $color, [50, $cy-20, 100, $cy+20, 90, $cy, 140, $cy+40], $thickness);
                break;
            case 9: // Roots (Downwards branching)
                $this->drawThickLine($image, $cx, 20, $cx, 60, $color, $thickness*2);
                $this->drawThickLine($image, $cx, 60, $cx-30, 100, $color, $thickness);
                $this->drawThickLine($image, $cx, 60, $cx+30, 100, $color, $thickness);
                break;
        }
    }

    // ==========================================
    // FAMILY 6: RISING / HISTORY (50-59)
    // Features: Upward trends, Charts, Stairs, Swoosh up
    // ==========================================
    private function familyRising($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        $startX = 30;
        $endX = $width - 30;
        $startY = $height - 40;
        $endY = 40; // High up

        switch ($subType) {
            case 0: // Linear Growth
                $this->drawThickLine($image, $startX, $startY, $endX, $endY, $color, $thickness);
                break;
            case 1: // Exponential Curve
                $this->drawBezier($image, $color, $startX, $startY, $endX-50, $startY, $endX, $startY, $endX, $endY, $thickness);
                break;
            case 2: // Stairs Up
                $steps = 5;
                $stepW = ($endX - $startX) / $steps;
                $stepH = ($startY - $endY) / $steps;
                $cx = $startX; $cy = $startY;
                for($i=0; $i<$steps; $i++) {
                    $this->drawThickLine($image, $cx, $cy, $cx+$stepW, $cy, $color, $thickness);
                    $this->drawThickLine($image, $cx+$stepW, $cy, $cx+$stepW, $cy-$stepH, $color, $thickness);
                    $cx += $stepW; $cy -= $stepH;
                }
                break;
            case 3: // Volatile Growth (Stock market)
                $pts = 10;
                $stepW = ($endX - $startX) / $pts;
                $cx = $startX; $cy = $startY;
                for($i=0; $i<$pts; $i++) {
                    $ny = $cy - mt_rand(-10, 40); // Generally up
                    $this->drawThickLine($image, $cx, $cy, $cx+$stepW, $ny, $color, $thickness);
                    $cx += $stepW; $cy = $ny;
                }
                break;
            case 4: // Loop-de-loop Up
                $this->drawBezier($image, $color, $startX, $startY, $startX+50, $startY, $startX+20, $endY+50, $endX, $endY, $thickness);
                break;
            case 5: // Arrow Up
                $this->drawThickLine($image, $startX, $startY, $endX, $endY, $color, $thickness);
                $this->drawThickLine($image, $endX, $endY, $endX-20, $endY+10, $color, $thickness);
                $this->drawThickLine($image, $endX, $endY, $endX-10, $endY+20, $color, $thickness);
                break;
            case 6: // Checkmark (Huge)
                $this->drawThickLine($image, $startX, $height/2, $startX+30, $height-30, $color, $thickness);
                $this->drawThickLine($image, $startX+30, $height-30, $endX, 20, $color, $thickness);
                break;
            case 7: // Tornado Up
                $this->drawSpiral($image, $color, $width/2, $height/2, 40, $thickness); // Base spiral
                $this->drawThickLine($image, $width/2, $height/2, $endX, $endY, $color, 1); // Shoot up
                break;
            case 8: // Three Parallel Lines Up
                $this->drawThickLine($image, $startX, $startY, $endX-20, $endY, $color, $thickness);
                $this->drawThickLine($image, $startX+10, $startY+10, $endX-10, $endY+10, $color, $thickness);
                $this->drawThickLine($image, $startX+20, $startY+20, $endX, $endY+20, $color, $thickness);
                break;
            case 9: // Mountain Peak
                $this->drawPoly($image, $color, [$startX, $startY, $width/2, $endY, $endX, $startY], $thickness);
                break;
        }
    }

    // ==========================================
    // FAMILY 7: CHAOS / DOCTOR (60-69)
    // Features: Scribbles, High density, Illegible, Fast
    // ==========================================
    private function familyChaos($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        $cx = $width / 2;
        $cy = $height / 2;

        // Base chaos loop
        $count = mt_rand(10, 30);
        $prevX = mt_rand(20, 50);
        $prevY = $cy;

        for ($i = 0; $i < $count; $i++) {
            $nextX = $prevX + mt_rand(5, 20);
            $nextY = $cy + mt_rand(-30, 30);

            // Add subtypes of chaos
            if ($subType < 3) { // Horizontal scratch
                $nextY = $cy + mt_rand(-5, 5);
            } elseif ($subType < 6) { // Vertical spikes
                $nextY = $cy + mt_rand(-50, 50);
                $nextX = $prevX + mt_rand(2, 5);
            } elseif ($subType < 8) { // Loop scratch
                $this->drawOval($image, $color, $prevX, $prevY, mt_rand(10,30), mt_rand(10,30));
            } else { // Total random
                $nextX = mt_rand(0, $width);
                $nextY = mt_rand(0, $height);
            }

            $this->drawThickLine($image, $prevX, $prevY, $nextX, $nextY, $color, 1);
            $prevX = $nextX;
            $prevY = $nextY;
        }
    }

    // ==========================================
    // FAMILY 8: STRUCTURED / ARCHITECT (70-79)
    // Features: Grid-like, angular, distinct
    // ==========================================
    private function familyStructured($image, $color, $width, $height, $thickness, $slant, $subType)
    {
         // Re-using Architect engine logic but with forced variations
         // Subtypes 0-9 map to variations of spacing, height, and "font"
         $this->engineArchitect($image, $color, $width, $height, $thickness, $slant);

         if ($subType % 2 == 0) {
             // Add a box around it
             $this->drawThickLine($image, 10, 10, $width-10, 10, $color, 1);
             $this->drawThickLine($image, 10, $height-10, $width-10, $height-10, $color, 1);
         }
         if ($subType > 5) {
             // Add cross hatch
             for($i=20; $i<$width; $i+=20) {
                 $this->drawThickLine($image, $i, $height/2-5, $i, $height/2+5, $color, 1);
             }
         }
    }

    // ==========================================
    // FAMILY 9: MIXED / ALIEN (80-89)
    // Features: Combinations of all above
    // ==========================================
    private function familyMixed($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        // Combine 2 random engines
        $e1 = mt_rand(0, 7);
        $e2 = mt_rand(0, 7);

        $this->familyClassic($image, $color, $width, $height, max(1, $thickness-1), $slant, $e1);
        $this->familyGeometric($image, $color, $width, $height, 1, $slant, $e2);
    }

    // ==========================================
    // FAMILY 10: GRAND / COMPLEX (90-99)
    // Features: Multi-layered, Heavy ink, Showy
    // ==========================================
    private function familyGrand($image, $color, $width, $height, $thickness, $slant, $subType)
    {
        // 1. Big Underlying Swash
        $this->drawBezier($image, $color, 10, $height-20, $width/3, 10, $width*2/3, 10, $width-10, $height-20, $thickness+2);

        // 2. Name scribbled on top
        $this->familyClassic($image, $color, $width, $height, 2, $slant, 0); // Looper

        // 3. Underline + Dots
        $this->drawThickLine($image, 20, $height-10, $width-20, $height-10, $color, $thickness);
        $this->drawOval($image, $color, $width/2, $height-5, 4, 4);
        $this->drawOval($image, $color, $width/2 + 10, $height-5, 4, 4);
    }


    // ==========================================
    // ORIGINAL ENGINES (Re-implemented/Kept for Family 1)
    // ==========================================
    private function engineLooper($image, $color, $width, $height, $thickness, $slant) {
        $startX = mt_rand(20, 60); $startY = $height / 2; $currentX = $startX; $currentY = $startY;
        for ($i = 0; $i < mt_rand(6, 12); $i++) {
             $endX = $currentX + mt_rand(20,40); $endY = $startY;
             $this->drawBezier($image, $color, $currentX, $currentY, $currentX+10, $currentY-40, $endX-10, $endY-40, $endX, $endY, $thickness);
             $currentX = $endX;
        }
    }
    private function engineSpike($image, $color, $width, $height, $thickness, $slant) {
         $startX = mt_rand(20, 50); $currentX = $startX; $baseY = $height/2;
         for ($i=0; $i<15; $i++) {
             $nextX = $currentX + mt_rand(10, 20); $nextY = $baseY + mt_rand(-30, 30);
             $this->drawThickLine($image, $currentX, $baseY, $nextX, $nextY, $color, $thickness);
             $this->drawThickLine($image, $nextX, $nextY, $nextX, $baseY, $color, $thickness);
             $currentX = $nextX;
         }
    }
    private function engineBlock($image, $color, $width, $height, $thickness, $slant) {
        $x = 40; $y=$height/2;
        for($i=0; $i<4; $i++) {
            $this->drawThickLine($image, $x, $y-20, $x, $y+20, $color, $thickness); // Vertical
            $this->drawThickLine($image, $x, $y-20, $x+20, $y-20, $color, $thickness); // Top
            $this->drawThickLine($image, $x, $y+20, $x+20, $y+20, $color, $thickness); // Bottom
            $x += 30;
        }
    }
    private function engineWave($image, $color, $width, $height, $thickness, $slant) {
         $this->drawBezier($image, $color, 20, $height/2, $width/3, $height/2-30, $width*2/3, $height/2+30, $width-20, $height/2, $thickness);
    }
    private function engineAbstract($image, $color, $width, $height, $thickness, $slant) {
         $this->familyChaos($image, $color, $width, $height, $thickness, $slant, 9);
    }
    private function engineMinimalist($image, $color, $width, $height, $thickness, $slant) {
         $this->drawThickLine($image, 50, $height/2, $width-50, $height/2, $color, $thickness);
    }
    private function engineArchitect($image, $color, $width, $height, $thickness, $slant) {
         $x = 40; $y=$height/2;
         for($i=0; $i<5; $i++) {
             $this->drawThickLine($image, $x, $y-20, $x+15, $y+20, $color, $thickness); // Diagonal
             $this->drawThickLine($image, $x+15, $y+20, $x+30, $y-20, $color, $thickness); // Diagonal up
             $x += 35;
         }
    }
    private function engineChildish($image, $color, $width, $height, $thickness, $slant) {
         $x = 40; $y=$height/2;
         for($i=0; $i<4; $i++) {
             $this->drawOval($image, $color, $x, $y+mt_rand(-5,5), 30, 30);
             $x += 35;
         }
    }
    private function engineCompact($image, $color, $width, $height, $thickness, $slant) {
         $this->drawSpiral($image, $color, $width/2, $height/2, 20, $thickness);
    }
    private function engineWild($image, $color, $width, $height, $thickness, $slant) {
         $this->drawBezier($image, $color, 20, $height-20, $width/2, 0, $width/2, $height, $width-20, 20, $thickness);
    }


    // ==========================================
    // UTILS
    // ==========================================

    private function drawBezier($image, $color, $x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $thickness = 1)
    {
        $steps = 40;
        $prevX = $x0;
        $prevY = $y0;
        for ($i = 1; $i <= $steps; $i++) {
            $t = $i / $steps;
            $u = 1 - $t;
            $tt = $t * $t; $uu = $u * $u;
            $uuu = $uu * $u; $ttt = $tt * $t;
            $x = $uuu * $x0 + 3 * $uu * $t * $x1 + 3 * $u * $tt * $x2 + $ttt * $x3;
            $y = $uuu * $y0 + 3 * $uu * $t * $y1 + 3 * $u * $tt * $y2 + $ttt * $y3;
            $this->drawThickLine($image, $prevX, $prevY, $x, $y, $color, $thickness);
            $prevX = $x; $prevY = $y;
        }
    }

    private function drawThickLine($image, $x1, $y1, $x2, $y2, $color, $thickness) {
        if ($thickness <= 1) { imageline($image, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $color); return; }
        for ($i = -floor($thickness/2); $i <= floor($thickness/2); $i++) {
             imageline($image, (int)$x1+$i, (int)$y1+$i, (int)$x2+$i, (int)$y2+$i, $color);
             imageline($image, (int)$x1+$i, (int)$y1, (int)$x2+$i, (int)$y2, $color);
        }
    }

    private function drawOval($image, $color, $cx, $cy, $w, $h) {
        imageellipse($image, (int)$cx, (int)$cy, (int)$w, (int)$h, $color);
        imageellipse($image, (int)$cx, (int)$cy, (int)max(1,$w-1), (int)max(1,$h-1), $color);
    }

    private function drawPoly($image, $color, $points, $thickness) {
        // Simple polygon drawer using lines
        $count = count($points);
        for($i=0; $i<$count; $i+=2) {
             $x1 = $points[$i]; $y1 = $points[$i+1];
             $x2 = $points[($i+2)%$count]; $y2 = $points[($i+3)%$count];
             $this->drawThickLine($image, $x1, $y1, $x2, $y2, $color, $thickness);
        }
    }

    private function drawSpiral($image, $color, $cx, $cy, $maxR, $thickness) {
        $angle = 0;
        $r = 5;
        $prevX = $cx; $prevY = $cy;
        while($r < $maxR) {
             $x = $cx + cos($angle) * $r;
             $y = $cy + sin($angle) * $r;
             $this->drawThickLine($image, $prevX, $prevY, $x, $y, $color, $thickness);
             $prevX = $x; $prevY = $y;
             $angle += 0.5;
             $r += 2;
        }
    }

    private function drawArc($image, $color, $cx, $cy, $w, $h, $s, $e, $thickness) {
         imagearc($image, (int)$cx, (int)$cy, (int)$w, (int)$h, $s, $e, $color);
         if($thickness > 1) imagearc($image, (int)$cx, (int)$cy, (int)$w-1, (int)$h-1, $s, $e, $color);
    }
}

<?php

namespace App\Services;

class SignatureGeneratorService
{
    /**
     * Generate a unique, highly diverse signature image.
     * Overhauled to simulate natural human handwriting with variable pressure and smoothing.
     *
     * @param string $seed Unique identifier
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
        mt_srand(crc32($seed) + strlen($seed));

        // 3. Global Style Profile
        $colorId = mt_rand(0, 9);
        $inkColor = $this->resolveColor($image, $colorId);
        $baseThickness = 1.6 + (mt_rand(0, 22) / 10); // 1.6 to 3.8
        $globalSlant = (mt_rand(-20, 40) / 100);
        $globalJitter = mt_rand(3, 12) / 10;

        // 4. Initial Position
        $currX = mt_rand(30, 60);
        $currY = mt_rand((int)($height * 0.45), (int)($height * 0.55));
        $currentPos = ['x' => (float)$currX, 'y' => (float)$currY];

        // 5. Build Composition
        // Component 1: The Head (Large Initial)
        $headType = mt_rand(0, 11);
        $this->drawComponent($image, 'head', $currentPos, $inkColor, $baseThickness * 1.2, $globalSlant, $globalJitter, $headType);

        // Component 2: The Body (Scribbles)
        $numSegments = mt_rand(2, 6);
        for ($i = 0; $i < $numSegments; $i++) {
            $segmentType = mt_rand(0, 10);
            $this->drawComponent($image, 'body', $currentPos, $inkColor, $baseThickness * 0.9, $globalSlant, $globalJitter, $segmentType);

            // Randomly lift pen or add a small connector
            if (mt_rand(0, 100) < 20) {
                $currentPos['x'] += mt_rand(8, 20);
                $currentPos['y'] += mt_rand(-5, 5);
            }
        }

        // Component 3: The Tail / Flourish
        $tailType = mt_rand(0, 12);
        $this->drawComponent($image, 'tail', $currentPos, $inkColor, $baseThickness, $globalSlant, $globalJitter, $tailType, $width, $height);

        // 6. Output
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }

    private function drawComponent($image, $role, &$currentPos, $color, $thickness, $slant, $jitter, $type, $canvasW = 300, $canvasH = 150)
    {
        $points = [];
        $startX = $currentPos['x'];
        $startY = $currentPos['y'];

        switch ($role) {
            case 'head': $points = $this->generateHeadPoints($startX, $startY, $type); break;
            case 'body': $points = $this->generateBodyPoints($startX, $startY, $type); break;
            case 'tail': $points = $this->generateTailPoints($startX, $startY, $type, $canvasW, $canvasH); break;
        }

        if (empty($points)) return;

        foreach ($points as &$p) {
            // Apply Slant
            $p['x'] += ($p['y'] - $startY) * $slant;
            // Apply Jitter
            $p['x'] += (mt_rand(-100, 100) / 100) * $jitter;
            $p['y'] += (mt_rand(-100, 100) / 100) * $jitter;
        }

        $smoothPoints = $this->smoothPath($points);
        $this->drawNaturalStroke($image, $smoothPoints, $color, $thickness);

        $lastPoint = end($points);
        $currentPos['x'] = $lastPoint['x'];
        $currentPos['y'] = $lastPoint['y'];
    }

    // ==========================================
    // POINT GENERATORS
    // ==========================================

    private function generateHeadPoints($x, $y, $type)
    {
        $p = [];
        switch ($type) {
            case 0: // Loop 'L' or 'N' style
                $p = [[$x, $y], [$x+5, $y-45], [$x+35, $y-45], [$x+15, $y], [$x+40, $y+15]]; break;
            case 1: // Sharp 'A' style
                $p = [[$x, $y+15], [$x+15, $y-40], [$x+30, $y+15], [$x+10, $y-5], [$x+35, $y-5]]; break;
            case 2: // Rounded 'B'/'3' style
                $p = [[$x, $y-40], [$x+30, $y-35], [$x+10, $y-20], [$x+35, $y], [$x+5, $y+10]]; break;
            case 3: // Spiral 'O' style
                $p = [[$x+20, $y-20], [$x, $y-40], [$x-15, $y-20], [$x, $y], [$x+30, $y-10], [$x+45, $y]]; break;
            case 4: // Vertical with long tail
                $p = [[$x, $y-50], [$x, $y+20], [$x+30, $y-10]]; break;
            case 5: // 'S' / 'G' curve
                $p = [[$x+35, $y-45], [$x, $y-45], [$x, $y-15], [$x+35, $y-15], [$x+35, $y+15], [$x, $y+15]]; break;
            case 6: // Geometric integrated
                $p = [[$x, $y-20], [$x+15, $y-40], [$x+40, $y-40], [$x+55, $y-20], [$x+40, $y], [$x+15, $y], [$x+55, $y-20]]; break;
            case 7: // Double Loop 'M'
                $p = [[$x, $y], [$x+10, $y-40], [$x+20, $y], [$x+30, $y-40], [$x+40, $y]]; break;
            case 8: // Fancy 'T' / 'F'
                $p = [[$x-10, $y-40], [$x+40, $y-40], [$x+15, $y-40], [$x+15, $y+20], [$x+5, $y+10]]; break;
            case 9: // Simple 'C' curve
                $p = [[$x+40, $y-30], [$x+10, $y-30], [$x+10, $y+10], [$x+40, $y+10]]; break;
            case 10: // Upward hook
                $p = [[$x+10, $y+20], [$x+10, $y-20], [$x+30, $y-40]]; break;
            case 11: // Lightning bolt start
                $p = [[$x+10, $y-30], [$x, $y], [$x+20, $y-10], [$x+10, $y+20], [$x+40, $y]]; break;
        }
        return $this->arrayToPoints($p);
    }

    private function generateBodyPoints($x, $y, $type)
    {
        $p = [];
        $w = mt_rand(20, 40);
        $h = mt_rand(10, 20);
        switch ($type) {
            case 0: // 'u'
                $p = [[$x, $y], [$x+$w*0.3, $y+$h], [$x+$w*0.7, $y+$h], [$x+$w, $y]]; break;
            case 1: // 'n'
                $p = [[$x, $y], [$x+$w*0.3, $y-$h], [$x+$w*0.7, $y-$h], [$x+$w, $y]]; break;
            case 2: // 'e' loop
                $p = [[$x, $y], [$x+$w, $y-$h], [$x+$w+5, $y], [$x+$w/2, $y+5], [$x+$w*1.2, $y]]; break;
            case 3: // 'w'
                $p = [[$x, $y], [$x+$w/4, $y+$h], [$x+$w/2, $y], [$x+$w*0.75, $y+$h], [$x+$w, $y]]; break;
            case 4: // 'i' style
                $p = [[$x, $y], [$x+5, $y-15], [$x+5, $y], [$x+15, $y]]; break;
            case 5: // Sharp peaks
                $p = [[$x, $y], [$x+10, $y-15], [$x+20, $y+10], [$x+30, $y-15], [$x+40, $y]]; break;
            case 6: // Low wave
                $p = [[$x, $y], [$x+$w/2, $y+5], [$x+$w, $y-5], [$x+$w*1.5, $y]]; break;
            case 7: // Connected loops (cursive)
                $p = [[$x, $y], [$x+10, $y-20], [$x+15, $y], [$x+25, $y-20], [$x+30, $y]]; break;
            case 8: // Flat line with bump
                $p = [[$x, $y], [$x+10, $y], [$x+15, $y-10], [$x+20, $y], [$x+30, $y]]; break;
            case 9: // Sawtooth
                $p = [[$x, $y], [$x+5, $y-10], [$x+10, $y], [$x+15, $y-10], [$x+20, $y]]; break;
            case 10: // Loop-de-loop
                $p = [[$x, $y], [$x+10, $y-15], [$x+5, $y-5], [$x+15, $y-20], [$x+20, $y]]; break;
        }
        return $this->arrayToPoints($p);
    }

    private function generateTailPoints($x, $y, $type, $canvasW, $canvasH)
    {
        $p = [];
        switch ($type) {
            case 0: // Long underline
                $p = [[$x-150, $y+35], [$x-50, $y+25], [$x+50, $y+35], [$canvasW-30, $y+25]]; break;
            case 1: // Swoosh up
                $p = [[$x, $y], [$x+30, $y+20], [$x+80, $y-70]]; break;
            case 2: // Big circle back
                $p = [[$x, $y], [$x+50, $y+40], [$x-180, $y+30], [$x-180, $y-80], [$x, $y-60]]; break;
            case 3: // Zigzag underline
                $p = [[$x-100, $y+30], [$x-80, $y+20], [$x-60, $y+30], [$x-40, $y+20], [$x-20, $y+30], [$x, $y+20]]; break;
            case 4: // Sharp cross
                $p = [[$x-50, $y-20], [$x+50, $y+20], [$x, $y], [$x-30, $y+20], [$x+30, $y-20]]; break;
            case 5: // Three dots
                $p = [[$x+10, $y+20], [$x+11, $y+21], [$x+25, $y+20], [$x+26, $y+21], [$x+40, $y+20], [$x+41, $y+21]]; break;
            case 6: // "X"
                $p = [[$x+10, $y-10], [$x+30, $y+10], [$x+20, $y], [$x+30, $y-10], [$x+10, $y+10]]; break;
            case 7: // Dropping tail
                $p = [[$x, $y], [$x+10, $y+60], [$x-10, $y+50]]; break;
            case 8: // Heart
                $p = [[$x, $y], [$x+10, $y-15], [$x+20, $y], [$x, $y+20], [$x-20, $y], [$x-10, $y-15], [$x, $y]]; break;
            case 9: // Parallel underlines
                $p = [[$x-100, $y+25], [$x+20, $y+25], [$x+10, $y+32], [$x-90, $y+32]]; break;
            case 10: // Circle dot
                $p = [[$x+20, $y+10], [$x+22, $y+10], [$x+21, $y+11], [$x+20, $y+10]]; break;
            case 11: // Long dash
                $p = [[$x+10, $y], [$x+60, $y]]; break;
            case 12: // Curly Q
                $p = [[$x, $y], [$x+20, $y+20], [$x+10, $y+10], [$x+30, $y-10]]; break;
        }
        return $this->arrayToPoints($p);
    }

    private function arrayToPoints($arr)
    {
        $pts = [];
        foreach ($arr as $a) $pts[] = ['x' => (float)$a[0], 'y' => (float)$a[1]];
        return $pts;
    }

    // ==========================================
    // RENDERING ENGINE
    // ==========================================

    private function drawNaturalStroke($image, $points, $color, $maxThickness)
    {
        $count = count($points);
        if ($count < 2) return;

        $prevP = null;
        $prevT = null;

        for ($i = 0; $i < $count; $i++) {
            $p = $points[$i];
            $progress = $i / ($count - 1);
            $thickness = $maxThickness;

            // Tapering
            if ($progress < 0.1) $thickness = 0.5 + ($maxThickness - 0.5) * ($progress / 0.1);
            elseif ($progress > 0.9) $thickness = 0.2 + ($maxThickness - 0.2) * ((1 - $progress) / 0.1);

            $thickness *= (0.85 + (mt_rand(0, 30) / 100));
            if ($thickness < 0.5) $thickness = 0.5;

            // Draw droplet
            if ($p['x'] >= 0 && $p['x'] < imagesx($image) && $p['y'] >= 0 && $p['y'] < imagesy($image)) {
                imagefilledellipse($image, (int)$p['x'], (int)$p['y'], (int)$thickness, (int)$thickness, $color);

                // Fill gap to previous point
                if ($prevP) {
                    $this->drawGapFill($image, $prevP, $p, $color, ($thickness + $prevT) / 2);
                }
            }
            $prevP = $p; $prevT = $thickness;
        }
    }

    private function drawGapFill($image, $p1, $p2, $color, $thickness)
    {
        $t = (int)round($thickness);
        if ($t <= 1) {
            imageline($image, (int)$p1['x'], (int)$p1['y'], (int)$p2['x'], (int)$p2['y'], $color);
        } else {
            for ($offset = -($t-1)/2; $offset <= ($t-1)/2; $offset += 0.5) {
                imageline($image, (int)($p1['x']+$offset), (int)$p1['y'], (int)($p2['x']+$offset), (int)$p2['y'], $color);
                imageline($image, (int)($p1['x']), (int)($p1['y']+$offset), (int)$p2['x'], (int)($p2['y']+$offset), $color);
            }
        }
    }

    private function smoothPath($points)
    {
        if (count($points) < 3) return $points;

        $smooth = [];
        $fullPoints = array_merge([$points[0]], $points, [end($points)]);

        for ($i = 0; $i < count($fullPoints) - 3; $i++) {
            $p1 = $fullPoints[$i+1];
            $p2 = $fullPoints[$i+2];
            $dist = sqrt(pow($p2['x'] - $p1['x'], 2) + pow($p2['y'] - $p1['y'], 2));
            $steps = max(15, (int)($dist * 2.5));

            for ($t = 0; $t < $steps; $t++) {
                $smooth[] = $this->getCatmullRomPoint($fullPoints[$i], $p1, $p2, $fullPoints[$i+3], $t / $steps);
            }
        }
        $smooth[] = end($points);
        return $smooth;
    }

    private function getCatmullRomPoint($p0, $p1, $p2, $p3, $t)
    {
        $t2 = $t * $t; $t3 = $t2 * $t;
        $f0 = -0.5 * $t3 + $t2 - 0.5 * $t;
        $f1 = 1.5 * $t3 - 2.5 * $t2 + 1.0;
        $f2 = -1.5 * $t3 + 2.0 * $t2 + 0.5 * $t;
        $f3 = 0.5 * $t3 - 0.5 * $t2;
        return [
            'x' => $p0['x'] * $f0 + $p1['x'] * $f1 + $p2['x'] * $f2 + $p3['x'] * $f3,
            'y' => $p0['y'] * $f0 + $p1['y'] * $f1 + $p2['y'] * $f2 + $p3['y'] * $f3
        ];
    }

    private function resolveColor($image, $id)
    {
        switch ($id) {
            case 0: return imagecolorallocate($image, 0, 0, 139);
            case 1: return imagecolorallocate($image, 0, 0, 0);
            case 2: return imagecolorallocate($image, 25, 25, 112);
            case 3: return imagecolorallocate($image, 40, 40, 40);
            case 4: return imagecolorallocate($image, 0, 0, 180);
            case 5: return imagecolorallocate($image, 20, 20, 20);
            case 6: return imagecolorallocate($image, 0, 50, 100);
            case 7: return imagecolorallocate($image, 30, 0, 0);
            case 8: return imagecolorallocate($image, 0, 30, 0);
            case 9: return imagecolorallocate($image, 70, 70, 90);
            default: return imagecolorallocate($image, 0, 0, 0);
        }
    }
}

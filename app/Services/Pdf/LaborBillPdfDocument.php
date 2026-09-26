<?php

namespace App\Services\Pdf;

use setasign\Fpdi\Fpdi;

/**
 * FPDF page class for the Labor billing statement (LaborBillPdfService):
 * adds rounded boxes, a "bold" look for THSarabunNew (only the regular
 * weight is installed — bold is drawn as filled + outlined text), and a
 * footer with the page number on every page.
 */
class LaborBillPdfDocument extends Fpdi
{
    /** Footer text (left side), already encoded for the PDF font. */
    public string $footerLeft = '';
    public string $fontFamily = 'THSarabunNew';
    /** Encoded "หน้า" label for the page counter. */
    public string $pageLabel = 'Page';

    /** Filled + outlined text so the regular font reads as bold. */
    public function setBold(bool $on, float $weightMm = 0.18): void
    {
        if ($this->page === 0) {
            return;
        }
        if ($on) {
            $this->_out(sprintf('2 Tr %.3F w', $weightMm * $this->k));
        } else {
            $this->_out(sprintf('0 Tr %.3F w', $this->LineWidth * $this->k));
        }
    }

    /** Text colour that also sets the outline colour (needed for setBold). */
    public function ink(int $r, int $g, int $b): void
    {
        $this->SetTextColor($r, $g, $b);
        $this->SetDrawColor($r, $g, $b);
    }

    /** Rectangle with rounded corners. $style: 'D' border, 'F' fill, 'DF' both. */
    public function roundedRect(float $x, float $y, float $w, float $h, float $r, string $style = 'D'): void
    {
        $k = $this->k;
        $hp = $this->h;
        $op = $style === 'F' ? 'f' : (($style === 'FD' || $style === 'DF') ? 'B' : 'S');
        $arc = 4 / 3 * (M_SQRT2 - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->arc($xc + $r * $arc, $yc - $r, $xc + $r, $yc - $r * $arc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->arc($xc + $r, $yc + $r * $arc, $xc + $r * $arc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->arc($xc - $r * $arc, $yc + $r, $xc - $r, $yc + $r * $arc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->arc($xc - $r, $yc - $r * $arc, $xc - $r * $arc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    protected function arc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k, ($h - $y1) * $this->k,
            $x2 * $this->k, ($h - $y2) * $this->k,
            $x3 * $this->k, ($h - $y3) * $this->k));
    }

    /** Number of lines MultiCell($w, …, $txt) will produce. */
    public function nbLines(float $w, string $txt): int
    {
        $cw = $this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] === "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c === ' ') {
                $sep = $i;
            }
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep === -1) {
                    if ($i === $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }

        return $nl;
    }

    public function Footer()
    {
        $this->SetY(-13);
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.2);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->SetFont($this->fontFamily, '', 9.5);
        $this->SetTextColor(148, 163, 184);
        $this->SetXY(12, $this->GetY() + 1.2);
        $this->Cell(130, 5, $this->footerLeft, 0, 0, 'L');
        $this->Cell(56, 5, $this->pageLabel . ' ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
}

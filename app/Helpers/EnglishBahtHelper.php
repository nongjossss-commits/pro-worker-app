<?php

namespace App\Helpers;

/**
 * English counterpart to ThaiBahtHelper::toText() — same convert(), toText()
 * shape so the two are easy to compare/reuse side by side. Used by the Pro
 * Worker Contract "ค่าบริการ" (Service Fee) template tool, which needs a
 * spelled-out amount in both languages from a single typed number (see
 * LaborContractController::resolveFeeGroupValues()).
 */
class EnglishBahtHelper
{
    public static function toText($number)
    {
        if (!$number) return 'Zero Baht Only';

        $number = number_format($number, 2, '.', '');
        $parts = explode('.', $number);
        $baht = (int) $parts[0];
        $satang = (int) $parts[1];

        $text = self::convert($baht) . ' Baht';

        if ($satang > 0) {
            $text .= ' and ' . self::convert($satang) . ' Satang';
        }

        return $text . ' Only';
    }

    /**
     * Plain integer → English words (0–999,999,999). No currency wording —
     * that's added by toText() above.
     */
    private static function convert($number)
    {
        $number = (int) $number;
        if ($number === 0) return 'Zero';

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        // Converts a 0-999 chunk to words.
        $threeDigits = function ($n) use ($ones, $tens) {
            $n = (int) $n;
            $words = [];
            if ($n >= 100) {
                $words[] = $ones[intdiv($n, 100)] . ' Hundred';
                $n %= 100;
            }
            if ($n >= 20) {
                $tensWord = $tens[intdiv($n, 10)];
                $n %= 10;
                $words[] = $n > 0 ? $tensWord . '-' . $ones[$n] : $tensWord;
            } elseif ($n > 0) {
                $words[] = $ones[$n];
            }
            return implode(' ', $words);
        };

        // Group into thousands/millions from the top down, same shape as
        // ThaiBahtHelper's place-value loop.
        $scales = ['', ' Thousand', ' Million'];
        $chunks = [];
        while ($number > 0) {
            $chunks[] = $number % 1000;
            $number = intdiv($number, 1000);
        }

        $words = [];
        for ($i = count($chunks) - 1; $i >= 0; $i--) {
            if ($chunks[$i] > 0) {
                $words[] = $threeDigits($chunks[$i]) . $scales[$i];
            }
        }

        return implode(' ', $words);
    }
}

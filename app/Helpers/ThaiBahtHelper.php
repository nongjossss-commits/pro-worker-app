<?php

namespace App\Helpers;

class ThaiBahtHelper
{
    public static function toText($number)
    {
        if (!$number) return 'ศูนย์บาทถ้วน';

        $number = number_format($number, 2, '.', '');
        $bahtText = '';
        $numbers = explode('.', $number);
        $baht = $numbers[0];
        $satang = $numbers[1];

        if ($baht > 0) {
            $bahtText .= self::convert($baht) . 'บาท';
        }

        if ($satang > 0) {
            $bahtText .= self::convert($satang) . 'สตางค์';
        } else {
            $bahtText .= 'ถ้วน';
        }

        return $bahtText;
    }

    private static function convert($number)
    {
        $values = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $places = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];

        $output = '';
        $len = strlen($number);

        for ($i = 0; $i < $len; $i++) {
            $digit = (int)$number[$i];
            $position = $len - $i - 1; // 0 for unit, 1 for tens, etc.

            if ($digit != 0) {
                // Handling specific Thai numbering rules
                if ($position == 0 && $digit == 1 && $len > 1) {
                    $val = 'เอ็ด'; // Ends in 1 (11, 21, 101) but not just "1"
                }
                elseif ($position == 1 && $digit == 2) {
                    $val = 'ยี่'; // 20 -> Yee Sib
                }
                elseif ($position == 1 && $digit == 1) {
                    $val = ''; // 10 -> Sib (not Nueng Sib)
                }
                else {
                    $val = $values[$digit];
                }

                $place = $places[$position % 6];
                $output .= $val . $place;
            }

            // Add "Lan" (Million) every 6th position from right
            if ($position > 0 && $position % 6 == 0) {
                $output .= 'ล้าน';
            }
        }

        return $output;
    }
}

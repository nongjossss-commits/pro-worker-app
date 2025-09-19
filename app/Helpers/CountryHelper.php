<?php

namespace App\Helpers;

class CountryHelper
{
    /**
     * Get the two-letter country code for a given Thai nationality name.
     *
     * @param string|null $nationality
     * @return string|null
     */
    public static function getFlagCode(?string $nationality): ?string
    {
        $flagMap = [
            'เมียนมา' => 'mm',
            'ลาว' => 'la',
            'กัมพูชา' => 'kh',
            'เวียดนาม' => 'vn',
            // Add other nationalities here if needed
        ];

        return $flagMap[$nationality] ?? null;
    }
}

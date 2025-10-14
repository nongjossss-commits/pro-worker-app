<?php

namespace App\Helpers;

class CountryHelper
{
    /**
     * Map Thai nationality names to ISO 3166-1 alpha-2 country codes.
     *
     * @param string|null $nationality The Thai name of the nationality.
     * @return string|null The two-letter country code or null if not found.
     */
    public static function getCountryCode(?string $nationality): ?string
    {
        if ($nationality === null) {
            return null;
        }

        $map = [
            'ไทย' => 'th',
            'เมียนมา' => 'mm',
            'ลาว' => 'la',
            'กัมพูชา' => 'kh',
            'เวียดนาม' => 'vn',
            // Add other nationalities as needed
        ];

        return $map[trim($nationality)] ?? null;
    }
}
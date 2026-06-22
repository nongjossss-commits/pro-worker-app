<?php

namespace App\Services;

use App\Models\SuperAdminSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Read/write the per-installation brand (logo + theme colors).
 *
 * Settings stored in `super_admin_settings` (key/value):
 *   - brand.logos          (JSON array of relative paths in storage/public)
 *   - brand.active_logo    (relative path that the sidebar should render)
 *   - brand.primary_color  (#hex)
 *   - brand.sidebar_color  (#hex)
 *   - brand.accent_color   (#hex)
 *
 * Anything missing → fall back to factory defaults so existing installs
 * keep their current orange theme without any DB rows.
 */
class BrandService
{
    public const CACHE_KEY = 'brand.settings.v1';
    public const CACHE_TTL = 600; // 10 minutes

    public const DEFAULTS = [
        'primary_color' => '#F97316',
        'sidebar_color' => '#FFFFFF',
        'accent_color'  => '#FB923C',
        'app_name'      => 'Proworker labour',
        'short_name'    => 'Proworker labour',
    ];

    /**
     * Get the resolved brand bundle, cached.
     * Returns: ['logos' => [...], 'active_logo' => ?string,
     *           'primary_color', 'sidebar_color', 'accent_color']
     */
    public static function current(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $rows = SuperAdminSetting::whereIn('key', [
                'brand.logos', 'brand.active_logo',
                'brand.primary_color', 'brand.sidebar_color', 'brand.accent_color',
                'brand.app_name', 'brand.short_name',
            ])->pluck('value', 'key');

            $logos = json_decode($rows['brand.logos'] ?? '[]', true);
            $logos = is_array($logos) ? array_values($logos) : [];

            $appName = trim((string) ($rows['brand.app_name'] ?? ''));
            $shortName = trim((string) ($rows['brand.short_name'] ?? ''));

            return [
                'logos'         => $logos,
                'active_logo'   => $rows['brand.active_logo'] ?? null,
                'primary_color' => self::sanitizeHex($rows['brand.primary_color'] ?? null, self::DEFAULTS['primary_color']),
                'sidebar_color' => self::sanitizeHex($rows['brand.sidebar_color'] ?? null, self::DEFAULTS['sidebar_color']),
                'accent_color'  => self::sanitizeHex($rows['brand.accent_color']  ?? null, self::DEFAULTS['accent_color']),
                'app_name'      => $appName !== ''  ? $appName  : self::DEFAULTS['app_name'],
                // short_name falls back to app_name (one source of truth when the
                // user only fills the long name).
                'short_name'    => $shortName !== '' ? $shortName : ($appName !== '' ? $appName : self::DEFAULTS['short_name']),
            ];
        });
    }

    /**
     * URL to the active logo for use in <img src="">. Falls back to the
     * stock asset shipped with the app so a fresh install still renders.
     */
    public static function logoUrl(): string
    {
        $brand = self::current();
        if (!empty($brand['active_logo']) && Storage::disk('public')->exists($brand['active_logo'])) {
            return asset('storage/' . $brand['active_logo']);
        }
        return asset('images/logo_new.jpg');
    }

    /**
     * Convert primary hex → "R, G, B" for use in --bs-primary-rgb.
     */
    public static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = ltrim(self::DEFAULTS['primary_color'], '#');
        }
        return hexdec(substr($hex, 0, 2)) . ', '
             . hexdec(substr($hex, 2, 2)) . ', '
             . hexdec(substr($hex, 4, 2));
    }

    /**
     * Darken a hex color by a percentage (0.0-1.0). Used to derive
     * --bs-primary-dark from the user-picked primary so hover states
     * still look right without asking the user to pick two shades.
     */
    public static function darken(string $hex, float $amount = 0.12): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return self::DEFAULTS['primary_color'];

        $r = max(0, hexdec(substr($hex, 0, 2)) * (1 - $amount));
        $g = max(0, hexdec(substr($hex, 2, 2)) * (1 - $amount));
        $b = max(0, hexdec(substr($hex, 4, 2)) * (1 - $amount));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Accept only well-formed #RRGGBB or #RGB; otherwise fall back.
     */
    protected static function sanitizeHex(?string $value, string $fallback): string
    {
        if (!is_string($value)) return $fallback;
        $value = trim($value);
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value)) {
            return strtoupper($value);
        }
        return $fallback;
    }
}

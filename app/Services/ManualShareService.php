<?php

namespace App\Services;

use App\Models\SuperAdminSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Public, read-only share links for the user manuals (Super Admin →
 * Branding). Each bundle has at most one active link: a random 40-char token
 * stored in super_admin_settings under "manual.share.{bundle}". Anyone with
 * the link sees ONLY that manual page (no login, no app navigation) and can
 * print it; regenerating or revoking the link kills the old URL at once.
 *
 * The stored value is JSON {"token", "expires_at"} (expires_at null = never).
 * Links created before expiry existed hold a bare token and never expire.
 */
class ManualShareService
{
    public const BUNDLES = [
        'main' => 'manuals._bundle',
        'finance' => 'manuals._finance_bundle',
        'training' => 'manuals._training_bundle',
        'training_finance' => 'manuals._training_finance_bundle',
    ];

    /** Languages the manuals are written in (th = default Thai files). */
    public const LANGS = ['th' => 'ไทย', 'en' => 'English', 'my' => 'မြန်မာ', 'zh' => '中文'];

    protected static function key(string $bundle): string
    {
        return 'manual.share.' . $bundle;
    }

    public static function isBundle(string $bundle): bool
    {
        return array_key_exists($bundle, self::BUNDLES);
    }

    /** How long a new link may live, in days (0 = no expiry). */
    public const EXPIRY_OPTIONS = [7, 30, 90, 0];

    /**
     * The bundle's link as ['token' => ..., 'expires_at' => ?Carbon], or null
     * when there is none. An expired link is still returned (the admin page
     * shows it as expired); use isExpired() before serving it.
     */
    public static function link(string $bundle): ?array
    {
        $value = SuperAdminSetting::where('key', self::key($bundle))->value('value');
        if ($value === null || $value === '') {
            return null;
        }

        $data = json_decode($value, true);
        if (! is_array($data)) {
            return ['token' => $value, 'expires_at' => null]; // legacy bare token
        }
        if (empty($data['token'])) {
            return null;
        }

        return [
            'token' => $data['token'],
            'expires_at' => ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
        ];
    }

    public static function isExpired(?array $link): bool
    {
        return $link !== null && $link['expires_at'] !== null && $link['expires_at']->isPast();
    }

    public static function token(string $bundle): ?string
    {
        return self::link($bundle)['token'] ?? null;
    }

    /** Create a new link (replacing — and invalidating — any previous one). */
    public static function generate(string $bundle, int $days = 0): string
    {
        $token = Str::random(40);
        SuperAdminSetting::updateOrCreate(['key' => self::key($bundle)], ['value' => json_encode([
            'token' => $token,
            'expires_at' => $days > 0 ? now()->addDays($days)->toIso8601String() : null,
        ])]);

        return $token;
    }

    public static function revoke(string $bundle): void
    {
        SuperAdminSetting::where('key', self::key($bundle))->delete();
    }

    /**
     * Which bundle a token belongs to — ['bundle' => ..., 'expired' => bool] —
     * or null if it isn't (or is no longer) a current link.
     */
    public static function lookup(string $token): ?array
    {
        foreach (array_keys(self::BUNDLES) as $bundle) {
            $link = self::link($bundle);
            if ($link !== null && hash_equals($link['token'], $token)) {
                return ['bundle' => $bundle, 'expired' => self::isExpired($link)];
            }
        }

        return null;
    }

    /** Which bundle a token opens, or null if it isn't valid or has expired. */
    public static function bundleForToken(string $token): ?string
    {
        $found = self::lookup($token);

        return $found !== null && ! $found['expired'] ? $found['bundle'] : null;
    }

    public static function url(string $bundle, ?string $lang = null): ?string
    {
        $token = self::token($bundle);
        if ($token === null) {
            return null;
        }

        $params = ['token' => $token];
        if ($lang && $lang !== 'th' && array_key_exists($lang, self::LANGS)) {
            $params['lang'] = $lang;
        }

        return route('manuals.public', $params);
    }

    /** ?lang=xx → that language for this request only (manual pages). */
    public static function applyLang(?string $lang): void
    {
        if ($lang && array_key_exists($lang, self::LANGS)) {
            app()->setLocale($lang);
        }
    }
}

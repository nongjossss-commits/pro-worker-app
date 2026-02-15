<?php

namespace App\Services;

use App\Models\SuperAdminSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class SuperAdminService
{
    /**
     * Cache key for settings.
     */
    const CACHE_KEY = 'super_admin_settings';

    /**
     * Get all settings (cached).
     */
    public function getSettings()
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return SuperAdminSetting::all()->keyBy('key');
        });
    }

    /**
     * Check if a menu is visible.
     */
    public function isVisible($key)
    {
        $settings = $this->getSettings();
        if (isset($settings[$key])) {
            return $settings[$key]->is_visible;
        }
        // Default to visible if not set
        return true;
    }

    /**
     * Check if a menu has a password set.
     */
    public function hasPassword($key)
    {
        $settings = $this->getSettings();
        if (isset($settings[$key]) && !empty($settings[$key]->access_password)) {
            return true;
        }
        return false;
    }

    /**
     * Check if the current session has unlocked the menu.
     */
    public function isUnlocked($key)
    {
        if (!$this->hasPassword($key)) {
            return true;
        }

        $sessionKey = 'menu_unlocked_' . $key;
        $expiry = Session::get($sessionKey);

        if ($expiry && now()->timestamp < $expiry) {
            return true;
        }

        return false;
    }

    /**
     * Verify password and unlock the menu for 30 minutes.
     */
    public function unlock($key, $password)
    {
        $settings = $this->getSettings();
        $setting = $settings[$key] ?? null;

        if (!$setting || empty($setting->access_password)) {
            return true; // No password needed
        }

        if (Hash::check($password, $setting->access_password)) {
            // Set session expiry to 30 minutes from now
            Session::put('menu_unlocked_' . $key, now()->addMinutes(30)->timestamp);
            return true;
        }

        return false;
    }

    /**
     * Clear the cache (call this after updating settings).
     */
    public function clearCache()
    {
        Cache::forget(self::CACHE_KEY);
    }
}

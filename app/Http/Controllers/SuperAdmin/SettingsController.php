<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        // Define all menu keys and their labels
        $menus = [
            'dashboard' => 'Dashboard',
            'finance' => 'Finance (การเงิน)',
            'financial_profiles' => 'Financial Profiles (ข้อมูลผู้ตั้งบิล)',
            'activity_logs' => 'Activity Logs',
            'notifications' => 'Notifications',
            'incomplete_data' => 'Incomplete Data',
            'ticket_inbox' => 'Ticket Inbox',
            'employer_ticket' => 'Employer Ticket',
            'employers' => 'Employers',
            'employees' => 'Employees',
            'production' => 'P Production',
            'workflow' => 'Workflow',
            'registration_resolution' => 'Registration Resolution',
            'renewal_resolution' => 'Renewal Resolution',
            'importers' => 'Importers',
            'agents' => 'Agents',
            'delegates' => 'Delegates',
            'user_management' => 'User Management',
            'roles_permissions' => 'Roles & Permissions',
            'pdf_templates' => 'PDF Templates',
            'central_trash' => 'Central Trash',
        ];

        // Fetch current settings from DB
        $settings = SuperAdminSetting::all()->keyBy('key');

        return view('super-admin.settings', compact('menus', 'settings'));
    }

    /**
     * Update a specific menu setting.
     */
    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'is_visible' => 'nullable|boolean',
            'password' => 'nullable|string|confirmed', // expects password_confirmation field
            'remove_password' => 'nullable|boolean',
        ]);

        $key = $request->input('key');
        $setting = SuperAdminSetting::firstOrNew(['key' => $key]);

        // Toggle visibility
        if ($request->has('is_visible')) {
            $setting->is_visible = $request->boolean('is_visible');
        }

        // Handle password update
        if ($request->filled('password')) {
            $setting->access_password = Hash::make($request->input('password'));
        } elseif ($request->boolean('remove_password')) {
            $setting->access_password = null;
        }

        $setting->save();

        // Clear cache to apply changes immediately
        Cache::forget(\App\Services\SuperAdminService::CACHE_KEY);

        return redirect()->route('super-admin.settings.index')->with('success', 'Settings for ' . $key . ' updated successfully.');
    }

    /**
     * Show the menu unlock form.
     */
    public function unlockForm($key)
    {
        return view('auth.menu-unlock', compact('key'));
    }

    /**
     * Process the menu unlock.
     */
    public function unlock(Request $request, $key)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $setting = SuperAdminSetting::where('key', $key)->first();

        // If no password is set, or setting doesn't exist, just let them through (or redirect to home)
        if (!$setting || empty($setting->access_password)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->intended('/dashboard');
        }

        if (Hash::check($request->password, $setting->access_password)) {
            // Unlock session for 30 minutes
            Session::put('menu_unlocked_' . $key, now()->addMinutes(30)->timestamp);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->intended('/dashboard'); // Go to where they wanted to go
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Incorrect password.'], 401);
        }

        return back()->withErrors(['password' => 'Incorrect password for this menu.']);
    }

    /**
     * Check if a menu is unlocked (AJAX).
     */
    public function checkAccess(Request $request, $key)
    {
        // 1. Check if invisible (global disable)
        $setting = SuperAdminSetting::where('key', $key)->first();
        $isVisible = $setting ? $setting->is_visible : true; // Default true if no record

        if (!$isVisible) {
            return response()->json([
                'locked' => true,
                'reason' => 'disabled',
                'message' => 'This feature is currently disabled by the administrator.'
            ]);
        }

        // 2. Check Password
        if (!$setting || empty($setting->access_password)) {
            return response()->json(['locked' => false]);
        }

        // 3. Check Session
        $sessionKey = 'menu_unlocked_' . $key;
        $expiry = Session::get($sessionKey);

        if ($expiry && now()->timestamp < $expiry) {
            // Unlocked and valid. Extend session on activity.
            Session::put($sessionKey, now()->addMinutes(30)->timestamp);
            return response()->json(['locked' => false]);
        }

        return response()->json(['locked' => true, 'reason' => 'password']);
    }

    /**
     * Update menu visibility via AJAX.
     */
    public function updateVisibility(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'is_visible' => 'required|boolean',
        ]);

        $key = $request->input('key');
        $isVisible = $request->boolean('is_visible');

        // Logic Change: If setting to visible and no password, delete the record to use default (true)
        // This prevents issues where 'true' is stored but not read correctly, or cache issues.
        $setting = SuperAdminSetting::firstOrNew(['key' => $key]);

        if ($isVisible && empty($setting->access_password)) {
            if ($setting->exists) {
                $setting->delete();
            }
            // If it was new, we just don't create it.
        } else {
            $setting->is_visible = $isVisible;
            $setting->save();
        }

        // Clear cache to apply changes immediately
        Cache::forget(\App\Services\SuperAdminService::CACHE_KEY);

        return response()->json(['success' => true]);
    }

    /**
     * Render the sidebar menu HTML.
     */
    public function renderSidebar(Request $request)
    {
        // Force cache clear if requested (e.g., immediately after update)
        if ($request->has('refresh')) {
            Cache::forget(\App\Services\SuperAdminService::CACHE_KEY);
        }

        return response(view('partials.sidebar-menu'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}

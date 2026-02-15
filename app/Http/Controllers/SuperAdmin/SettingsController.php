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
            return redirect()->intended('/dashboard');
        }

        if (Hash::check($request->password, $setting->access_password)) {
            // Unlock session for 30 minutes
            Session::put('menu_unlocked_' . $key, now()->addMinutes(30)->timestamp);
            return redirect()->intended('/dashboard'); // Go to where they wanted to go
        }

        return back()->withErrors(['password' => 'Incorrect password for this menu.']);
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
        $setting = SuperAdminSetting::firstOrNew(['key' => $key]);
        $setting->is_visible = $request->boolean('is_visible');
        $setting->save();

        // Clear cache to apply changes immediately
        Cache::forget(\App\Services\SuperAdminService::CACHE_KEY);

        return response()->json(['success' => true]);
    }

    /**
     * Render the sidebar menu HTML.
     */
    public function renderSidebar()
    {
        return view('partials.sidebar-menu');
    }
}

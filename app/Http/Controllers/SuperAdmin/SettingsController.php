<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminSetting;
use App\Models\DownloadProfile;
use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        // Define all menu keys and their labels.
        //
        // KEEP THIS IN SYNC WITH:
        //   1. resources/views/partials/sidebar-menu.blade.php (every @if(SuperAdmin::isVisible('xxx'))
        //      must have a matching row here, and every row here must be referenced in the sidebar)
        //   2. database/seeders/SuperAdminSeeder.php $keys array (so fresh installs seed the default rows)
        //   3. Spatie permission @can()/@role() guards in the sidebar — when adding a new menu, also
        //      decide which roles should see it and update RoleAndPermissionSeeder accordingly
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
            'sales' => 'Read and Sale (การขายและใบเสนอราคา)',
            'production' => 'P Production',
            'workflow' => 'Workflow',
            'registration_resolution' => 'Registration Resolution',
            'renewal_resolution' => 'Renewal Resolution',
            'importers' => 'Importers',
            'agents' => 'Agents',
            'delegates' => 'Delegates',
            'user_management' => 'User Management',
            'pdf_templates' => 'PDF Templates',
            'central_trash' => 'Central Trash',
        ];

        // Fetch current settings from DB
        $settings = SuperAdminSetting::all()->keyBy('key');

        $profiles = DownloadProfile::latest()->get();

        // Employee cap (system-wide), driven by Super Admin.
        $maxEmployees = \App\Services\EmployeeQuotaService::getMax();
        $currentEmployees = \App\Services\EmployeeQuotaService::getCurrentCount();

        // Brand settings (logo + theme colors).
        $brand = \App\Services\BrandService::current();

        return view('super-admin.settings', compact('menus', 'settings', 'profiles', 'maxEmployees', 'currentEmployees', 'brand'));
    }

    /**
     * Upload a new brand logo. Stored in storage/app/public/brand/logos/
     * and appended to the brand.logos array. First upload also becomes
     * the active logo so the change is visible immediately.
     */
    public function uploadBrandLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $path = $request->file('logo')->store('brand/logos', 'public');

        $brand = \App\Services\BrandService::current();
        $logos = $brand['logos'];
        $logos[] = ['path' => $path, 'uploaded_at' => now()->toDateTimeString()];

        SuperAdminSetting::updateOrCreate(
            ['key' => 'brand.logos'],
            ['value' => json_encode(array_values($logos))]
        );

        // First upload? auto-select it.
        if (empty($brand['active_logo'])) {
            SuperAdminSetting::updateOrCreate(
                ['key' => 'brand.active_logo'],
                ['value' => $path]
            );
        }

        \App\Services\BrandService::forgetCache();

        return redirect()->route('super-admin.settings.index', ['tab' => 'branding'])
            ->with('success', __('Logo uploaded.'));
    }

    public function setActiveBrandLogo(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $brand = \App\Services\BrandService::current();
        $paths = array_column($brand['logos'], 'path');
        abort_unless(in_array($request->path, $paths, true), 404);

        SuperAdminSetting::updateOrCreate(
            ['key' => 'brand.active_logo'],
            ['value' => $request->path]
        );

        \App\Services\BrandService::forgetCache();

        return redirect()->route('super-admin.settings.index', ['tab' => 'branding'])
            ->with('success', __('Active logo updated.'));
    }

    public function deleteBrandLogo(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $target = $request->path;

        $brand = \App\Services\BrandService::current();
        $logos = array_values(array_filter(
            $brand['logos'],
            fn ($l) => ($l['path'] ?? null) !== $target
        ));

        SuperAdminSetting::updateOrCreate(
            ['key' => 'brand.logos'],
            ['value' => json_encode($logos)]
        );

        // If we just removed the active one, clear the pointer.
        if (($brand['active_logo'] ?? null) === $target) {
            SuperAdminSetting::where('key', 'brand.active_logo')->delete();
        }

        Storage::disk('public')->delete($target);

        \App\Services\BrandService::forgetCache();

        return redirect()->route('super-admin.settings.index', ['tab' => 'branding'])
            ->with('success', __('Logo deleted.'));
    }

    public function updateBrandColors(Request $request)
    {
        // NOTE: must use array-of-rules (not pipe-string), otherwise Laravel
        // splits on the "|" inside the regex pattern and the parser blows up.
        $request->validate([
            'primary_color' => ['required', 'regex:/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
            'sidebar_color' => ['required', 'regex:/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
            'accent_color'  => ['required', 'regex:/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
        ]);

        foreach (['primary_color', 'sidebar_color', 'accent_color'] as $key) {
            SuperAdminSetting::updateOrCreate(
                ['key' => 'brand.' . $key],
                ['value' => strtoupper($request->input($key))]
            );
        }

        \App\Services\BrandService::forgetCache();

        return redirect()->route('super-admin.settings.index', ['tab' => 'branding'])
            ->with('success', __('Theme colors updated.'));
    }

    /**
     * Show all in-app user manuals on a single printable HTML page.
     * The browser's "Print → Save as PDF" generates a branded training booklet
     * with the installation's logo + theme color baked in.
     */
    public function manualBundle()
    {
        return view('manuals._bundle');
    }

    /**
     * Finance-only manual bundle — separately distributed because the Finance
     * feature is sold as a licensed add-on module (see Service Contract clause 8).
     */
    public function financeManualBundle()
    {
        return view('manuals._finance_bundle');
    }

    /**
     * Training Edition bundle — slide-friendly layout with annotated screenshots,
     * optimized for projection during training sessions or printing as a workbook.
     * Excludes Finance (which lives in trainingFinanceBundle).
     */
    public function trainingBundle()
    {
        return view('manuals._training_bundle');
    }

    /**
     * Training Edition Finance bundle — slide-friendly add-on training booklet
     * for the Finance module. Includes Sales (quotation flow) + Finance + Profiles.
     */
    public function trainingFinanceBundle()
    {
        return view('manuals._training_finance_bundle');
    }

    public function resetBrandColors()
    {
        SuperAdminSetting::whereIn('key', [
            'brand.primary_color', 'brand.sidebar_color', 'brand.accent_color',
        ])->delete();

        \App\Services\BrandService::forgetCache();

        return redirect()->route('super-admin.settings.index', ['tab' => 'branding'])
            ->with('success', __('Theme colors reset to defaults.'));
    }

    /**
     * Update the installation's app name (long + short).
     * Short name is what fits in the sidebar header next to the logo;
     * long name is what shows on the welcome/landing surfaces.
     */
    public function updateBrandName(Request $request)
    {
        $request->validate([
            'app_name'   => 'required|string|max:100',
            'short_name' => 'nullable|string|max:60',
        ]);

        SuperAdminSetting::updateOrCreate(
            ['key' => 'brand.app_name'],
            ['value' => trim($request->input('app_name'))]
        );

        // Short name is optional; empty string → delete the row so the
        // fallback in BrandService kicks in (uses app_name).
        $short = trim((string) $request->input('short_name'));
        if ($short === '') {
            SuperAdminSetting::where('key', 'brand.short_name')->delete();
        } else {
            SuperAdminSetting::updateOrCreate(
                ['key' => 'brand.short_name'],
                ['value' => $short]
            );
        }

        \App\Services\BrandService::forgetCache();

        return redirect()->route('super-admin.settings.index', ['tab' => 'branding'])
            ->with('success', __('App name updated.'));
    }

    /**
     * Save the system-wide max employees cap. Empty value or 0 = unlimited.
     */
    public function updateMaxEmployees(Request $request)
    {
        $request->validate([
            'max_employees' => 'nullable|integer|min:0|max:1000000',
        ]);

        $value = $request->input('max_employees');
        SuperAdminSetting::updateOrCreate(
            ['key' => \App\Services\EmployeeQuotaService::SETTING_KEY],
            ['value' => ($value === null || $value === '') ? null : (string) $value]
        );

        \App\Services\EmployeeQuotaService::forgetCache();

        return redirect()->route('super-admin.settings.index', ['tab' => 'employee-cap'])
            ->with('success', __('Employee cap updated.'));
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

    public function swapAttachments(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|in:employer,employee',
            'from_field' => 'required|string',
            'to_field' => 'required|string',
            'mode' => 'required|in:swap,move_delete',
        ]);

        $entityType = $request->entity_type;
        $fromField = $request->from_field;
        $toField = $request->to_field;
        $mode = $request->mode;

        if ($fromField === $toField) {
            return back()->with('error', 'Cannot swap the same field.');
        }

        DB::beginTransaction();
        try {
            if ($entityType === 'employee') {
                $records = Employee::all();
            } else {
                $records = Employer::all();
            }

            foreach ($records as $record) {
                $fromPath = $record->{$fromField};
                $toPath = $record->{$toField};

                if ($mode === 'swap') {
                    // Simple swap
                    $record->{$fromField} = $toPath;
                    $record->{$toField} = $fromPath;
                } else {
                    // Move and delete
                    // Move $fromPath to $toField. Delete what was in $toField.
                    if ($toPath) {
                        Storage::disk('public')->delete($toPath);
                    }
                    $record->{$toField} = $fromPath;
                    $record->{$fromField} = null;
                }

                // Optimization: Avoid firing full model events if we only update two columns
                $record->saveQuietly();
            }

            DB::commit();

            return redirect()->route('super-admin.settings.index', ['tab' => 'attachment-settings'])
                             ->with('success', ucfirst($entityType) . " files swapped successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('super-admin.settings.index', ['tab' => 'attachment-settings'])
                             ->with('error', 'Error swapping files: ' . $e->getMessage());
        }
    }

    /**
     * Save the Program Pricelist config (SaaS pricing shown on the printable pricelist page).
     * Stored as a single JSON blob in super_admin_settings.program_pricelist_config.
     */
    public function saveProgramPricelist(Request $request)
    {
        $request->validate([
            'title'       => 'nullable|string|max:255',
            'subtitle'    => 'nullable|string|max:255',
            'currency'    => 'nullable|string|max:5',
            'footer_note' => 'nullable|string|max:1000',
            'tiers'       => 'nullable|array',
            'tiers.*.label'      => 'nullable|string|max:100',
            'tiers.*.sublabel'   => 'nullable|string|max:100',
            'tiers.*.setup_fee'  => 'nullable|numeric|min:0',
            'tiers.*.annual_fee' => 'nullable|numeric|min:0',
            'features'    => 'nullable|array',
            'features.*.name'  => 'nullable|string|max:255',
            'features.*.scope' => 'nullable|string|max:500',
        ]);

        $config = [
            'title'       => trim((string) $request->input('title', '')),
            'subtitle'    => trim((string) $request->input('subtitle', '')),
            'currency'    => trim((string) $request->input('currency', 'THB')) ?: 'THB',
            'footer_note' => trim((string) $request->input('footer_note', '')),
            'tiers'       => array_values(array_map(function ($t) {
                return [
                    'label'      => trim((string) ($t['label'] ?? '')),
                    'sublabel'   => trim((string) ($t['sublabel'] ?? '')),
                    'setup_fee'  => (float) ($t['setup_fee'] ?? 0),
                    'annual_fee' => (float) ($t['annual_fee'] ?? 0),
                ];
            }, $request->input('tiers', []))),
            'features'    => array_values(array_map(function ($f) {
                return [
                    'name'  => trim((string) ($f['name'] ?? '')),
                    'scope' => trim((string) ($f['scope'] ?? '')),
                ];
            }, $request->input('features', []))),
        ];

        // Drop blank tiers / features
        $config['tiers']    = array_values(array_filter($config['tiers'],    fn($t) => $t['label']   !== '' || $t['setup_fee'] > 0 || $t['annual_fee'] > 0));
        $config['features'] = array_values(array_filter($config['features'], fn($f) => $f['name']    !== '' || $f['scope']     !== ''));

        SuperAdminSetting::updateOrCreate(
            ['key' => 'program_pricelist_config'],
            ['value' => json_encode($config, JSON_UNESCAPED_UNICODE)]
        );

        return redirect()->route('super-admin.settings.index', ['tab' => 'program-pricelist'])
                         ->with('success', 'Program pricelist saved.');
    }

    /**
     * Save the Provider Info (used as the issuer on Pricelist + Trial + Service contracts).
     */
    public function saveProviderInfo(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'address'      => 'nullable|string|max:1000',
            'tax_id'       => 'nullable|string|max:13',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'website'      => 'nullable|string|max:255',
            'signer_name'  => 'nullable|string|max:255',
            'signer_title' => 'nullable|string|max:255',
        ]);

        $provider = [
            'name'         => trim($request->input('name', '')),
            'address'      => trim($request->input('address', '')),
            'tax_id'       => trim($request->input('tax_id', '')),
            'phone'        => trim($request->input('phone', '')),
            'email'        => trim($request->input('email', '')),
            'website'      => trim($request->input('website', '')),
            'signer_name'  => trim($request->input('signer_name', '')),
            'signer_title' => trim($request->input('signer_title', '')),
        ];

        SuperAdminSetting::updateOrCreate(
            ['key' => 'program_provider_info'],
            ['value' => json_encode($provider, JSON_UNESCAPED_UNICODE)]
        );

        return redirect()->route('super-admin.settings.index', ['tab' => 'program-pricelist'])
                         ->with('success', 'Provider info saved.');
    }

    /**
     * Render a printable Trial or Service contract from query params + provider config.
     * Customer info is supplied per-document (not stored).
     */
    public function programContractView(Request $request, string $type)
    {
        if (!in_array($type, ['trial', 'service'], true)) {
            abort(404);
        }

        $providerRow = SuperAdminSetting::where('key', 'program_provider_info')->value('value');
        $provider = $providerRow ? json_decode($providerRow, true) : [];
        $provider = is_array($provider) ? $provider : [];

        // Sensible defaults if provider config not set yet
        $provider = array_replace([
            'name' => '— ยังไม่ได้ตั้งค่าผู้ให้บริการ —',
            'address' => '', 'tax_id' => '', 'phone' => '', 'email' => '', 'website' => '',
            'signer_name' => '', 'signer_title' => '',
        ], $provider);

        $customer = [
            'name'        => trim((string) $request->query('customer_name', '')),
            'tax_id'      => trim((string) $request->query('customer_tax_id', '')),
            'phone'       => trim((string) $request->query('customer_phone', '')),
            'email'       => trim((string) $request->query('customer_email', '')),
            'address'     => trim((string) $request->query('customer_address', '')),
            'signer_name' => trim((string) $request->query('customer_signer_name', '')),
            'signer_title'=> trim((string) $request->query('customer_signer_title', '')),
        ];

        $witnesses = [
            ['name' => trim((string) $request->query('witness_1_name', '')), 'title' => trim((string) $request->query('witness_1_title', ''))],
            ['name' => trim((string) $request->query('witness_2_name', '')), 'title' => trim((string) $request->query('witness_2_title', ''))],
        ];

        $contract = [
            'type' => $type,
            'date' => date('Y-m-d'),
            'trial_start' => $request->query('trial_start'),
            'trial_end'   => $request->query('trial_end'),
            'test_server_url' => trim((string) $request->query('test_server_url', '')),
            'service_start' => $request->query('service_start'),
            'service_years' => (int) ($request->query('service_years', 1)),
            'package'     => null,
        ];

        // Parse the package_tier from the service form: "label|sublabel|setup|annual"
        if ($type === 'service' && $request->filled('package_tier')) {
            $parts = explode('|', $request->query('package_tier'));
            if (count($parts) === 4) {
                $contract['package'] = [
                    'label'      => $parts[0],
                    'sublabel'   => $parts[1],
                    'setup_fee'  => (float) $parts[2],
                    'annual_fee' => (float) $parts[3],
                ];
            }
        }

        // Language selection: up to 2 of th/en/zh/my, default to Thai only
        // (matches the original single-language behaviour when untouched).
        $requestedLangs = (array) $request->query('langs', ['th']);
        $langs = array_values(array_intersect(['th', 'en', 'zh', 'my'], $requestedLangs));
        $langs = array_slice($langs, 0, 2);
        if (empty($langs)) $langs = ['th'];

        $allLangData = config("contracts.{$type}", []);
        $langData = array_intersect_key($allLangData, array_flip($langs));

        $view = $type === 'trial' ? 'super-admin.contract-trial' : 'super-admin.contract-service';
        return view($view, compact('provider', 'customer', 'witnesses', 'contract', 'langs', 'langData'));
    }

    /**
     * Public printable view of the Program Pricelist — designed to be opened in a new tab
     * and printed (Ctrl+P) to PDF. Uses Brand Settings for logo + name + primary color.
     */
    public function programPricelistView()
    {
        $row = SuperAdminSetting::where('key', 'program_pricelist_config')->first();
        $config = $row ? json_decode($row->value, true) : [];
        $config = is_array($config) ? $config : [];

        $defaults = [
            'title' => 'Pro-Worker — PRICELIST',
            'subtitle' => date('Y') . ' Q' . ceil(date('n') / 3),
            'currency' => 'THB',
            'footer_note' => '',
            'tiers' => [],
            'features' => [],
        ];
        $config = array_replace_recursive($defaults, $config);

        return view('super-admin.program-pricelist', ['config' => $config]);
    }

    /**
     * Per-slot update: save one slot's default to SuperAdminSetting AND force-update
     * every existing record's description column to match. Returns JSON for AJAX.
     */
    public function updateSingleAttachmentDescription(Request $request)
    {
        $request->validate([
            'entity' => 'required|in:employer,employee',
            'slot' => 'required|integer|min:1|max:10',
            'value' => 'nullable|string|max:255',
        ]);

        $entity = $request->entity;
        $slot = (int) $request->slot;
        $value = trim((string) $request->input('value', ''));

        // Cap slots by entity (employer = 3, employee = 10)
        if ($entity === 'employer' && $slot > 3) {
            return response()->json(['success' => false, 'message' => 'Employer has only 3 slots.'], 422);
        }

        $settingKey = $entity === 'employer'
            ? "employer_other_{$slot}_desc"
            : "employee_other_{$slot}_desc";

        $dbColumn = $entity === 'employer'
            ? "employer_doc_other_{$slot}_desc"
            : "other_doc_{$slot}_desc";

        DB::beginTransaction();
        try {
            // 1) Save default to SuperAdminSetting (drives auto-apply for future records)
            SuperAdminSetting::updateOrCreate(['key' => $settingKey], ['value' => $value]);

            // 2) Force-update every existing record to match
            $affected = 0;
            if ($entity === 'employer') {
                $affected = Employer::query()->update([$dbColumn => $value !== '' ? $value : null]);
            } else {
                $affected = Employee::query()->update([$dbColumn => $value !== '' ? $value : null]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Slot {$slot} ของ" . ($entity === 'employer' ? 'นายจ้าง' : 'ลูกจ้าง') . " อัพเดตแล้ว ({$affected} records)",
                'affected' => $affected,
                'value' => $value,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateAttachmentDescriptions(Request $request)
    {
        $request->validate([
            'employer_other_1_desc' => 'nullable|string|max:255',
            'employer_other_2_desc' => 'nullable|string|max:255',
            'employer_other_3_desc' => 'nullable|string|max:255',
        ]);

        // Validate employee descriptions (1 to 10)
        $employeeRules = [];
        for ($i = 1; $i <= 10; $i++) {
            $employeeRules["employee_other_{$i}_desc"] = 'nullable|string|max:255';
        }
        $request->validate($employeeRules);

        // Save to Settings
        $settingData = [];
        for ($i = 1; $i <= 3; $i++) {
            $key = "employer_other_{$i}_desc";
            if ($request->has($key)) {
                $settingData[$key] = $request->input($key);
                SuperAdminSetting::updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            $key = "employee_other_{$i}_desc";
            if ($request->has($key)) {
                $settingData[$key] = $request->input($key);
                SuperAdminSetting::updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }

        // Apply changes to existing records
        DB::beginTransaction();
        try {
            // Update Employers
            $employerUpdates = [];
            for ($i = 1; $i <= 3; $i++) {
                $inputKey = "employer_other_{$i}_desc";
                $dbCol = "employer_doc_other_{$i}_desc";
                if ($request->has($inputKey) && !empty($request->input($inputKey))) {
                    $employerUpdates[$dbCol] = $request->input($inputKey);
                }
            }
            if (!empty($employerUpdates)) {
                Employer::query()->update($employerUpdates);
            }

            // Update Employees
            $employeeUpdates = [];
            for ($i = 1; $i <= 10; $i++) {
                $inputKey = "employee_other_{$i}_desc";
                $dbCol = "other_doc_{$i}_desc";
                if ($request->has($inputKey) && !empty($request->input($inputKey))) {
                    $employeeUpdates[$dbCol] = $request->input($inputKey);
                }
            }
            if (!empty($employeeUpdates)) {
                Employee::query()->update($employeeUpdates);
            }

            DB::commit();

            return redirect()->route('super-admin.settings.index', ['tab' => 'attachment-settings'])
                             ->with('success', 'Attachment descriptions updated globally and default values saved.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('super-admin.settings.index', ['tab' => 'attachment-settings'])
                             ->with('error', 'Error updating descriptions: ' . $e->getMessage());
        }
    }
}

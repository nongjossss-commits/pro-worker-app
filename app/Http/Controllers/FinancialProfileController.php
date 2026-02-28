<?php

namespace App\Http\Controllers;

use App\Models\FinancialProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FinancialProfileController extends Controller
{
    // Fetch all profiles
    public function index(Request $request)
    {
        $type = $request->query('type');
        $query = FinancialProfile::query();
        if ($type) {
            $query->where('type', $type);
        }
        return response()->json($query->latest()->get());
    }

    // Return view for managing/building profiles
    public function builder()
    {
        return view('finance.profiles.builder');
    }

    // Store a new profile
    public function store(Request $request)
    {
        // Decode JSON strings from FormData before validation
        if ($request->has('signature_position') && is_string($request->signature_position)) {
            $request->merge(['signature_position' => json_decode($request->signature_position, true)]);
        }
        if ($request->has('stamp_position') && is_string($request->stamp_position)) {
            $request->merge(['stamp_position' => json_decode($request->stamp_position, true)]);
        }

        $validated = $request->validate([
            'type' => 'required|in:biller,customer',
            'name' => 'required|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'stamp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature_position' => 'nullable|array',
            'stamp_position' => 'nullable|array',
        ]);

        $data = $request->only(['type', 'name', 'tax_id', 'branch', 'address', 'phone', 'email', 'signature_position', 'stamp_position']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('financial_profiles/logos', 'public');
        }
        if ($request->hasFile('signature')) {
            $data['signature_path'] = $request->file('signature')->store('financial_profiles/signatures', 'public');
        }
        if ($request->hasFile('stamp')) {
            $data['stamp_path'] = $request->file('stamp')->store('financial_profiles/stamps', 'public');
        }

        $profile = FinancialProfile::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'profile' => $profile]);
        }

        return redirect()->back()->with('success', 'Profile created successfully.');
    }

    // Show a specific profile
    public function show(FinancialProfile $profile)
    {
        return response()->json($profile);
    }

    // Update an existing profile
    public function update(Request $request, FinancialProfile $profile)
    {
        // Decode JSON strings from FormData before validation
        if ($request->has('signature_position') && is_string($request->signature_position)) {
            $request->merge(['signature_position' => json_decode($request->signature_position, true)]);
        }
        if ($request->has('stamp_position') && is_string($request->stamp_position)) {
            $request->merge(['stamp_position' => json_decode($request->stamp_position, true)]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'stamp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'signature_position' => 'nullable|array',
            'stamp_position' => 'nullable|array',
            'remove_logo' => 'nullable|boolean',
            'remove_signature' => 'nullable|boolean',
            'remove_stamp' => 'nullable|boolean',
        ]);

        $data = $request->only(['name', 'tax_id', 'branch', 'address', 'phone', 'email', 'signature_position', 'stamp_position']);

        // Handle File Removals
        if ($request->input('remove_logo') && $profile->logo_path) {
            Storage::disk('public')->delete($profile->logo_path);
            $data['logo_path'] = null;
        }
        if ($request->input('remove_signature') && $profile->signature_path) {
            Storage::disk('public')->delete($profile->signature_path);
            $data['signature_path'] = null;
            $data['signature_position'] = null;
        }
        if ($request->input('remove_stamp') && $profile->stamp_path) {
            Storage::disk('public')->delete($profile->stamp_path);
            $data['stamp_path'] = null;
            $data['stamp_position'] = null;
        }

        // Handle File Uploads
        if ($request->hasFile('logo')) {
            if ($profile->logo_path) Storage::disk('public')->delete($profile->logo_path);
            $data['logo_path'] = $request->file('logo')->store('financial_profiles/logos', 'public');
        }
        if ($request->hasFile('signature')) {
            if ($profile->signature_path) Storage::disk('public')->delete($profile->signature_path);
            $data['signature_path'] = $request->file('signature')->store('financial_profiles/signatures', 'public');
        }
        if ($request->hasFile('stamp')) {
            if ($profile->stamp_path) Storage::disk('public')->delete($profile->stamp_path);
            $data['stamp_path'] = $request->file('stamp')->store('financial_profiles/stamps', 'public');
        }

        $profile->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'profile' => $profile->fresh()]);
        }

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    // Delete a profile
    public function destroy(FinancialProfile $profile)
    {
        if ($profile->logo_path) Storage::disk('public')->delete($profile->logo_path);
        if ($profile->signature_path) Storage::disk('public')->delete($profile->signature_path);
        if ($profile->stamp_path) Storage::disk('public')->delete($profile->stamp_path);

        $profile->delete();

        return response()->json(['success' => true]);
    }
}

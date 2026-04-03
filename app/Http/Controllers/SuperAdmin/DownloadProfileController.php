<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DownloadProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadProfileController extends Controller
{
    public function index()
    {
        return redirect()->route('super-admin.settings.index', ['tab' => 'download-profiles']);
    }

    public function create()
    {
        return view('super-admin.download-profiles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = $request->only(['name', 'phone_number']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('download_profiles', 'public');
        }

        DownloadProfile::create($data);

        return redirect()->route('super-admin.settings.index', ['tab' => 'download-profiles'])->with('success', 'Download Profile created successfully.');
    }

    public function edit(DownloadProfile $downloadProfile)
    {
        return view('super-admin.download-profiles.edit', compact('downloadProfile'));
    }

    public function update(Request $request, DownloadProfile $downloadProfile)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = $request->only(['name', 'phone_number']);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($downloadProfile->logo_path) {
                Storage::disk('public')->delete($downloadProfile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('download_profiles', 'public');
        }

        $downloadProfile->update($data);

        return redirect()->route('super-admin.settings.index', ['tab' => 'download-profiles'])->with('success', 'Download Profile updated successfully.');
    }

    public function destroy(DownloadProfile $downloadProfile)
    {
        if ($downloadProfile->logo_path) {
            Storage::disk('public')->delete($downloadProfile->logo_path);
        }
        $downloadProfile->delete();

        return redirect()->route('super-admin.settings.index', ['tab' => 'download-profiles'])->with('success', 'Download Profile deleted successfully.');
    }
}

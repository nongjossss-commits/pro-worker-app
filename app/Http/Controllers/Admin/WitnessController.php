<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Witness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WitnessController extends Controller
{
    public function index()
    {
        $witnesses = Witness::latest()->get();
        return response()->json($witnesses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_th' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'signature_file' => 'nullable|image|max:2048',
        ]);

        $witness = new Witness();
        $witness->name_th = $request->name_th;
        $witness->name_en = $request->name_en;

        if ($request->hasFile('signature_file')) {
            $path = $request->file('signature_file')->store('signatures/witnesses', 'public');
            $witness->signature_path = $path;
        }

        $witness->save();

        return response()->json([
            'success' => true,
            'message' => 'Witness added successfully.',
            'witness' => $witness
        ]);
    }

    public function update(Request $request, $id)
    {
        $witness = Witness::findOrFail($id);

        $request->validate([
            'name_th' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'signature_file' => 'nullable|image|max:2048',
        ]);

        $witness->name_th = $request->name_th;
        $witness->name_en = $request->name_en;

        if ($request->hasFile('signature_file')) {
            if ($witness->signature_path && Storage::disk('public')->exists($witness->signature_path)) {
                Storage::disk('public')->delete($witness->signature_path);
            }
            $path = $request->file('signature_file')->store('signatures/witnesses', 'public');
            $witness->signature_path = $path;
        }

        $witness->save();

        return response()->json([
            'success' => true,
            'message' => 'Witness updated successfully.',
            'witness' => $witness
        ]);
    }

    public function destroy($id)
    {
        $witness = Witness::findOrFail($id);

        if ($witness->signature_path && Storage::disk('public')->exists($witness->signature_path)) {
            Storage::disk('public')->delete($witness->signature_path);
        }

        $witness->delete();

        return response()->json([
            'success' => true,
            'message' => 'Witness deleted successfully.'
        ]);
    }
}

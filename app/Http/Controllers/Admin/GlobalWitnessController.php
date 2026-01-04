<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalWitness;
use App\Services\SignatureGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GlobalWitnessController extends Controller
{
    protected $signatureService;

    public function __construct(SignatureGeneratorService $signatureService)
    {
        $this->signatureService = $signatureService;
        // Require manage-tickets permissions which is the general admin capability in this system
        $this->middleware('permission:manage-tickets');
    }

    public function index()
    {
        // Ensure 4 witnesses exist
        $witnesses = collect();
        for ($i = 1; $i <= 4; $i++) {
            $alias = "witness_{$i}";
            $witness = GlobalWitness::firstOrCreate(
                ['alias' => $alias],
                ['name_th' => "พยาน {$i}", 'name_en' => "Witness {$i}"]
            );
            $witnesses->push($witness);
        }

        return view('admin.witnesses.index', compact('witnesses'));
    }

    public function update(Request $request, $id)
    {
        $witness = GlobalWitness::findOrFail($id);

        $request->validate([
            'name_th' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'signature_action' => 'nullable|in:keep,upload,generate',
            'signature_file' => 'nullable|required_if:signature_action,upload|image|max:2048',
        ]);

        $witness->name_th = $request->name_th;
        $witness->name_en = $request->name_en;

        $action = $request->input('signature_action', 'keep');

        if ($action === 'upload' && $request->hasFile('signature_file')) {
            // Delete old
            if ($witness->signature_path && Storage::disk('public')->exists($witness->signature_path)) {
                Storage::disk('public')->delete($witness->signature_path);
            }
            // Save new
            $path = $request->file('signature_file')->store('signatures/witnesses', 'public');
            $witness->signature_path = $path;

        } elseif ($action === 'generate') {
            // Delete old
            if ($witness->signature_path && Storage::disk('public')->exists($witness->signature_path)) {
                Storage::disk('public')->delete($witness->signature_path);
            }
            // Generate new
            // Use a random seed based on time + alias to ensure uniqueness every time "Generate" is clicked
            $seed = $witness->alias . '_' . time();
            $sigContent = $this->signatureService->generate($seed);

            $filename = 'signatures/witnesses/' . $witness->alias . '_' . time() . '.png';
            Storage::disk('public')->put($filename, $sigContent);
            $witness->signature_path = $filename;
        }

        $witness->save();

        return redirect()->route('admin.witnesses.index')->with('success', 'Witness updated successfully.');
    }
}

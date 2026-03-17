<?php

namespace App\Http\Controllers;

use App\Models\Importer;
use Illuminate\Http\Request;

class ImporterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $importers = Importer::all();
        return view('importers.index', compact('importers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('importers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'importerNameTh' => 'required|string|max:255',
            'importerNameEn' => 'nullable|string|max:255',
            'importerId' => 'nullable|string|max:255',
            'importerLicenseNo' => 'nullable|string|max:255',
            'importerLicenseIssueDate' => 'nullable|date',
            'importerLicenseExpiryDate' => 'nullable|date',
            'importerSignerTh' => 'nullable|string|max:255',
            'importerSignerEn' => 'nullable|string|max:255',
            'signer_2_name_th' => 'nullable|string|max:255',
            'signer_2_name_en' => 'nullable|string|max:255',
            'importer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_1_desc' => 'nullable|string|max:255',
            'importer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_2_desc' => 'nullable|string|max:255',
            'importer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_3_desc' => 'nullable|string|max:255',
            'signature_1_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'signature_2_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'importer_stamp_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $data = $request->all();

        // Remove signature temp fields from mass assignment
        unset(
            $data['signature_1_action'], $data['signature_1_file'], $data['signature_1_base64'],
            $data['signature_2_action'], $data['signature_2_file'], $data['signature_2_base64'],
            $data['importer_stamp_action'], $data['importer_stamp_file'], $data['importer_stamp_base64']
        );

        $docFields = ['importer_doc_other_1', 'importer_doc_other_2', 'importer_doc_other_3'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('importer_documents', 'public');
            }
        }

        $importer = Importer::create($data);

        // Process temporary addresses from request
        if ($request->filled('registered_addresses')) {
            $addrs = json_decode($request->input('registered_addresses'), true);
            if (is_array($addrs)) {
                foreach ($addrs as $addr) {
                    $addr['type'] = 'registered';
                    $importer->addresses()->create($addr);
                }
            }
        }
        if ($request->filled('workplace_addresses')) {
            $addrs = json_decode($request->input('workplace_addresses'), true);
            if (is_array($addrs)) {
                foreach ($addrs as $addr) {
                    $addr['type'] = 'workplace';
                    $importer->addresses()->create($addr);
                }
            }
        }

        return redirect()->route('importers.index')
            ->with('success', 'เพิ่มข้อมูลบริษัทนำเข้าเรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(Importer $importer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Importer $importer)
    {
        return view('importers.edit', compact('importer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Importer $importer)
    {
        // Validate and update the importer
        $request->validate([
            'importerNameTh' => 'required|string|max:255',
            'importerNameEn' => 'nullable|string|max:255',
            'importerId' => 'nullable|string|max:255',
            'importerLicenseNo' => 'nullable|string|max:255',
            'importerLicenseIssueDate' => 'nullable|date',
            'importerLicenseExpiryDate' => 'nullable|date',
            'importerSignerTh' => 'nullable|string|max:255',
            'importerSignerEn' => 'nullable|string|max:255',
            'signer_2_name_th' => 'nullable|string|max:255',
            'signer_2_name_en' => 'nullable|string|max:255',
            'importer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_1_desc' => 'nullable|string|max:255',
            'importer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_2_desc' => 'nullable|string|max:255',
            'importer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_3_desc' => 'nullable|string|max:255',
            'signature_1_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'signature_2_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'importer_stamp_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $data = $request->all();

        // Remove signature temp fields from mass assignment
        unset(
            $data['signature_1_action'], $data['signature_1_file'], $data['signature_1_base64'],
            $data['signature_2_action'], $data['signature_2_file'], $data['signature_2_base64'],
            $data['importer_stamp_action'], $data['importer_stamp_file'], $data['importer_stamp_base64']
        );

        // Process Signature 1
        $sig1Action = $request->input('signature_1_action', 'keep');
        if ($sig1Action === 'upload' && $request->hasFile('signature_1_file')) {
             if ($importer->signature_1_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->signature_1_path);
             $data['signature_1_path'] = $request->file('signature_1_file')->store('signatures/importers', 'public');
        } elseif ($sig1Action === 'draw' && $request->filled('signature_1_base64')) {
             if ($importer->signature_1_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->signature_1_path);
             $base64Image = $request->input('signature_1_base64');
             $imageInfo = explode(";base64,", $base64Image);
             $image = str_replace(' ', '+', $imageInfo[1] ?? '');
             $filename = 'sig_1_' . time() . '.png';
             $path = 'signatures/importers/' . $filename;
             \Illuminate\Support\Facades\Storage::disk('public')->put($path, base64_decode($image));
             $data['signature_1_path'] = $path;
        } elseif ($sig1Action === 'generate') {
             if ($importer->signature_1_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->signature_1_path);
             $data['signature_1_path'] = null; // System will generate on-the-fly or we handle in PDF
        }

        // Process Signature 2
        $sig2Action = $request->input('signature_2_action', 'keep');
        if ($sig2Action === 'upload' && $request->hasFile('signature_2_file')) {
             if ($importer->signature_2_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->signature_2_path);
             $data['signature_2_path'] = $request->file('signature_2_file')->store('signatures/importers', 'public');
        } elseif ($sig2Action === 'draw' && $request->filled('signature_2_base64')) {
             if ($importer->signature_2_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->signature_2_path);
             $base64Image = $request->input('signature_2_base64');
             $imageInfo = explode(";base64,", $base64Image);
             $image = str_replace(' ', '+', $imageInfo[1] ?? '');
             $filename = 'sig_2_' . time() . '.png';
             $path = 'signatures/importers/' . $filename;
             \Illuminate\Support\Facades\Storage::disk('public')->put($path, base64_decode($image));
             $data['signature_2_path'] = $path;
        } elseif ($sig2Action === 'generate') {
             if ($importer->signature_2_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->signature_2_path);
             $data['signature_2_path'] = null;
        }

        // Process Importer Stamp
        $stampAction = $request->input('importer_stamp_action', 'keep');
        if ($stampAction === 'upload' && $request->hasFile('importer_stamp_file')) {
            if ($importer->importer_stamp_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->importer_stamp_path);
            $data['importer_stamp_path'] = $request->file('importer_stamp_file')->store('signatures/importers', 'public');
        } elseif ($stampAction === 'draw' && $request->filled('importer_stamp_base64')) {
            if ($importer->importer_stamp_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->importer_stamp_path);
            $base64Image = $request->input('importer_stamp_base64');
            $imageInfo = explode(";base64,", $base64Image);
            $image = str_replace(' ', '+', $imageInfo[1] ?? '');
            $filename = 'stamp_' . time() . '.png';
            $path = 'signatures/importers/' . $filename;
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, base64_decode($image));
            $data['importer_stamp_path'] = $path;
        }

        $docFields = ['importer_doc_other_1', 'importer_doc_other_2', 'importer_doc_other_3'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                if ($importer->{$field}) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($importer->{$field});
                }
                $data[$field] = $request->file($field)->store('importer_documents', 'public');
            }
        }

        $importer->update($data);

        return redirect()->route('importers.index')
            ->with('success', 'อัปเดตข้อมูลบริษัทนำเข้าเรียบร้อยแล้ว');
    }

    public function downloadDocumentAsPdf(Request $request, Importer $importer, $field)
    {
        $allowedFields = [
            'importer_doc_other_1',
            'importer_doc_other_2',
            'importer_doc_other_3'
        ];

        if (!in_array($field, $allowedFields)) {
            abort(404, 'Document type not found.');
        }

        $filePath = $importer->{$field};
        $disk = 'public';

        if (!$filePath || !\Illuminate\Support\Facades\Storage::disk($disk)->exists($filePath)) {
            abort(404, 'File not found.');
        }

        $fieldMapping = [
            'importer_doc_other_1' => 'Other_1',
            'importer_doc_other_2' => 'Other_2',
            'importer_doc_other_3' => 'Other_3',
        ];

        $docName = $fieldMapping[$field] ?? $field;
        $name = $importer->importerNameEn ?: 'Importer_' . $importer->id;
        $name = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $name);

        $filename = "{$docName}_{$name}.pdf";
        $disposition = $request->input('disposition', 'inline');

        return \App\Helpers\PdfHelper::streamFile($disk, $filePath, $disposition, $filename);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Importer $importer)
    {
        try {
            $importer->delete();
            return response()->json(['success' => 'ลบข้อมูลบริษัทนำเข้าเรียบร้อยแล้ว']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not delete importer.'], 500);
        }
    }
}

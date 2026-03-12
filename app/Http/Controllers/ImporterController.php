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
            'importer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_1_desc' => 'nullable|string|max:255',
            'importer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_2_desc' => 'nullable|string|max:255',
            'importer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_3_desc' => 'nullable|string|max:255',
        ]);

        $data = $request->all();

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
            'importer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_1_desc' => 'nullable|string|max:255',
            'importer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_2_desc' => 'nullable|string|max:255',
            'importer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'importer_doc_other_3_desc' => 'nullable|string|max:255',
        ]);

        $data = $request->all();

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

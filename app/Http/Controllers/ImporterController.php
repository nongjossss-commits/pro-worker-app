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
        // return view('importers.index', compact('importers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // return view('importers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'importerNameTh' => 'nullable',
            'importerNameEn' => 'nullable',
            'importerId' => 'nullable',
            'importerLicenseNo' => 'nullable',
            'importerLicenseIssueDate' => 'nullable|date',
            'importerLicenseExpiryDate' => 'nullable|date',
            'importerSignerTh' => 'nullable',
            'importerSignerEn' => 'nullable',
        ]);

        Importer::create($request->all());

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
        // return view('importers.edit', compact('importer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Importer $importer)
    {
        $request->validate([
            'importerNameTh' => 'nullable',
            'importerNameEn' => 'nullable',
            'importerId' => 'nullable',
            'importerLicenseNo' => 'nullable',
            'importerLicenseIssueDate' => 'nullable|date',
            'importerLicenseExpiryDate' => 'nullable|date',
            'importerSignerTh' => 'nullable',
            'importerSignerEn' => 'nullable',
        ]);

        $importer->update($request->all());

        return redirect()->route('importers.index')
            ->with('success', 'อัปเดตข้อมูลบริษัทนำเข้าเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Importer $importer)
    {
        $importer->delete();

        return redirect()->route('importers.index')
            ->with('success', 'ลบข้อมูลบริษัทนำเข้าเรียบร้อยแล้ว');
    }
}

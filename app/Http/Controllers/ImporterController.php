<?php

namespace App\Http\Controllers;

use App\Models\Importer;
use Illuminate\Http\Request;

class ImporterController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-importers', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-importers', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-importers', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-importers', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $query = Importer::query();

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('importerNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('importerNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('importerId', 'like', "%{$searchTerm}%");
            });
        }

        $importers = $query->latest()->paginate(10);
        return view('importers.index', compact('importers'));
    }

    public function create()
    {
        $lastImporter = Importer::orderBy('id', 'desc')->first();
        $nextId = $lastImporter ? (int)substr($lastImporter->importerId, 4) + 1 : 1;
        $newImporterId = 'IMP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('importers.create', compact('newImporterId'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'importerId' => 'required|unique:importers,importerId',
            'importerNameTh' => 'required|string|max:255',
            'importerNameEn' => 'nullable|string|max:255',
        ]);

        Importer::create($validatedData);

        return redirect()->route('importers.index')->with('success', 'Importer created successfully.');
    }

    public function show(Importer $importer)
    {
        return view('importers.show', compact('importer'));
    }

    public function edit(Importer $importer)
    {
        return view('importers.edit', compact('importer'));
    }

    public function update(Request $request, Importer $importer)
    {
        $validatedData = $request->validate([
            'importerNameTh' => 'required|string|max:255',
            'importerNameEn' => 'nullable|string|max:255',
        ]);

        $importer->update($validatedData);

        return redirect()->route('importers.index')->with('success', 'Importer updated successfully.');
    }

    public function destroy(Importer $importer)
    {
        $importer->delete();
        return redirect()->route('importers.index')->with('success', 'Importer moved to trash.');
    }
}
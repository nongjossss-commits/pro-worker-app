<?php

namespace App\Http\Controllers;

use App\Models\Delegate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DelegateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-delegates', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-delegates', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-delegates', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-delegates', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $query = Delegate::query();

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('delegateNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('delegateNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('delegateId', 'like', "%{$searchTerm}%");
            });
        }

        $delegates = $query->latest()->paginate(10);
        return view('delegates.index', compact('delegates'));
    }

    public function create()
    {
        return view('delegates.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'delegateNameTh' => 'required|string|max:255',
            'delegateNameEn' => 'nullable|string|max:255',
            'delegateId' => 'required|unique:delegates,delegateId',
            'delegateTel' => 'nullable|string|max:20',
            'delegateLineId' => 'nullable|string|max:255',
            'delegateEmail' => 'nullable|email|max:255',
            'delegatePhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('delegatePhoto')) {
            $validatedData['delegatePhoto'] = $request->file('delegatePhoto')->store('delegate_photos', 'public');
        }

        Delegate::create($validatedData);

        return redirect()->route('delegates.index')->with('success', 'Delegate created successfully.');
    }

    public function show(Delegate $delegate)
    {
        return view('delegates.show', compact('delegate'));
    }

    public function edit(Delegate $delegate)
    {
        return view('delegates.edit', compact('delegate'));
    }

    public function update(Request $request, Delegate $delegate)
    {
        $validatedData = $request->validate([
            'delegateNameTh' => 'required|string|max:255',
            'delegateNameEn' => 'nullable|string|max:255',
            'delegateId' => 'required|unique:delegates,delegateId,' . $delegate->id,
            'delegateTel' => 'nullable|string|max:20',
            'delegateLineId' => 'nullable|string|max:255',
            'delegateEmail' => 'nullable|email|max:255',
            'delegatePhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('delegatePhoto')) {
            // Delete old photo if it exists
            if ($delegate->delegatePhoto) {
                Storage::disk('public')->delete($delegate->delegatePhoto);
            }
            $validatedData['delegatePhoto'] = $request->file('delegatePhoto')->store('delegate_photos', 'public');
        }

        $delegate->update($validatedData);

        return redirect()->route('delegates.index')->with('success', 'Delegate updated successfully.');
    }

    public function destroy(Delegate $delegate)
    {
        $delegate->delete();
        return redirect()->route('delegates.index')->with('success', 'Delegate moved to trash.');
    }
}
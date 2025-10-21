<?php

namespace App\Http\Controllers;

use App\Models\Delegate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DelegateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $delegates = Delegate::all();
        return view('delegates.index', compact('delegates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('delegates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'delegateNameTh' => 'required',
            'delegateNameEn' => 'required',
            'delegateId' => 'required',
            'delegateEmployeeId' => 'required',
            'delegateIssueDate' => 'required|date',
            'delegateExpiryDate' => 'required|date',
            'delegatePhone' => 'required',
            'delegateEmail' => 'required|email',
            'delegatePhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('delegatePhoto')) {
            $path = $request->file('delegatePhoto')->store('delegate_photos', 'public');
            $data['delegatePhoto'] = $path;
        }

        Delegate::create($data);

        return redirect()->route('delegates.index')
                        ->with('success','Delegate created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Delegate $delegate)
    {
        return view('delegates.show',compact('delegate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Delegate $delegate)
    {
        return view('delegates.edit',compact('delegate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Delegate $delegate)
    {
        $request->validate([
            'delegateNameTh' => 'required',
            'delegateNameEn' => 'required',
            'delegateId' => 'required',
            'delegateEmployeeId' => 'required',
            'delegateIssueDate' => 'required|date',
            'delegateExpiryDate' => 'required|date',
            'delegatePhone' => 'required',
            'delegateEmail' => 'required|email',
            'delegatePhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('delegatePhoto')) {
            // Delete old photo
            if ($delegate->delegatePhoto) {
                Storage::disk('public')->delete($delegate->delegatePhoto);
            }
            $path = $request->file('delegatePhoto')->store('delegate_photos', 'public');
            $data['delegatePhoto'] = $path;
        }

        $delegate->update($data);

        return redirect()->route('delegates.index')
                        ->with('success','Delegate updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Delegate $delegate)
    {
        try {
            if ($delegate->delegatePhoto) {
                Storage::disk('public')->delete($delegate->delegatePhoto);
            }
            $delegate->delete();
            return response()->json(['success' => 'Delegate deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not delete delegate.'], 500);
        }
    }
}

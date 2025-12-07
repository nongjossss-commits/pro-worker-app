<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkflowBarrier;
use Illuminate\Http\Request;

class WorkflowBarrierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $barriers = WorkflowBarrier::orderBy('sequence')->get();
        return view('admin.production.barriers.index', compact('barriers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
            'sequence' => 'nullable|integer',
        ]);

        WorkflowBarrier::create($request->all());

        return redirect()->route('admin.production.barriers.index')->with('success', 'Barrier created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkflowBarrier $barrier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
            'sequence' => 'nullable|integer',
        ]);

        $barrier->update($request->all());

        return redirect()->route('admin.production.barriers.index')->with('success', 'Barrier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkflowBarrier $barrier)
    {
        $barrier->delete();
        return redirect()->route('admin.production.barriers.index')->with('success', 'Barrier deleted successfully.');
    }
}

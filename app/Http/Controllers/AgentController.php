<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $agents = Agent::all();
        return view('agents.index', compact('agents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('agents.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'agentNameEn' => 'required|string|max:255',
            'agentLicense' => 'nullable|string|max:255',
            'agentPhone' => 'nullable|string|max:255',
            'agentEmail' => 'nullable|email|max:255',
            'agentAddress' => 'nullable|string',
        ]);

        Agent::create($request->all());

        return redirect()->route('agents.index')
            ->with('success', 'เพิ่มข้อมูลเอเจนซี่เรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(Agent $agent)
    {
        // Not implemented as per requirements
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Agent $agent)
    {
        return view('agents.edit', compact('agent'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent)
    {
        $request->validate([
            'agentNameEn' => 'required|string|max:255',
            'agentLicense' => 'nullable|string|max:255',
            'agentPhone' => 'nullable|string|max:255',
            'agentEmail' => 'nullable|email|max:255',
            'agentAddress' => 'nullable|string',
        ]);

        $agent->update($request->all());

        return redirect()->route('agents.index')
            ->with('success', 'อัปเดตข้อมูลเอเจนซี่เรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agent $agent)
    {
        try {
            $agent->delete();
            return response()->json(['success' => 'ลบข้อมูลเอเจนซี่เรียบร้อยแล้ว']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not delete agent.'], 500);
        }
    }
}

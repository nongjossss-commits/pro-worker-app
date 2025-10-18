<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-agents', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-agents', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-agents', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-agents', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $query = Agent::query();

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('agentNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('agentNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('agentId', 'like', "%{$searchTerm}%");
            });
        }

        $agents = $query->latest()->paginate(10);
        return view('agents.index', compact('agents'));
    }

    public function create()
    {
        $lastAgent = Agent::orderBy('id', 'desc')->first();
        $nextId = $lastAgent ? (int)substr($lastAgent->agentId, 4) + 1 : 1;
        $newAgentId = 'AGT-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('agents.create', compact('newAgentId'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'agentId' => 'required|unique:agents,agentId',
            'agentNameTh' => 'required|string|max:255',
            'agentNameEn' => 'nullable|string|max:255',
        ]);

        Agent::create($validatedData);

        return redirect()->route('agents.index')->with('success', 'Agent created successfully.');
    }

    public function show(Agent $agent)
    {
        return view('agents.show', compact('agent'));
    }

    public function edit(Agent $agent)
    {
        return view('agents.edit', compact('agent'));
    }

    public function update(Request $request, Agent $agent)
    {
        $validatedData = $request->validate([
            'agentNameTh' => 'required|string|max:255',
            'agentNameEn' => 'nullable|string|max:255',
        ]);

        $agent->update($validatedData);

        return redirect()->route('agents.index')->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent)
    {
        $agent->delete();
        return redirect()->route('agents.index')->with('success', 'Agent moved to trash.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentController extends Controller
{
    /**
     * Validation rules ของ Agent — ใช้ทั้ง store และ update
     */
    private const AGENT_RULES = [
        'agentNameEn'   => 'required|string|max:255',
        'agentLicense'  => 'nullable|string|max:255',
        'agentPhone'    => 'nullable|string|max:255',
        'agentEmail'    => 'nullable|email|max:255',
        'agentAddress'  => 'nullable|string',
        'agent_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:30720',
        'agent_doc_other_1_desc' => 'nullable|string|max:255',
        'agent_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:30720',
        'agent_doc_other_2_desc' => 'nullable|string|max:255',
        'agent_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:30720',
        'agent_doc_other_3_desc' => 'nullable|string|max:255',
    ];

    /** Fields handled via Storage — kept out of mass-assignment until uploaded. */
    private const AGENT_DOC_FIELDS = ['agent_doc_other_1', 'agent_doc_other_2', 'agent_doc_other_3'];

    /**
     * Authorization: ต้องเป็น admin/super-admin หรือมี permission view-agents/manage-agents
     * (Spatie's Gate::before bypass admin/super-admin อยู่แล้วใน AuthServiceProvider)
     */
    public function __construct()
    {
        $this->middleware('permission:view-agents')->only(['index', 'show']);
        $this->middleware('permission:create-agents')->only(['create', 'store']);
        $this->middleware('permission:edit-agents')->only(['edit', 'update']);
        $this->middleware('permission:delete-agents')->only(['destroy']);
    }

    public function index()
    {
        $agents = Agent::all();
        return view('agents.index', compact('agents'));
    }

    public function create()
    {
        return view('agents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(self::AGENT_RULES);

        foreach (self::AGENT_DOC_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('agent_documents', 'public');
            }
        }

        Agent::create($validated);

        return redirect()->route('agents.index')
            ->with('success', 'เพิ่มข้อมูลเอเจนซี่เรียบร้อยแล้ว');
    }

    public function show(Agent $agent)
    {
        // Not implemented as per requirements
    }

    public function edit(Agent $agent)
    {
        return view('agents.edit', compact('agent'));
    }

    public function update(Request $request, Agent $agent)
    {
        $validated = $request->validate(self::AGENT_RULES);

        foreach (self::AGENT_DOC_FIELDS as $field) {
            if ($request->hasFile($field)) {
                if ($agent->{$field}) {
                    Storage::disk('public')->delete($agent->{$field});
                }
                $validated[$field] = $request->file($field)->store('agent_documents', 'public');
            }
        }

        $agent->update($validated);

        return redirect()->route('agents.index')
            ->with('success', 'อัปเดตข้อมูลเอเจนซี่เรียบร้อยแล้ว');
    }

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

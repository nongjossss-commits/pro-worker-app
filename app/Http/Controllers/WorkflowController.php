<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem; // Added
use App\Models\WorkflowBarrier;
use App\Models\WorkflowStep; // Added
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    /**
     * Display a listing of Active Production Orders (Workflow).
     */
    public function index()
    {
        $orders = ProductionOrder::with('employer')
                    ->where('status', '!=', 'pre_production') // Active, Completed, Cancelled
                    ->withCount('items')
                    ->latest()
                    ->paginate(15);

        return view('workflow.index', compact('orders'));
    }

    /**
     * Display the specified resource (The Kanban/Board View).
     */
    public function show($id)
    {
        $production = ProductionOrder::with(['items.employee', 'items.currentBarrier', 'employer', 'items.steps'])->findOrFail($id);

        if ($production->status === 'pre_production') {
            return redirect()->route('production.edit', $production->id);
        }

        $barriers = WorkflowBarrier::orderBy('sequence')->get();

        // Ensure items without a barrier are assigned to the first one
        if ($barriers->isNotEmpty()) {
            $firstBarrierId = $barriers->first()->id;
            foreach ($production->items as $item) {
                if (!$item->current_barrier_id) {
                    $item->update(['current_barrier_id' => $firstBarrierId]);
                }
            }
        }

        return view('workflow.board', compact('production', 'barriers'));
    }

    /**
     * API: Update Item Barrier (Drag & Drop)
     */
    public function updateItemBarrier(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:production_items,id',
            'barrier_id' => 'required|exists:workflow_barriers,id'
        ]);

        $item = ProductionItem::findOrFail($request->item_id);
        $item->update(['current_barrier_id' => $request->barrier_id]);

        return response()->json(['success' => true]);
    }

    /**
     * API: Bulk Add Step (Create Fields)
     */
    public function bulkStoreStep(Request $request)
    {
        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:production_items,id',
            'step_type' => 'required|in:text,date,file',
            'label' => 'required|string|max:255',
            'value' => 'nullable'
        ]);

        foreach ($request->item_ids as $id) {
            WorkflowStep::create([
                'production_item_id' => $id,
                'step_type' => $request->step_type,
                'label' => $request->label,
                'value_text' => $request->step_type === 'text' ? $request->value : null,
                'value_date' => $request->step_type === 'date' ? $request->value : null,
                'created_by' => auth()->id()
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Show a specific item detail (Step tracker).
     */
    public function showItem($item_id)
    {
        $item = ProductionItem::with(['steps', 'employee', 'order'])->findOrFail($item_id);
        return view('workflow.item_detail', compact('item'));
    }

    /**
     * Store a new step for an item.
     */
    public function storeStep(Request $request, $item_id)
    {
        $item = ProductionItem::findOrFail($item_id);

        $request->validate([
            'step_type' => 'required|in:text,date,file',
            'label' => 'required|string|max:255',
            'value' => 'nullable'
        ]);

        WorkflowStep::create([
            'production_item_id' => $item->id,
            'step_type' => $request->step_type,
            'label' => $request->label,
            'value_text' => $request->step_type === 'text' ? $request->value : null,
            'value_date' => $request->step_type === 'date' ? $request->value : null,
            'created_by' => auth()->id()
        ]);

        return back()->with('success', 'Step added successfully');
    }
}

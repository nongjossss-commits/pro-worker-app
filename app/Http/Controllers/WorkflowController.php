<?php

namespace App\Http\Controllers;

use App\Models\ProductionItem;
use App\Models\WorkflowStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkflowController extends Controller
{
    /**
     * Show the timeline for a specific item (employee in a project).
     */
    public function showItem($itemId)
    {
        $item = ProductionItem::with(['order', 'employee', 'steps.barrier', 'steps.creator', 'currentBarrier'])
                ->findOrFail($itemId);

        return view('production.workflow_item_timeline', compact('item'));
    }

    /**
     * Add a step to the timeline.
     */
    public function storeStep(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);

        $request->validate([
            'step_type' => 'required|in:text,date,file,barrier',
            'label' => 'nullable|string|max:255',
            'barrier_id' => 'required_if:step_type,barrier|exists:workflow_barriers,id',
            'value_file' => 'required_if:step_type,file|file|max:10240', // 10MB
        ]);

        $data = [
            'production_item_id' => $item->id,
            'step_type' => $request->step_type,
            'label' => $request->label,
            'created_by' => auth()->id(),
        ];

        if ($request->step_type === 'text') {
            $data['value_text'] = $request->value_text;
        } elseif ($request->step_type === 'date') {
            $data['value_date'] = $request->value_date;
        } elseif ($request->step_type === 'file') {
            if ($request->hasFile('value_file')) {
                $path = $request->file('value_file')->store('workflow_files', 'public');
                $data['file_path'] = $path;
                // Use the original filename as text value for display if needed
                $data['value_text'] = $request->file('value_file')->getClientOriginalName();
            }
        } elseif ($request->step_type === 'barrier') {
            $data['barrier_id'] = $request->barrier_id;

            // UPDATE THE CURRENT STATUS of the item
            $item->current_barrier_id = $request->barrier_id;
            $item->save();
        }

        WorkflowStep::create($data);

        return back()->with('success', 'Step added.');
    }
}

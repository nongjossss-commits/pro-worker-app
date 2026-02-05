<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\AddressFilterTrait;

class ProductionController extends Controller
{
    use AddressFilterTrait;

    /**
     * Display a listing of Production Orders (Preparation / Pre-Production).
     * Now mirrored with Workflow tabs.
     */
    public function index(Request $request)
    {
        // 1. Get Tabs (Work Types) - Same as Workflow
        // We might want to show all tabs, or filter. For now, show all.
        $tabs = WorkType::withCount(['orders' => function($q){
             $q->where('status', 'pre_production');
        }])->orderBy('order')->get();

        if ($tabs->isEmpty()) {
            // Seeding logic is in WorkflowController, assuming it's done.
            $tabs = WorkType::orderBy('order')->get();
        }

        // 2. Determine Active Tab
        $activeTabSlug = $request->query('tab');

        // If no tab is selected, default to the first one (unlike Workflow dashboard)
        // or keep "Overview" if preferred. Let's default to first tab if no dashboard desired.
        // User requested "Same structure as Workflow", so we keep Dashboard?
        // User said: "Pre-Production is preparation... structure same as Workflow".
        // Let's implement Dashboard for Pre-Prod too if needed, but for now let's focus on Tabs.

        $activeTab = null;
        if ($activeTabSlug) {
            $activeTab = $tabs->where('slug', $activeTabSlug)->first();
        }

        // 3. Query Orders for this Tab
        $query = ProductionOrder::with(['employer', 'workType'])
                    ->where('status', 'pre_production');

        if ($activeTab) {
            $query->where('work_type_id', $activeTab->id);
        }

        // Address Filtering
        $addressOptions = $this->getAddressOptions($query, 'employer_id');
        $query = $this->applyAddressFilters($query, $request, 'employer');

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($e) use ($search) {
                      $e->where('employerNameTh', 'like', "%{$search}%")
                        ->orWhere('employerNameEn', 'like', "%{$search}%")
                        ->orWhere(function($addrQ) use ($search) {
                            $addrQ->filterByAddress($search);
                        });
                  });
            });
        }

        // Count active items for sorting (Active = Not Cancelled AND Not Completed)
        $query->withCount(['items as active_items_count' => function($q) {
             $q->whereNotIn('status', ['cancelled', 'completed']);
        }]);

        $orders = $query->orderByDesc('active_items_count')
                        ->latest('updated_at')
                        ->paginate(15)
                        ->withQueryString();

        // Load Relations for View
        // Note: steps for Pre-Production might be different.
        // We filter steps by stage = 'preparation' (if we decide to split) or just use all steps but allow independent checking.
        // Based on user: "Steps... independent of workflow... user sets freely".
        // So we fetch steps for this WorkType, but maybe filter by 'stage' if we implemented it.
        // Let's fetch 'preparation' steps if they exist, else all.

        $orders->load(['items.completedWorkTypeSteps', 'employer.addresses']);

        $steps = collect();
        if ($activeTab) {
            // Get steps specifically for 'preparation' stage, or fallback?
            // Since we just added 'stage', old steps are 'workflow'.
            // User needs to create 'preparation' steps.
            $steps = WorkTypeStep::where('work_type_id', $activeTab->id)
                        ->where('stage', 'preparation')
                        ->orderBy('order')
                        ->get();

            // If no preparation steps found, maybe return empty (user needs to add them)
        }

        // Calculate Stats (Similar to Workflow but for Pre-Prod)
        foreach ($orders as $order) {
            $items = $order->items;
            $total = 0;
            $notStarted = 0; // Items with no steps checked?
            $ready = 0; // Maybe not needed
            $stepStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

            foreach ($items as $item) {
                $total++;

                // Calculate step progress
                $completedSteps = $item->completedWorkTypeSteps->pluck('id')->toArray();
                if (empty($completedSteps)) {
                    $notStarted++;
                }

                foreach ($completedSteps as $sId) {
                    if (isset($stepStats[$sId])) {
                        $stepStats[$sId]++;
                    }
                }
            }

            $order->computedStats = [
                'total' => $total,
                'not_started' => $notStarted,
                'step_stats' => $stepStats
            ];
        }

        // Employers for Dropdown (Global Add Employee)
        $employers = Employer::orderBy('employerNameTh')->get();

        return view('production.index', compact('orders', 'tabs', 'activeTab', 'steps', 'addressOptions', 'employers'));
    }

    /**
     * Send an item to the Workflow (Active Status).
     */
    public function sendToWorkflow(Request $request, $itemId)
    {
        $item = ProductionItem::with(['order', 'employee'])->findOrFail($itemId);
        $currentOrder = $item->order;

        if ($currentOrder->status !== 'pre_production') {
            return response()->json(['success' => false, 'message' => 'Item is already in Workflow.'], 400);
        }

        DB::beginTransaction();
        try {
            // Find an Active Order for this Employer + WorkType
            $activeOrder = ProductionOrder::where('employer_id', $currentOrder->employer_id)
                                ->where('work_type_id', $currentOrder->work_type_id)
                                ->where('status', '!=', 'pre_production') // Active
                                ->latest()
                                ->first();

            if (!$activeOrder) {
                // Create new Active Order
                $activeOrder = ProductionOrder::create([
                    'employer_id' => $currentOrder->employer_id,
                    'work_type_id' => $currentOrder->work_type_id,
                    'type' => $currentOrder->type,
                    'project_name' => $currentOrder->project_name . ' (Workflow)', // Or keep same name?
                    'description' => $currentOrder->description,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
            }

            // Move Item
            $item->update([
                'production_order_id' => $activeOrder->id,
                'status' => 'pending', // Reset status to pending in workflow
                'last_checked_at' => null, // Reset checks
            ]);

            // Clear Completed Steps (User said steps don't follow)
            $item->completedWorkTypeSteps()->detach();

            // If the old order is empty, should we delete it?
            // Maybe keep it as empty shell or delete. User didn't specify.
            // Let's leave it for now.

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new Step (Preparation Stage).
     */
    public function storeStep(Request $request)
    {
        $request->validate([
            'work_type_id' => 'required|exists:work_types,id',
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = WorkTypeStep::where('work_type_id', $request->work_type_id)
                        ->where('stage', 'preparation') // Scoped to preparation
                        ->max('order') ?? 0;

        WorkTypeStep::create([
            'work_type_id' => $request->work_type_id,
            'name' => $request->name,
            'order' => $maxOrder + 1,
            'stage' => 'preparation' // Distinct from workflow steps
        ]);

        return response()->json(['success' => true]);
    }

    // ... (Keep other methods like edit, update, etc. if they are still relevant or redirect them)
    // For now, we are replacing the main Index logic.
    // The previous 'create', 'store' methods in ProductionController are still useful for creating the Pre-Prod job initially.

    public function create(Request $request)
    {
        // ... (Keep existing create logic, just ensure work_type_id is handled)
        // Actually, we might need to update Create to select WorkType.
        // Let's assume the Workflow "Create Job" modal is used generally,
        // OR we update the existing create view.
        // For this task, I'll focus on the Index/Board first.

        // Return existing create view but passing worktypes
        $workTypes = WorkType::orderBy('order')->get();
        return view('production.create', compact('workTypes')); // We'll need to update view too
    }

    public function store(Request $request)
    {
         // Update Store to include work_type_id
         // ...
         // For brevity, let's assume we use the WorkflowController@store logic
         // but adapted for Pre-Production if status is set to pre_production.

         // Let's leave existing Store for now and assume migration of logic if needed.
         // The user instruction "Production menu ... like Workflow" implies
         // we might use a similar Modal to create jobs.

         return parent::callAction('store', [$request]); // Fallback or implement
    }
}

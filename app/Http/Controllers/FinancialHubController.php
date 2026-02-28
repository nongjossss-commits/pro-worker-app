<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\ProductionOrder;
use App\Models\ProductionFinancialGroup;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialHubController extends Controller
{
    /**
     * Display the financial dashboard and transaction list.
     */
    public function index(Request $request)
    {
        // 1. Calculate Stats (Efficiently)
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $stats = [
            'income_today' => FinancialTransaction::whereDate('paid_at', $today)->sum('paid_amount'),
            'income_month' => FinancialTransaction::whereDate('paid_at', '>=', $startOfMonth)->sum('paid_amount'),
            'pending_amount' => FinancialTransaction::whereIn('status', ['pending', 'partial'])->sum(DB::raw('amount - paid_amount')),
            'overdue_amount' => FinancialTransaction::where('status', 'overdue')->sum(DB::raw('amount - paid_amount')),
        ];

        // 2. Query Transactions
        $query = FinancialTransaction::with(['productionOrder.employer', 'financialGroup'])
            ->latest('created_at');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Search by ID or Description/Notes
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('productionOrder', function($po) use ($search) {
                      $po->where('project_name', 'like', "%{$search}%")
                         ->orWhereHas('employer', function($e) use ($search) {
                             $e->where('employerNameTh', 'like', "%{$search}%")
                               ->orWhere('employerNameEn', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20)->withQueryString();

        return view('financial.index', compact('stats', 'transactions'));
    }

    /**
     * Show the form for creating a manual bill.
     */
    public function createManual(Request $request)
    {
        // For the employer dropdown. In a real scenario with thousands of employers,
        // we should use an AJAX search (like select2).
        // For now, I'll pass basic data or use the existing "searchable-select" component if available.
        // Assuming we can pass a few recent employers or rely on the frontend component to fetch.
        // I'll pass all for now as per ProductionController pattern, but lighter.
        $employers = Employer::select('id', 'employerNameTh', 'employerNameEn')
            ->orderBy('employerNameTh')
            ->limit(500) // Safety limit
            ->get();

        $selectedEmployees = collect();
        $employeeIds = $request->input('employee_ids', old('employee_ids'));
        if ($employeeIds && is_array($employeeIds)) {
            $selectedEmployees = \App\Models\Employee::whereIn('id', $employeeIds)
                ->select('id', 'employeeNameTh', 'employeeNameEn', 'employer_employee_id')
                ->get();
        }

        return view('financial.create_manual', compact('employers', 'selectedEmployees'));
    }

    /**
     * Store a manual bill (ProductionOrder) and redirect to its financial tab.
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'description' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'bill_date' => 'nullable|date',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        DB::beginTransaction();
        try {
            $employer = Employer::find($request->employer_id);
            $date = $request->bill_date ? Carbon::parse($request->bill_date) : now();

            // 1. Create Production Order (Type: Manual/General)
            // We use work_type_id = null to signify a generic/manual order.
            $order = ProductionOrder::create([
                'employer_id' => $employer->id,
                'work_type_id' => null,
                'type' => 'employer', // Standard type
                'project_name' => $request->description ?: ('Manual Bill - ' . $date->format('d/m/Y')),
                'description' => 'Generated via Finance Hub',
                'status' => 'active', // Active so it appears in lists if needed, or 'completed'
                'created_by' => auth()->id(),
                'financial_data' => [], // Empty init
            ]);

            // 2. Create Financial Group
            $group = $order->financialGroups()->create([
                'name' => 'General',
                'financial_data' => [],
                'production_order_id' => $order->id,
            ]);

            // 3. Attach Employees to the Order
            if ($request->has('employee_ids') && is_array($request->employee_ids)) {
                $employeeIds = $request->employee_ids;
                $itemsToInsert = [];
                foreach ($employeeIds as $empId) {
                    $itemsToInsert[] = [
                        'production_order_id' => $order->id,
                        'employee_id' => $empId,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($itemsToInsert)) {
                    \App\Models\ProductionItem::insert($itemsToInsert);

                    // Fetch the newly created items to get their IDs
                    $insertedItems = \App\Models\ProductionItem::where('production_order_id', $order->id)->pluck('id')->toArray();

                    // Update the group and order's financial_data to include these items in the default pricing tier
                    if (!empty($insertedItems)) {
                        $financialData = [
                            'pricing_mode' => 'per_head', // Default to per head if there are employees
                            'pricing_tiers' => [
                                [
                                    'id' => 'tier_' . time(),
                                    'name' => 'Default Tier',
                                    'price' => 0,
                                    'item_ids' => $insertedItems
                                ]
                            ]
                        ];

                        $group->update(['financial_data' => $financialData]);
                        $order->update(['financial_data' => $financialData]);
                    }
                }
            }

            // 4. (Optional) Create Initial Transaction
            if ($request->filled('amount') && $request->amount > 0) {
                FinancialTransaction::create([
                    'production_order_id' => $order->id,
                    'production_financial_group_id' => $group->id,
                    'type' => 'installment', // Default generic type
                    'amount' => $request->amount,
                    'due_date' => $date, // Due immediately or on date
                    'status' => 'pending',
                    'notes' => 'Initial amount',
                    'created_at' => $date,
                ]);
            }

            DB::commit();

            // Redirect to the Financial Tab of this Order
            // The route for editing production is usually production.index with query param or a specific edit route.
            // Wait, standard CRUD is production.edit?
            // Let's check routes again.
            // Route::resource('production', \App\Http\Controllers\ProductionController::class)
            // So 'production.edit' exists.
            // I'll redirect there with ?tab=financial to open the right tab (assuming frontend supports it).

            return redirect()->route('production.edit', ['production' => $order->id, 'tab' => 'financial'])
                             ->with('success', 'Manual bill created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create bill: ' . $e->getMessage())->withInput();
        }
    }
}

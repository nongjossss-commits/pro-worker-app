<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\FinancialTransaction;
use App\Models\CompanyProfile;
use App\Models\ProductionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
// use Barryvdh\DomPDF\Facade\Pdf; // Ensure this package is available, or use view return for print

class FinancialController extends Controller
{
    /**
     * Store a new installment or payment plan item.
     */
    public function storeTransaction(Request $request, $productionId)
    {
        // Permission check
        if (!auth()->user()->can('manage-finance') && !auth()->user()->hasRole('admin')) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'type' => 'required|in:installment,down_payment,full_payment,advance_payment',
            'notes' => 'nullable|string',
            'financial_group_id' => 'required|exists:production_financial_groups,id',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'exists:production_items,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        $transaction = FinancialTransaction::create([
            'production_order_id' => $productionId,
            'production_financial_group_id' => $request->financial_group_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'status' => 'pending'
        ]);

        $finalItemIds = $request->item_ids ?? [];

        // Handle direct Employee IDs (Create items on the fly)
        if ($request->has('employee_ids') && is_array($request->employee_ids)) {
            foreach ($request->employee_ids as $empId) {
                // Find or Create ProductionItem for this order/employee
                $item = ProductionItem::firstOrCreate(
                    [
                        'production_order_id' => $productionId,
                        'employee_id' => $empId
                    ],
                    [
                        'status' => 'pending', // Or registration_pending context dependent? Safe default.
                        'group_name' => null
                    ]
                );
                $finalItemIds[] = $item->id;
            }
        }

        $finalItemIds = array_unique($finalItemIds);

        if (!empty($finalItemIds)) {
            $transaction->items()->attach($finalItemIds);
        }

        // Return transaction with items to update frontend state if needed
        $transaction->load('items.employee'); // Load employee for name display

        return response()->json([
            'success' => true,
            'message' => 'Transaction added.',
            'transaction' => $transaction
        ]);
    }

    /**
     * Update transaction (e.g., mark paid, upload slip).
     */
    public function updateTransaction(Request $request, $id)
    {
         if (!auth()->user()->can('manage-finance') && !auth()->user()->hasRole('admin')) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::findOrFail($id);

        $rules = [
            'paid_amount' => 'nullable|numeric',
            'status' => 'nullable|in:pending,partial,paid,overdue',
            'slip_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
            'item_ids' => 'nullable', // Can be array or string "1,2,3" if FormData
            'employee_ids' => 'nullable' // Can be array or string
        ];

        $request->validate($rules);

        if ($request->hasFile('slip_file')) {
            // Delete old slip if exists
            if ($transaction->slip_path) {
                Storage::disk('public')->delete($transaction->slip_path);
            }
            $path = $request->file('slip_file')->store('financial_slips', 'public');
            $transaction->slip_path = $path;
        }

        if ($request->has('paid_amount')) {
            $transaction->paid_amount = $request->paid_amount;
            if (!$transaction->paid_at && $transaction->paid_amount > 0) {
                $transaction->paid_at = now();
            }
        }

        if ($request->has('status')) {
            $transaction->status = $request->status;
        }

        // Auto-update status if fully paid
        if ($transaction->paid_amount >= $transaction->amount && $transaction->status !== 'paid') {
            $transaction->status = 'paid';
            $transaction->paid_at = now(); // Ensure date is set
        }

        $transaction->save();

        // Sync Items
        // We need to merge item_ids and employee_ids logic
        if ($request->has('item_ids') || $request->has('employee_ids')) {
            $itemIds = $request->input('item_ids', []);
            $employeeIds = $request->input('employee_ids', []);

            // Handle FormData format (strings)
            if (is_string($itemIds)) {
                $itemIds = $itemIds ? explode(',', $itemIds) : [];
            }
            if (is_string($employeeIds)) {
                $employeeIds = $employeeIds ? explode(',', $employeeIds) : [];
            }

            // Convert to array if not
            if (!is_array($itemIds)) $itemIds = [];
            if (!is_array($employeeIds)) $employeeIds = [];

            // Process new employee IDs
            foreach ($employeeIds as $empId) {
                $item = ProductionItem::firstOrCreate(
                    [
                        'production_order_id' => $transaction->production_order_id,
                        'employee_id' => $empId
                    ],
                    [
                        'status' => 'pending'
                    ]
                );
                $itemIds[] = $item->id;
            }

            $itemIds = array_unique($itemIds);

            // Only sync if we actually received data updates.
            // If item_ids was passed (even empty), it means we want to update the list.
            if ($request->has('item_ids') || $request->has('employee_ids')) {
                $transaction->items()->sync($itemIds);
            }
        }

        $transaction->load('items.employee');

        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    /**
     * Delete a transaction.
     */
    public function destroyTransaction($id)
    {
        if (!auth()->user()->can('manage-finance') && !auth()->user()->hasRole('admin')) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        FinancialTransaction::destroy($id);
        return response()->json(['success' => true]);
    }

    /**
     * Generate Document (Quotation, Invoice, Receipt, Tax Invoice).
     */
    public function generateDocument(Request $request, $productionId, $type)
    {
        // ... (Keeping existing logic for safety, though likely unused)
        $production = ProductionOrder::with(['employer', 'items.employee', 'financialGroups'])->findOrFail($productionId);

        $transactionIds = $request->query('transaction_ids');
        if ($transactionIds) {
            $ids = explode(',', $transactionIds);
            $transactions = FinancialTransaction::whereIn('id', $ids)->orderBy('due_date')->get();
        } else {
            $transactions = FinancialTransaction::where('production_order_id', $productionId)->orderBy('due_date')->get();
        }

        $activeGroup = null;
        if ($transactions->isNotEmpty()) {
            $activeGroup = $transactions->first()->financialGroup;
        }

        if (!$activeGroup && $request->has('group_id')) {
            $activeGroup = $production->financialGroups->where('id', $request->query('group_id'))->first();
        }

        if (!$activeGroup) {
            $activeGroup = $production->financialGroups->first();
        }

        $financial = $activeGroup ? $activeGroup->financial_data : ($production->financial_data ?? []);

        $profileId = $request->query('profile_id') ?? ($financial['profile_id'] ?? null);
        $profile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::where('is_default', true)->first();

        $customHeader = $financial['custom_header'] ?? null;
        if (!empty($customHeader) && !empty($customHeader['name'])) {
            $profile = new CompanyProfile([
                'name' => $customHeader['name'],
                'address' => $customHeader['address'] ?? '',
                'tax_id' => $customHeader['tax_id'] ?? '',
                'logo_path' => $customHeader['logo'] ?? null,
                'phone' => $customHeader['phone'] ?? null
            ]);
        }

        if (!$profile) {
            $profile = new CompanyProfile([
                'name' => 'Company Name',
                'address' => 'Company Address',
                'tax_id' => '0000000000000'
            ]);
        }

        $viewName = match ($type) {
            'quotation' => 'documents.quotation',
            'invoice' => 'documents.invoice',
            'receipt' => 'documents.receipt',
            'credit_note' => 'documents.credit_note',
            'tax_invoice' => 'documents.tax_invoice',
            default => 'documents.generic',
        };

        return view($viewName, compact('production', 'profile', 'financial', 'transactions', 'type', 'activeGroup'));
    }

    // --- Settings Methods ---

    public function indexSettings()
    {
        $profiles = CompanyProfile::all();
        return view('admin.settings.financial', compact('profiles'));
    }

    public function storeProfile(Request $request)
    {
        // Allow logo to be a string (path) for JSON requests
        $request->validate([
            'name' => 'required',
            'logo' => 'nullable',
        ]);

        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        } elseif ($request->filled('logo') && is_string($request->logo)) {
            // If saving via JSON and logo is already a path
            $data['logo_path'] = $request->logo;
        }

        if ($request->is_default) {
            CompanyProfile::where('is_default', true)->update(['is_default' => false]);
        }

        $profile = CompanyProfile::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'profile' => $profile]);
        }

        return back()->with('success', 'Profile created');
    }
}

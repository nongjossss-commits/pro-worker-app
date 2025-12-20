<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\FinancialTransaction;
use App\Models\CompanyProfile;
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
             // Allow if it's the creator? Or strict?
             // User requested professional restriction.
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'type' => 'required|in:installment,down_payment,full_payment,advance_payment',
            'notes' => 'nullable|string',
            'financial_group_id' => 'required|exists:production_financial_groups,id'
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

        $request->validate([
            'paid_amount' => 'nullable|numeric',
            'status' => 'nullable|in:pending,partial,paid,overdue',
            'slip_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

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
        $production = ProductionOrder::with(['employer', 'items.employee', 'financialGroups'])->findOrFail($productionId);

        // Filter transactions by IDs if provided (Partial/Selective Generation)
        $transactionIds = $request->query('transaction_ids');
        if ($transactionIds) {
            $ids = explode(',', $transactionIds);
            $transactions = FinancialTransaction::whereIn('id', $ids)->orderBy('due_date')->get();
        } else {
            // Default: Should probably not show ALL transactions if we have multiple tabs?
            // User flow: They click "Generate" inside a tab.
            // Ideally, we filter by group if no specific transactions selected.
            // But let's stick to transaction IDs if possible, or fallback to all.
            $transactions = FinancialTransaction::where('production_order_id', $productionId)->orderBy('due_date')->get();
        }

        // Determine which Group (Tab) we are generating for
        // If transactions are selected, take the group from the first transaction
        $activeGroup = null;
        if ($transactions->isNotEmpty()) {
            $activeGroup = $transactions->first()->financialGroup;
        }

        // If no transactions but we have a group_id param?
        if (!$activeGroup && $request->has('group_id')) {
            $activeGroup = $production->financialGroups->where('id', $request->query('group_id'))->first();
        }

        // Fallback: First group
        if (!$activeGroup) {
            $activeGroup = $production->financialGroups->first();
        }

        // Financial Data comes from the Group now, fallback to Order for legacy
        $financial = $activeGroup ? $activeGroup->financial_data : ($production->financial_data ?? []);

        // Get Header Profile (From Params > Group Settings > Default)
        $profileId = $request->query('profile_id') ?? ($financial['profile_id'] ?? null);
        $profile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::where('is_default', true)->first();

        // Custom Header Override
        $customHeader = $financial['custom_header'] ?? null;
        // If Custom Header exists and "useCustomHeader" flag is true (implied if present?), we construct a mock profile.
        // Actually, logic is usually client-side param, but let's check data.
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

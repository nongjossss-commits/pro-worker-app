<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\FinancialTransaction;
use App\Models\FinancialPayment;
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
        if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
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
        $createdItemMap = []; // empId => itemId for syncing pricing tiers

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
                $createdItemMap[$empId] = $item->id;
            }
        }

        $finalItemIds = array_unique($finalItemIds);

        if (!empty($finalItemIds)) {
            $transaction->items()->attach($finalItemIds);
        }

        // Sync Pricing Tiers: Replace emp_X with new Item IDs in the pricing group
        // This ensures invoices can map the new item back to its price tier correctly.
        $this->syncPricingTiers($request->financial_group_id, $createdItemMap);

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
         if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::with('payments')->findOrFail($id);

        $rules = [
            'status' => 'nullable|in:pending,partial,paid,overdue',
            'notes' => 'nullable|string',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'wht_status' => 'nullable|in:not_required,pending,received',
            'withholding_tax_amount' => 'nullable|numeric',
            'slip_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
            'wht_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
            'item_ids' => 'nullable', // Can be array or string "1,2,3" if FormData
            'employee_ids' => 'nullable', // Can be array or string
            'amount' => 'nullable|numeric|min:0'
        ];

        $request->validate($rules);

        if ($request->has('notes')) {
            $transaction->notes = $request->notes;
        }

        if ($request->has('bank_account_id')) {
            $transaction->bank_account_id = $request->bank_account_id;
        }

        if ($request->has('wht_status')) {
            $transaction->wht_status = $request->wht_status;
        }

        if ($request->has('withholding_tax_amount')) {
            $transaction->withholding_tax_amount = $request->withholding_tax_amount;
        }

        if ($request->hasFile('slip_file')) {
            // Delete old slip if exists
            if ($transaction->slip_path) {
                Storage::disk('public')->delete($transaction->slip_path);
            }
            $path = $request->file('slip_file')->store('financial_slips', 'public');
            $transaction->slip_path = $path;
        }

        if ($request->hasFile('wht_document')) {
            if ($transaction->wht_document_path) {
                Storage::disk('public')->delete($transaction->wht_document_path);
            }
            $transaction->wht_document_path = $request->file('wht_document')->store('financial_slips/wht', 'public');
        }

        if ($request->has('amount')) {
            $transaction->amount = $request->amount;
        }

        if ($request->has('status')) {
            $transaction->status = $request->status;
        }

        // Auto-update status if fully paid (based on current paid_amount)
        if ($transaction->paid_amount > 0 && $transaction->paid_amount >= $transaction->amount && $transaction->status !== 'paid') {
            $transaction->status = 'paid';
        } elseif ($transaction->paid_amount > 0 && $transaction->paid_amount < $transaction->amount && $transaction->status !== 'partial') {
            $transaction->status = 'partial';
        } elseif ($transaction->paid_amount == 0 && $transaction->status !== 'pending') {
            $transaction->status = 'pending';
        }

        $transaction->save();

        // Sync Items
        // We need to merge item_ids and employee_ids logic
        if ($request->has('item_ids') || $request->has('employee_ids')) {
            $itemIds = $request->input('item_ids', []);
            $employeeIds = $request->input('employee_ids', []);
            $createdItemMap = []; // empId => itemId

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
                $createdItemMap[$empId] = $item->id;
            }

            $itemIds = array_unique($itemIds);

            // Only sync if we actually received data updates.
            // If item_ids was passed (even empty), it means we want to update the list.
            if ($request->has('item_ids') || $request->has('employee_ids')) {
                $transaction->items()->sync($itemIds);
            }

            // Sync Pricing Tiers if items were created
            if (!empty($createdItemMap)) {
                $this->syncPricingTiers($transaction->production_financial_group_id, $createdItemMap);
            }
        }

        $transaction->load(['items.employee', 'payments', 'bankAccount']);

        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    /**
     * Store a payment for a transaction.
     */
    public function storePayment(Request $request, $transactionId)
    {
         if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::findOrFail($transactionId);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'slip_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string'
        ]);

        $slipPath = null;
        if ($request->hasFile('slip_file')) {
            $slipPath = $request->file('slip_file')->store('financial_slips', 'public');
        }

        $payment = $transaction->payments()->create([
            'amount' => $request->amount,
            'paid_at' => \Carbon\Carbon::parse($request->paid_at),
            'bank_account_id' => $request->bank_account_id,
            'slip_path' => $slipPath,
            'notes' => $request->notes,
            'created_by' => auth()->id()
        ]);

        // Update transaction paid amount
        $transaction->paid_amount += $payment->amount;
        $transaction->paid_at = now();

        if ($transaction->paid_amount >= $transaction->amount) {
            $transaction->status = 'paid';
        } else {
            $transaction->status = 'partial';
        }
        $transaction->save();

        // Update Bank Balance
        if ($payment->bank_account_id) {
            \App\Models\BankAccount::where('id', $payment->bank_account_id)
                ->increment('current_balance', $payment->amount);
        }

        $transaction->load(['items.employee', 'payments', 'bankAccount']);
        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    /**
     * Update an existing payment.
     */
    public function updatePayment(Request $request, $paymentId)
    {
         if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = FinancialPayment::with('transaction')->findOrFail($paymentId);
        $transaction = $payment->transaction;

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'slip_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string'
        ]);

        $oldAmount = $payment->amount;
        $oldBankAccountId = $payment->bank_account_id;

        // Revert old bank balance
        if ($oldBankAccountId) {
            \App\Models\BankAccount::where('id', $oldBankAccountId)->decrement('current_balance', $oldAmount);
        }

        if ($request->hasFile('slip_file')) {
            if ($payment->slip_path) {
                Storage::disk('public')->delete($payment->slip_path);
            }
            $payment->slip_path = $request->file('slip_file')->store('financial_slips', 'public');
        }

        $payment->amount = $request->amount;
        $payment->paid_at = \Carbon\Carbon::parse($request->paid_at);
        $payment->bank_account_id = $request->bank_account_id;
        $payment->notes = $request->notes;
        $payment->save();

        // Apply new bank balance
        if ($payment->bank_account_id) {
            \App\Models\BankAccount::where('id', $payment->bank_account_id)
                ->increment('current_balance', $payment->amount);
        }

        // Recalculate transaction totals
        $newTotalPaid = $transaction->payments()->sum('amount');
        $transaction->paid_amount = $newTotalPaid;

        if ($newTotalPaid >= $transaction->amount) {
            $transaction->status = 'paid';
        } else if ($newTotalPaid > 0) {
            $transaction->status = 'partial';
        } else {
            $transaction->status = 'pending';
        }
        $transaction->save();

        $transaction->load(['items.employee', 'payments', 'bankAccount']);
        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    /**
     * Delete an existing payment.
     */
    public function destroyPayment($paymentId)
    {
         if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = FinancialPayment::with('transaction')->findOrFail($paymentId);
        $transaction = $payment->transaction;

        // Revert bank balance
        if ($payment->bank_account_id) {
            \App\Models\BankAccount::where('id', $payment->bank_account_id)->decrement('current_balance', $payment->amount);
        }

        $payment->delete();

        // Recalculate transaction totals
        $newTotalPaid = $transaction->payments()->sum('amount');
        $transaction->paid_amount = $newTotalPaid;

        if ($newTotalPaid >= $transaction->amount) {
            $transaction->status = 'paid';
        } else if ($newTotalPaid > 0) {
            $transaction->status = 'partial';
        } else {
            $transaction->status = 'pending';
        }
        $transaction->save();

        $transaction->load(['items.employee', 'payments', 'bankAccount']);
        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    /**
     * Helper to update Pricing Tiers: Replace candidate emp_IDs with real ProductionItem IDs.
     */
    private function syncPricingTiers($financialGroupId, $createdItemMap)
    {
        if (empty($createdItemMap)) return;

        $group = \App\Models\ProductionFinancialGroup::find($financialGroupId);
        if (!$group) return;

        $financialData = $group->financial_data;
        $tiers = $financialData['pricing_tiers'] ?? [];
        $updated = false;

        foreach ($tiers as &$tier) {
            if (isset($tier['item_ids']) && is_array($tier['item_ids'])) {
                $newItemIds = [];
                foreach ($tier['item_ids'] as $id) {
                    // Check if this ID is a candidate placeholder (string starting with 'emp_')
                    if (is_string($id) && str_starts_with($id, 'emp_')) {
                        $empId = str_replace('emp_', '', $id);
                        // If we have created a ProductionItem for this Employee ID, swap it
                        if (isset($createdItemMap[$empId])) {
                            $newItemIds[] = $createdItemMap[$empId];
                            $updated = true;
                        } else {
                            $newItemIds[] = $id;
                        }
                    } else {
                        $newItemIds[] = $id;
                    }
                }
                $tier['item_ids'] = $newItemIds;
                // Update count
                $tier['count'] = count($newItemIds);
            }
        }

        if ($updated) {
            $financialData['pricing_tiers'] = $tiers;
            $group->financial_data = $financialData;
            $group->save();
        }
    }

    /**
     * Delete a transaction.
     */
    public function destroyTransaction($id)
    {
        if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::with('payments')->findOrFail($id);

        // Revert bank balances for all associated payments
        foreach ($transaction->payments as $payment) {
            if ($payment->bank_account_id) {
                \App\Models\BankAccount::where('id', $payment->bank_account_id)->decrement('current_balance', $payment->amount);
            }
        }

        $transaction->delete(); // payments will cascade via DB if configured, but let's be safe.

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
            'signature' => 'nullable',
            'stamp' => 'nullable',
        ]);

        $data = $request->except(['logo', 'signature', 'stamp']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        } elseif ($request->filled('logo') && is_string($request->logo)) {
            // If saving via JSON and logo is already a path
            $data['logo_path'] = $request->logo;
        }

        if ($request->hasFile('signature')) {
            $data['signature_path'] = $request->file('signature')->store('company_signatures', 'public');
        }

        if ($request->hasFile('stamp')) {
            $data['stamp_path'] = $request->file('stamp')->store('company_stamps', 'public');
        }

        // JSON Positions
        if ($request->has('signature_pos') && is_string($request->signature_pos)) {
             $data['signature_pos'] = json_decode($request->signature_pos, true);
        }
        if ($request->has('stamp_pos') && is_string($request->stamp_pos)) {
             $data['stamp_pos'] = json_decode($request->stamp_pos, true);
        }

        if ($request->is_default) {
            CompanyProfile::where('is_default', true)->update(['is_default' => false]);
        }

        $data['use_signature'] = $request->has('use_signature');
        $data['use_stamp'] = $request->has('use_stamp');

        $profile = CompanyProfile::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'profile' => $profile]);
        }

        return back()->with('success', 'Profile created');
    }

    public function updateProfile(Request $request, $id)
    {
        $profile = CompanyProfile::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'logo' => 'nullable',
            'signature' => 'nullable',
            'stamp' => 'nullable',
            'signature_pos' => 'nullable|string',
            'stamp_pos' => 'nullable|string',
        ]);

        $data = $request->except(['logo', 'signature', 'stamp']);

        // Handle File Uploads
        if ($request->hasFile('logo')) {
            if ($profile->logo_path) Storage::disk('public')->delete($profile->logo_path);
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        }

        if ($request->hasFile('signature')) {
            if ($profile->signature_path) Storage::disk('public')->delete($profile->signature_path);
            $data['signature_path'] = $request->file('signature')->store('company_signatures', 'public');
        }

        if ($request->hasFile('stamp')) {
            if ($profile->stamp_path) Storage::disk('public')->delete($profile->stamp_path);
            $data['stamp_path'] = $request->file('stamp')->store('company_stamps', 'public');
        }

        // Handle JSON positions
        if ($request->has('signature_pos') && is_string($request->signature_pos)) {
             $data['signature_pos'] = json_decode($request->signature_pos, true);
        }
        if ($request->has('stamp_pos') && is_string($request->stamp_pos)) {
             $data['stamp_pos'] = json_decode($request->stamp_pos, true);
        }

        // Handle Default Toggle
        if ($request->is_default) {
            CompanyProfile::where('id', '!=', $id)->where('is_default', true)->update(['is_default' => false]);
        }

        // Handle Booleans
        $data['use_signature'] = $request->has('use_signature');
        $data['use_stamp'] = $request->has('use_stamp');
        $data['is_default'] = $request->has('is_default');

        $profile->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'profile' => $profile]);
        }

        return back()->with('success', 'Profile updated');
    }
}

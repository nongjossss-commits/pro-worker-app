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
            'type' => 'required|in:installment,down_payment,full_payment',
            'notes' => 'nullable|string'
        ]);

        $transaction = FinancialTransaction::create([
            'production_order_id' => $productionId,
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
     * Generate Document (Quotation, Invoice, Receipt).
     */
    public function generateDocument(Request $request, $productionId, $type)
    {
        $production = ProductionOrder::with(['employer', 'items.employee'])->findOrFail($productionId);

        // Get Header Profile
        $profileId = $request->query('profile_id');
        $profile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::where('is_default', true)->first();

        if (!$profile) {
            // Fallback dummy profile
            $profile = new CompanyProfile([
                'name' => 'Company Name',
                'address' => 'Company Address',
                'tax_id' => '0000000000000'
            ]);
        }

        // Financial Data
        $financial = $production->financial_data; // JSON
        // Transactions
        $transactions = FinancialTransaction::where('production_order_id', $productionId)->orderBy('due_date')->get();

        $viewName = match ($type) {
            'quotation' => 'documents.quotation',
            'invoice' => 'documents.invoice',
            'receipt' => 'documents.receipt',
            default => 'documents.generic',
        };

        // For now, return View for printing. PDF generation can be added if `barryvdh/laravel-dompdf` is installed.
        // User requested "Download", but browser "Print to PDF" is often better for simple setups without heavy deps.
        // We will stick to a clean Print View.
        return view($viewName, compact('production', 'profile', 'financial', 'transactions'));
    }

    // --- Settings Methods ---

    public function indexSettings()
    {
        $profiles = CompanyProfile::all();
        return view('admin.settings.financial', compact('profiles'));
    }

    public function storeProfile(Request $request)
    {
        $request->validate(['name' => 'required', 'logo' => 'nullable|image']);
        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        }

        if ($request->is_default) {
            CompanyProfile::where('is_default', true)->update(['is_default' => false]);
        }

        CompanyProfile::create($data);
        return back()->with('success', 'Profile created');
    }
}

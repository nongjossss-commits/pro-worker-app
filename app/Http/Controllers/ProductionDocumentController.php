<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\CompanyProfile;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;

class ProductionDocumentController extends Controller
{
    public function show(Request $request, $id, $type)
    {
        $production = ProductionOrder::with(['employer', 'items.employee', 'items'])->findOrFail($id);

        // Basic Validation of type
        if (!in_array($type, ['quotation', 'invoice', 'receipt', 'credit_note'])) {
            abort(404);
        }

        // Get Company Profile
        $profileId = $request->query('profile_id');
        $companyProfile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::first();

        // --- Filter Transactions Logic ---
        $transactionsQuery = FinancialTransaction::where('production_order_id', $production->id);

        // If specific transaction IDs are passed, filter by them
        if ($request->has('transaction_ids')) {
            $ids = explode(',', $request->query('transaction_ids'));
            $transactionsQuery->whereIn('id', $ids);
        }

        // For Receipt, usually only show 'paid' ones? Or user choice?
        // User choice via selection modal overrides this.
        // But if no selection, maybe default behavior?
        if ($type === 'receipt' && !$request->has('transaction_ids')) {
             $transactionsQuery->where('status', 'paid');
        }

        $transactions = $transactionsQuery->orderBy('due_date')->get();


        // Prepare data for the view
        $data = [
            'production' => $production,
            'company' => $companyProfile,
            'type' => $type,
            'date' => now(),
            'transactions' => $transactions, // Pass filtered transactions
        ];

        return view('production.documents.' . $type, $data);
    }
}

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

        // --- Header Logic ---
        // Check if Custom Header is active in production data
        $financialData = $production->financial_data ?? [];
        $customHeader = $financialData['custom_header'] ?? null;

        // If Custom Header exists and is not null, wrap it in an object similar to CompanyProfile
        if ($customHeader) {
            $companyProfile = (object) [
                'name' => $customHeader['name'] ?? 'Custom Company',
                'address' => $customHeader['address'] ?? '',
                'tax_id' => $customHeader['tax_id'] ?? '',
                'phone' => $customHeader['phone'] ?? '',
                'email' => $customHeader['email'] ?? '', // Optional
                'logo' => $customHeader['logo'] ?? null,
            ];
        } else {
            // Fallback to System Profile
            // Prefer the one saved in financial_data['profile_id'] if exists, else request param, else default
            $profileId = $financialData['profile_id'] ?? $request->query('profile_id');
            $companyProfile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::first();

            // Fallback dummy profile if none exists (Prevents view crash)
            if (!$companyProfile) {
                $companyProfile = new CompanyProfile([
                    'name' => 'Company Name (Default)',
                    'address' => 'Please configure a company profile in settings.',
                    'tax_id' => '0000000000000',
                    'phone' => '-',
                    'email' => '-',
                ]);
            }
        }

        // --- Bill To Logic (Customer Override) ---
        // Check if Customer Override is active in production data
        $customCustomer = $financialData['customer_override'] ?? null;
        $billTo = $production->employer; // Default

        if ($customCustomer) {
            // Wrap in object to match Employer structure interface used in view
            // Employer model uses: employerNameTh, employerAddress, employerPhone
            // We map the override data to these keys for compatibility, or view can handle both.
            // Let's create a generic object.
            $billTo = (object) [
                'employerNameTh' => $customCustomer['name'] ?? 'Client Name',
                'employerAddress' => $customCustomer['address'] ?? '',
                'employerPhone' => $customCustomer['phone'] ?? '-',
                'tax_id' => $customCustomer['tax_id'] ?? '-' // Employer model might not have tax_id accessor commonly used in view yet
            ];
        }

        // --- Filter Transactions Logic ---
        $transactionsQuery = FinancialTransaction::where('production_order_id', $production->id);

        // If specific transaction IDs are passed, filter by them
        if ($request->has('transaction_ids')) {
            $ids = explode(',', $request->query('transaction_ids'));
            $transactionsQuery->whereIn('id', $ids);
        }

        // For Receipt, usually only show 'paid' ones? Or user choice?
        if ($type === 'receipt' && !$request->has('transaction_ids')) {
             $transactionsQuery->where('status', 'paid');
        }

        $transactions = $transactionsQuery->orderBy('due_date')->get();

        // Prepare data for the view
        $data = [
            'production' => $production,
            'company' => $companyProfile,
            'billTo' => $billTo, // Pass the resolved Bill To entity
            'type' => $type,
            'date' => now(),
            'transactions' => $transactions,
            'financial' => $financialData // Pass full financial data for easier access in view
        ];

        return view('production.documents.' . $type, $data);
    }
}

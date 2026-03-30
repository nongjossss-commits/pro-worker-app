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
        // Eager load advanceItems for the active group
        $production = ProductionOrder::with(['employer', 'items.employee', 'items'])->findOrFail($id);

        // Basic Validation of type (Added 'tax_invoice' and 'advance_receipt')
        if (!in_array($type, ['quotation', 'invoice', 'receipt', 'credit_note', 'tax_invoice', 'advance_receipt'])) {
            abort(404);
        }

        // Handle Active Group & Advance Items
        $groupId = $request->query('group_id');
        $activeGroup = null;
        if ($groupId) {
            $activeGroup = $production->financialGroups()->with('advanceItems')->find($groupId);
        }
        // Fallback to first group if not specified
        if (!$activeGroup) {
            $activeGroup = $production->financialGroups()->with('advanceItems')->first();
        }

        $financialData = $activeGroup ? $activeGroup->financial_data : ($production->financial_data ?? []);
        $advanceItems = $activeGroup ? $activeGroup->advanceItems : collect();

        // --- Header Logic ---
        $profileId = $financialData['profile_id'] ?? $request->query('profile_id');
        $baseProfile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::where('is_default', true)->first();

        // Fallback Base
        if (!$baseProfile) {
            $baseProfile = CompanyProfile::first() ?? new CompanyProfile([
                'name' => 'Company Name (Default)',
                'address' => 'Please configure a company profile in settings.',
                'tax_id' => '0000000000000',
                'phone' => '-',
                'email' => '-',
            ]);
        }

        $customHeader = $financialData['custom_header'] ?? null;

        // If Custom Header exists, merge it with Base Profile
        if ($customHeader && !empty($customHeader['name'])) {
            $attributes = $baseProfile->toArray();

            // Merge Overrides
            $attributes['name'] = $customHeader['name'];
            $attributes['address'] = $customHeader['address'] ?? ($attributes['address'] ?? '');
            $attributes['tax_id'] = $customHeader['tax_id'] ?? ($attributes['tax_id'] ?? '');
            $attributes['phone'] = $customHeader['phone'] ?? ($attributes['phone'] ?? '');
            $attributes['email'] = $customHeader['email'] ?? ($attributes['email'] ?? '');

            // Only override logo if provided in custom header
            if (!empty($customHeader['logo'])) {
                $attributes['logo_path'] = $customHeader['logo'];
            }

            $companyProfile = new CompanyProfile($attributes);
        } else {
            $companyProfile = $baseProfile;
        }

        // --- Bill To Logic (Customer Override) ---
        $customCustomer = $financialData['customer_override'] ?? null;
        $billTo = $production->employer; // Default

        if ($customCustomer) {
            $billTo = (object) [
                'employerNameTh' => $customCustomer['name'] ?? 'Client Name',
                'employerAddress' => $customCustomer['address'] ?? '',
                'employerPhone' => $customCustomer['phone'] ?? '-',
                'tax_id' => $customCustomer['tax_id'] ?? '-'
            ];
        }

        // --- Transactions Logic ---
        $transactionsQuery = FinancialTransaction::with('items')->where('production_order_id', $production->id);
        if ($groupId) {
             $transactionsQuery->where('production_financial_group_id', $groupId);
        }

        if ($request->has('transaction_ids')) {
            $ids = explode(',', $request->query('transaction_ids'));
            $transactionsQuery->whereIn('id', $ids);
        }

        if ($type === 'receipt' && !$request->has('transaction_ids')) {
             $transactionsQuery->where('status', 'paid');
        }

        $transactions = $transactionsQuery->orderBy('due_date')->get();

        // --- Mode Logic (Combined, Service Only, Advance Only) ---
        $mode = $request->query('mode', 'combined'); // Default to combined for legacy

        // --- Document Title Logic ---
        $titles = [
            'quotation' => 'ใบเสนอราคา / Quotation',
            'invoice' => 'ใบแจ้งหนี้ / Invoice',
            'tax_invoice' => 'ใบกำกับภาษี / Tax Invoice',
            'receipt' => 'ใบเสร็จรับเงิน / Receipt',
            'advance_receipt' => 'ใบเสร็จรับเงินสำรองจ่าย / Advance Receipt',
            'credit_note' => 'ใบลดหนี้ / Credit Note',
        ];

        $title = $titles[$type] ?? ucfirst($type);

        // Construct descriptive filename for browser "Save as PDF"
        // [Document Type]_[Employer Name]_[Production ID]
        $employerName = $production->employer->employerNameTh ?: ($production->employer->employerNameEn ?: 'Unknown_Employer');
        $pageTitle = "{$title}_{$employerName}_PROD-{$production->id}";

        // --- Employee List Data Logic ---
        $includeEmployeeList = $request->query('include_employee_list') == 1;
        $employeeList = [];

        if ($includeEmployeeList) {
            $pricingMode = $financialData['pricing_mode'] ?? 'per_head';
            $pricingTiers = collect($financialData['pricing_tiers'] ?? []);

            // Start with all items
            $items = $production->items;

            // If specific transactions were requested, filter items to only include
            // those attached to the selected transactions.
            if ($request->has('transaction_ids')) {
                // $transactions is already filtered by transaction_ids above
                $transactionItemIds = $transactions->flatMap(function($t) {
                    return $t->items->pluck('id');
                })->unique();

                $items = $items->filter(function($item) use ($transactionItemIds) {
                    return $transactionItemIds->contains($item->id);
                });
            }

            $index = 1;
            foreach ($items as $item) {
                if ($item->employee) {
                    $emp = $item->employee;

                    // Determine Price
                    $price = 0;
                    $isAssignedToTier = false;

                    if ($pricingMode === 'per_head') {
                        // Find the tier this item belongs to
                        $tier = $pricingTiers->first(function ($t) use ($item) {
                            $itemIds = array_map('strval', $t['item_ids'] ?? []);
                            return in_array(strval($item->id), $itemIds, true) ||
                                   in_array('emp_' . $item->id, $itemIds, true) || // Added check for emp_ + item id
                                   ($item->employee_id && in_array('emp_' . $item->employee_id, $itemIds, true)) ||
                                   ($item->employee_id && in_array(strval($item->employee_id), $itemIds, true));
                        });

                        if ($tier) {
                            $price = $tier['price'] ?? 0;
                            $isAssignedToTier = true;
                        } else {
                            // Default tier fallback
                            $defaultTier = $pricingTiers->first(function ($t) {
                                return empty($t['item_ids']) || ($t['name'] ?? '') === 'Default Tier';
                            });
                            if ($defaultTier) {
                                $price = $defaultTier['price'] ?? 0;
                                // We don't mark as explicitly assigned to a tier if they are just catching the default,
                                // but we might want them to show up if the project relies on default tiers.
                                // Typically, employees without explicit tier mapping in 'per_head' mode shouldn't show
                                // up with 0 price unless it's genuinely free.
                            }
                        }
                    }

                    // For documents NOT filtered by transaction_ids, we only want to show employees
                    // who actually have a price set (or are assigned to a tier).
                    // This prevents printing all employees in the project when only a few have prices.
                    if (!$request->has('transaction_ids') && $pricingMode === 'per_head' && $price <= 0) {
                        continue; // Skip employees with 0 price in per-head full document lists
                    }

                    $employeeList[] = [
                        'index' => $index++,
                        'image' => $emp->employeePhoto,
                        'prefix' => $emp->employeeTitleEn ?: $emp->titleTh,
                        'name' => trim($emp->employeeNameEn ?: $emp->employeeNameTh),
                        'nationality' => $emp->employeeNationality,
                        'price' => $price,
                        'employee_id' => 'EMP' . str_pad($emp->id, 5, '0', STR_PAD_LEFT),
                    ];
                }
            }
        }

        // Prepare data for the view
        // Load Selected Profiles if they exist in financial_data
        $billerProfile = null;
        $customerProfile = null;
        if (!empty($financialData['custom_header']['biller_profile_id'])) {
            $billerProfile = \App\Models\FinancialProfile::find($financialData['custom_header']['biller_profile_id']);
        }
        if (!empty($financialData['custom_header']['customer_profile_id'])) {
            $customerProfile = \App\Models\FinancialProfile::find($financialData['custom_header']['customer_profile_id']);
        }

        $data = [
            'production' => $production,
            'includeEmployeeList' => $includeEmployeeList,
            'employeeList' => $employeeList,
            'profile' => $companyProfile, // Fix: layout expects 'profile'
            'company' => $companyProfile, // Keep for backward compat if any
            'billerProfile' => $billerProfile,
            'customerProfile' => $customerProfile,
            'billTo' => $billTo,
            'type' => $type,
            'title' => $title, // Explicitly pass the title
            'page_title' => $pageTitle, // For <title> tag
            'date' => now(),
            'transactions' => $transactions,
            'financial' => $financialData,
            'advanceItems' => $advanceItems,
            'activeGroup' => $activeGroup,
            'mode' => $mode
        ];

        // Determine View
        $view = 'documents.generic'; // Default

        // Check if a specific view exists for this type
        if (view()->exists('documents.' . $type)) {
            $view = 'documents.' . $type;
        } elseif (in_array($type, ['tax_invoice', 'invoice', 'receipt', 'quotation'])) {
             // Fallback to tax_invoice if specific file missing (legacy safety)
             $view = 'documents.tax_invoice';
        }

        return view($view, $data);
    }

    public function showPaymentDocument($id, $paymentId, $type, Request $request)
    {
        $production = ProductionOrder::with(['employer', 'items.employee', 'financialGroups', 'financialAdvances'])->findOrFail($id);

        // Fetch the specific payment
        $payment = \App\Models\FinancialPayment::with('transaction')->findOrFail($paymentId);

        // Mark as generated
        if (!$payment->receipt_generated_at) {
            $payment->update(['receipt_generated_at' => now()]);
        }

        // Ensure the payment belongs to a transaction in this production order
        if ($payment->transaction->production_order_id !== $production->id) {
            abort(403, 'Unauthorized access to this payment document.');
        }

        // Fake a transaction object that holds only the payment amount for the document logic
        // This makes the existing generic document views work without massive rewrites
        $mockTransaction = clone $payment->transaction;
        $mockTransaction->amount = $payment->amount; // Override the transaction amount with the payment amount
        $mockTransaction->id = $payment->transaction->id; // Keep ID
        $mockTransaction->type = $payment->transaction->type;
        $mockTransaction->notes = "Payment: " . ($payment->notes ?? "Partial/Full Payment");

        $transactions = collect([$mockTransaction]);

        // Standard setup from show()
        $financialData = is_string($production->financial_data) ? json_decode($production->financial_data, true) : ($production->financial_data ?? []);

        $groupId = $request->query('group_id', $payment->transaction->production_financial_group_id);
        $activeGroup = null;
        if ($groupId) {
            $activeGroup = $production->financialGroups->where('id', $groupId)->first();
            if ($activeGroup) {
                $groupData = is_string($activeGroup->financial_data) ? json_decode($activeGroup->financial_data, true) : ($activeGroup->financial_data ?? []);
                $financialData = array_merge($financialData, $groupData);
            }
        }

        // Advance Items specific to group
        $advanceItems = collect();

        // --- Header Logic ---
        $profileId = $financialData['profile_id'] ?? $request->query('profile_id');
        $baseProfile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::where('is_default', true)->first();

        // Fallback Base
        if (!$baseProfile) {
            $baseProfile = CompanyProfile::first() ?? new CompanyProfile([
                'name' => 'Company Name (Default)',
                'address' => 'Please configure a company profile in settings.',
                'tax_id' => '0000000000000',
                'phone' => '-',
                'email' => '-',
            ]);
        }

        $customHeader = $financialData['custom_header'] ?? null;

        // If Custom Header exists, merge it with Base Profile
        if ($customHeader && !empty($customHeader['name'])) {
            $attributes = $baseProfile->toArray();

            // Merge Overrides
            $attributes['name'] = $customHeader['name'];
            $attributes['address'] = $customHeader['address'] ?? ($attributes['address'] ?? '');
            $attributes['tax_id'] = $customHeader['tax_id'] ?? ($attributes['tax_id'] ?? '');
            $attributes['phone'] = $customHeader['phone'] ?? ($attributes['phone'] ?? '');
            $attributes['email'] = $customHeader['email'] ?? ($attributes['email'] ?? '');

            // Only override logo if provided in custom header
            if (!empty($customHeader['logo'])) {
                $attributes['logo_path'] = $customHeader['logo'];
            }

            $companyProfile = new CompanyProfile($attributes);
        } else {
            $companyProfile = $baseProfile;
        }

        // --- Bill To Logic (Customer Override) ---
        $customCustomer = $financialData['customer_override'] ?? null;
        $billTo = $production->employer; // Default

        if ($customCustomer) {
            $billTo = (object) [
                'employerNameTh' => $customCustomer['name'] ?? 'Client Name',
                'employerAddress' => $customCustomer['address'] ?? '',
                'employerPhone' => $customCustomer['phone'] ?? '-',
                'tax_id' => $customCustomer['tax_id'] ?? '-'
            ];
        }

        // --- Mode Logic (Combined, Service Only, Advance Only) ---
        $mode = $request->query('mode', 'combined');

        // --- Document Title Logic ---
        $titles = [
            'tax_invoice' => 'ใบกำกับภาษี / Tax Invoice',
            'receipt' => 'ใบเสร็จรับเงิน / Receipt',
        ];

        $title = $titles[$type] ?? ucfirst($type);

        $employerName = $production->employer->employerNameTh ?: ($production->employer->employerNameEn ?: 'Unknown_Employer');
        $pageTitle = "{$title}_{$employerName}_PAY-{$payment->id}";

        // Prepare data for the view
        $billerProfile = null;
        $customerProfile = null;
        if (!empty($financialData['custom_header']['biller_profile_id'])) {
            $billerProfile = \App\Models\FinancialProfile::find($financialData['custom_header']['biller_profile_id']);
        }
        if (!empty($financialData['custom_header']['customer_profile_id'])) {
            $customerProfile = \App\Models\FinancialProfile::find($financialData['custom_header']['customer_profile_id']);
        }

        $data = [
            'production' => $production,
            'includeEmployeeList' => false,
            'employeeList' => [],
            'profile' => $companyProfile,
            'company' => $companyProfile,
            'billerProfile' => $billerProfile,
            'customerProfile' => $customerProfile,
            'billTo' => $billTo,
            'type' => $type,
            'title' => $title,
            'page_title' => $pageTitle,
            'date' => $payment->paid_at ?? now(), // Use payment date instead of now
            'transactions' => $transactions,
            'financial' => $financialData,
            'advanceItems' => collect(), // usually no advances in direct payment doc unless specified
            'activeGroup' => $activeGroup,
            'mode' => $mode
        ];

        $view = 'documents.generic';

        if (view()->exists('documents.' . $type)) {
            $view = 'documents.' . $type;
        } elseif (in_array($type, ['tax_invoice', 'receipt'])) {
             $view = 'documents.tax_invoice';
        }

        return view($view, $data);
    }
}

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

        // Basic Validation of type (Added 'tax_invoice', 'advance_receipt', and 'reminder')
        if (!in_array($type, ['quotation', 'invoice', 'receipt', 'credit_note', 'tax_invoice', 'advance_receipt', 'reminder'])) {
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
        $billTo = $production->employer ?? (object) [
            'employerNameTh' => $production->project_name ?? 'ลูกค้า',
            'employerNameEn' => '', 'employerAddress' => '',
            'employerPhone' => '-', 'tax_id' => '-', 'employerTaxId' => '-',
        ];

        if ($customCustomer) {
            $billTo = (object) [
                'employerNameTh' => $customCustomer['name'] ?? 'Client Name',
                'employerAddress' => $customCustomer['address'] ?? '',
                'employerPhone' => $customCustomer['phone'] ?? '-',
                'tax_id' => $customCustomer['tax_id'] ?? '-'
            ];
        }

        // --- Transactions Logic ---
        // Reminder documents show past payments alongside the outstanding
        // balance, so eager-load the payments relation when we know that's
        // the type being requested — cheaper than N+1 in the view.
        $transactionsWith = $type === 'reminder'
            ? ['items', 'payments' => fn($q) => $q->orderBy('paid_at')]
            : ['items'];
        $transactionsQuery = FinancialTransaction::with($transactionsWith)->where('production_order_id', $production->id);
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
        $employerName = $production->employer
            ? ($production->employer->employerNameTh ?: ($production->employer->employerNameEn ?: 'Unknown_Employer'))
            : 'Sales_Quotation';
        $pageTitle = "{$title}_{$employerName}_PROD-{$production->id}";

        // --- Employee List Data Logic ---
        // list_only = ออกเฉพาะตารางรายชื่อ ไม่มี header invoice (force include list)
        $listOnly = $request->query('list_only') == 1;
        $includeEmployeeList = $listOnly || $request->query('include_employee_list') == 1;
        $employeeList = [];

        // list-only mode: ปรับ title + filename ให้สื่อความหมายตรง
        if ($listOnly) {
            $title = 'ตารางรายชื่อพนักงาน / Employee List';
            $pageTitle = "EmployeeList_{$employerName}_PROD-{$production->id}";
        }

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
                $emp = $item->employee;
                // A "draft" person — identity typed in directly for a
                // manual bill's quotation, no real Employee record. Same
                // ProductionItem row, same tier-matching below (keyed off
                // $item->id, not employee_id), just a different data source.
                $draft = $emp ? null : $item->new_employee_data;

                if ($emp || $draft) {
                    // Determine Price
                    $price = 0;
                    $isAssignedToTier = false;
                    $tierNote = null;

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
                            $tierNote = $tier['note'] ?? null;
                        } else {
                            // Default tier fallback
                            $defaultTier = $pricingTiers->first(function ($t) {
                                return empty($t['item_ids']) || ($t['name'] ?? '') === 'Default Tier';
                            });
                            if ($defaultTier) {
                                $price = $defaultTier['price'] ?? 0;
                                $tierNote = $defaultTier['note'] ?? null;
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

                    if ($emp) {
                        $employeeList[] = [
                            'index' => $index++,
                            'image' => $emp->employeePhoto,
                            'prefix' => $emp->employeeTitleEn ?: $emp->titleTh,
                            'name' => trim($emp->employeeNameEn ?: $emp->employeeNameTh),
                            'nationality' => $emp->employeeNationality,
                            'price' => $price,
                            'employee_id' => 'EMP' . str_pad($emp->id, 5, '0', STR_PAD_LEFT),
                            'note' => $tierNote,
                            'passport' => $emp->employeePassport,
                            'id_number' => $emp->employee_id_number,
                            'work_permit' => $emp->employeeWorkPermit,
                        ];
                    } else {
                        $employeeList[] = [
                            'index' => $index++,
                            'image' => $draft['photo'] ?? null,
                            'prefix' => $draft['title_en'] ?? '',
                            'name' => trim(($draft['name_en'] ?? '') ?: ($draft['name_th'] ?? '')),
                            'nationality' => $draft['nationality'] ?? '',
                            'price' => $price,
                            'employee_id' => '-', // no real Employee record
                            'note' => $tierNote,
                            'passport' => $draft['passport'] ?? '',
                            'id_number' => $draft['id_number'] ?? '',
                            'work_permit' => $draft['work_permit'] ?? '',
                        ];
                    }
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

        // Payment Methods — passed from the Create Invoice modal as a
        // base64-encoded JSON array. Quietly drop malformed payloads;
        // the view treats an empty array as "no payment block".
        $paymentMethods = [];
        if ($request->filled('payment_methods')) {
            $raw = base64_decode($request->query('payment_methods'), true);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $paymentMethods = $decoded;
                }
            }
        }

        // A quotation can choose to hide the grand total (per-unit rate quote,
        // no committed headcount) — defaults to showing it, same as before,
        // for every other document type and whenever the caller doesn't pass
        // the flag at all (e.g. the Sales-module quotation flow).
        $showTotal = $type === 'quotation' ? $request->boolean('show_total', true) : true;

        $data = [
            'production' => $production,
            'showTotal' => $showTotal,
            'includeEmployeeList' => $includeEmployeeList,
            'listOnly' => $listOnly,
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
            'mode' => $mode,
            'paymentMethods' => $paymentMethods,
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
        $production = ProductionOrder::with(['employer', 'items.employee', 'financialGroups'])->findOrFail($id);

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
        $mockTransaction->paid_amount = $payment->amount; // Override paid_amount to match this specific payment
        $mockTransaction->discount_amount = 0; // Discount already factored into transaction, don't double-subtract
        $mockTransaction->id = $payment->transaction->id; // Keep ID
        $mockTransaction->type = $payment->transaction->type;
        $mockTransaction->notes = "Payment: " . ($payment->notes ?? "Partial/Full Payment");

        // Clear items relationship so the layout uses fixed-amount display
        // instead of per_head breakdown (which would show original tier prices, not payment amount)
        $mockTransaction->setRelation('items', collect());

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
        $billTo = $production->employer ?? (object) [
            'employerNameTh' => $production->project_name ?? 'ลูกค้า',
            'employerNameEn' => '', 'employerAddress' => '',
            'employerPhone' => '-', 'tax_id' => '-', 'employerTaxId' => '-',
        ];

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

        $employerName = $production->employer
            ? ($production->employer->employerNameTh ?: ($production->employer->employerNameEn ?: 'Unknown_Employer'))
            : 'Sales_Quotation';
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
            // This path never generates a 'quotation' (payments only apply to
            // invoices/receipts/tax invoices) — always show the total, same as before.
            'showTotal' => true,
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

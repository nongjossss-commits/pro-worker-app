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

        // Prepare data for the view
        $data = [
            'production' => $production,
            'profile' => $companyProfile, // Fix: layout expects 'profile'
            'company' => $companyProfile, // Keep for backward compat if any
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
}

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
        $customHeader = $financialData['custom_header'] ?? null;

        // If Custom Header exists and is not null, wrap it in an object similar to CompanyProfile
        if ($customHeader) {
            $companyProfile = (object) [
                'name' => $customHeader['name'] ?? 'Custom Company',
                'address' => $customHeader['address'] ?? '',
                'tax_id' => $customHeader['tax_id'] ?? '',
                'phone' => $customHeader['phone'] ?? '',
                'email' => $customHeader['email'] ?? '',
                'logo_path' => $customHeader['logo'] ?? null,
            ];
        } else {
            // Fallback to System Profile
            $profileId = $financialData['profile_id'] ?? $request->query('profile_id');
            $companyProfile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::where('is_default', true)->first();

            if (!$companyProfile) {
                // Last resort: First available profile or dummy
                $companyProfile = CompanyProfile::first() ?? new CompanyProfile([
                    'name' => 'Company Name (Default)',
                    'address' => 'Please configure a company profile in settings.',
                    'tax_id' => '0000000000000',
                    'phone' => '-',
                    'email' => '-',
                ]);
            }
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
        $transactionsQuery = FinancialTransaction::where('production_order_id', $production->id);
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

        // Prepare data for the view
        $data = [
            'production' => $production,
            'profile' => $companyProfile, // Fix: layout expects 'profile'
            'company' => $companyProfile, // Keep for backward compat if any
            'billTo' => $billTo,
            'type' => $type,
            'title' => $title, // Explicitly pass the title
            'date' => now(),
            'transactions' => $transactions,
            'financial' => $financialData,
            'advanceItems' => $advanceItems,
            'activeGroup' => $activeGroup,
            'mode' => $mode
        ];

        // Determine View
        $view = 'documents.generic'; // Default
        if (in_array($type, ['tax_invoice', 'invoice', 'receipt', 'quotation'])) {
            // Re-use tax_invoice template for most structured docs, or specific ones if they exist
            // I'll update tax_invoice to be the master template
            $view = 'documents.tax_invoice';
        } elseif ($type === 'advance_receipt') {
            $view = 'documents.advance_receipt';
        }

        return view($view, $data);
    }
}

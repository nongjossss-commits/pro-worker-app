<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::with('financialProfile')->orderBy('account_type')->orderBy('bank_name')->paginate(15);
        $profiles = \App\Models\FinancialProfile::orderBy('name')->get();
        return view('financial.bank_accounts.index', compact('accounts', 'profiles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'account_type' => 'required|in:personal,company',
            'financial_profile_id' => 'nullable|exists:financial_profiles,id',
            'tax_id' => 'nullable|string|max:15',
            'initial_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['current_balance'] = $validated['initial_balance'];
        $validated['is_active'] = true;

        BankAccount::create($validated);

        return back()->with('success', 'Bank Account added successfully.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'account_type' => 'required|in:personal,company',
            'financial_profile_id' => 'nullable|exists:financial_profiles,id',
            'tax_id' => 'nullable|string|max:15',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->has('is_active');

        $bankAccount->update($validated);

        return back()->with('success', 'Bank Account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        // Ideally check if it has transactions before deleting
        // For now, simple delete
        $bankAccount->delete();
        return back()->with('success', 'Bank Account deleted successfully.');
    }
}

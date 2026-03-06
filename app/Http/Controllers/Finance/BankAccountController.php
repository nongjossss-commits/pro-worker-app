<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::orderBy('bank_name')->paginate(15);
        return view('financial.bank_accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'initial_balance' => 'required|numeric|min:0',
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
            'is_active' => 'boolean',
        ]);

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

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with(['category', 'bankAccount', 'financialProfile'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('financial.expenses.index', compact('expenses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'financial_profile_id' => 'nullable|exists:financial_profiles,id',
            'production_order_id' => 'nullable|exists:production_orders,id',
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240', // 10MB max
            'wht_document' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $category = ExpenseCategory::find($validated['expense_category_id']);
            $is_tax_deductible = $request->has('is_tax_deductible') ? true : $category->is_tax_deductible;

            $expense = new Expense([
                'expense_date' => $validated['expense_date'],
                'expense_category_id' => $validated['expense_category_id'],
                'bank_account_id' => $validated['bank_account_id'],
                'financial_profile_id' => $validated['financial_profile_id'] ?? null,
                'production_order_id' => $validated['production_order_id'] ?? null,
                'amount' => $validated['amount'],
                'vat_amount' => $validated['vat_amount'] ?? 0,
                'is_tax_deductible' => $is_tax_deductible,
                'description' => $validated['description'],
                'created_by' => auth()->id(),
            ]);

            // Handle file uploads
            if ($request->hasFile('receipt')) {
                $expense->receipt_path = $request->file('receipt')->store('expenses/receipts', 'public');
            }
            if ($request->hasFile('wht_document')) {
                $expense->wht_document_path = $request->file('wht_document')->store('expenses/wht_docs', 'public');
            }

            $expense->save();

            // Deduct from bank account balance
            $bankAccount = BankAccount::find($validated['bank_account_id']);
            $totalDeduct = $validated['amount'] + ($validated['vat_amount'] ?? 0);
            $bankAccount->decrement('current_balance', $totalDeduct);

            DB::commit();

            return back()->with('success', 'Expense added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error saving expense: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Expense $expense)
    {
        try {
            DB::beginTransaction();

            // Restore bank balance
            if ($expense->bank_account_id) {
                $bankAccount = BankAccount::find($expense->bank_account_id);
                if ($bankAccount) {
                    $totalRestore = $expense->amount + $expense->vat_amount;
                    $bankAccount->increment('current_balance', $totalRestore);
                }
            }

            // Delete files
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            if ($expense->wht_document_path) {
                Storage::disk('public')->delete($expense->wht_document_path);
            }

            $expense->delete();

            DB::commit();

            return back()->with('success', 'Expense deleted and balance restored successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting expense: ' . $e->getMessage());
        }
    }
}

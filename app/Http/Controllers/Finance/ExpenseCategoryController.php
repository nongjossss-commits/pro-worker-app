<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::orderBy('name')->paginate(15);
        return view('financial.expense_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_tax_deductible' => 'boolean',
        ]);

        $validated['is_tax_deductible'] = $request->has('is_tax_deductible');
        $validated['is_active'] = true;

        ExpenseCategory::create($validated);

        return back()->with('success', 'Expense Category added successfully.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_tax_deductible' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['is_tax_deductible'] = $request->has('is_tax_deductible');
        $validated['is_active'] = $request->has('is_active');

        $expenseCategory->update($validated);

        return back()->with('success', 'Expense Category updated successfully.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();
        return back()->with('success', 'Expense Category deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborExpenseCategory;
use Illuminate\Http\Request;

/**
 * Catalog CRUD for Company Books expense categories — Super Admin only,
 * mirrors LaborChargeTypeController exactly. Accounting Staff only ever
 * pick from this list when recording an expense in Company Books.
 */
class LaborExpenseCategoryController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_tax_deductible' => ['nullable', 'boolean'],
        ]);
        $validated['is_tax_deductible'] = $request->boolean('is_tax_deductible');

        LaborExpenseCategory::create($validated);

        return back()->with('success', 'เพิ่มหมวดหมู่รายจ่ายเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborExpenseCategory $expenseCategory)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_tax_deductible' => ['nullable', 'boolean'],
        ]);
        $validated['is_tax_deductible'] = $request->boolean('is_tax_deductible');
        $validated['is_active'] = $request->boolean('is_active');

        $expenseCategory->update($validated);

        return back()->with('success', 'อัปเดตหมวดหมู่รายจ่ายเรียบร้อยแล้ว');
    }
}

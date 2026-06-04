<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;

class IncomeCategoryController extends Controller
{
    public function index()
    {
        $categories = IncomeCategory::orderBy('name')->paginate(20);
        return view('financial.income_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        $validated['is_active'] = true;

        IncomeCategory::create($validated);

        return back()->with('success', __('Income category created.'));
    }

    public function update(Request $request, IncomeCategory $incomeCategory)
    {
        $validated = $this->validatePayload($request, $incomeCategory->id);
        $validated['is_active'] = $request->has('is_active');

        $incomeCategory->update($validated);

        return back()->with('success', __('Income category updated.'));
    }

    public function destroy(IncomeCategory $incomeCategory)
    {
        $incomeCategory->delete();
        return back()->with('success', __('Income category deleted.'));
    }

    protected function validatePayload(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'code' => 'nullable|string|max:20|unique:income_categories,code' . ($ignoreId ? ",{$ignoreId}" : ''),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_vat_treatment' => 'required|in:none,taxable,exempt,zero_rate',
            'default_wht_type' => 'required|in:none,pnd3,pnd53',
            'default_wht_rate' => 'nullable|numeric|min:0|max:100',
        ]);
    }
}

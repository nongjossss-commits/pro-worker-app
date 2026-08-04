<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborChargeType;
use Illuminate\Http\Request;

/**
 * Catalog CRUD for billable service types — Super Admin only. Accounting
 * Staff only ever pick from this list (see LaborChargeEntryController);
 * they never set or see rates change here.
 */
class LaborChargeTypeController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]);

        LaborChargeType::create($validated);

        return back()->with('success', 'เพิ่มประเภทรายการเรียกเก็บเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborChargeType $chargeType)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $chargeType->update($validated);

        return back()->with('success', 'อัปเดตประเภทรายการเรียบร้อยแล้ว');
    }
}

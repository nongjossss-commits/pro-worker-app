<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\FinancialProfile;
use App\Models\LaborBill;
use App\Models\LaborBillingSetting;
use App\Models\LaborTeam;
use App\Services\LaborBillService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Central Billing statements — periodic snapshots sent to each team so they
 * can review their outstanding balance. Same manage-labor-ledger gate as the
 * rest of Central Billing; the issuer profile picker is intentionally open
 * to both Accounting Staff and Super Admin (not locked to Super Admin like
 * the charge-type catalog).
 */
class LaborBillController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $bills = LaborBill::with(['team', 'financialProfile', 'creator'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(30);

        $teams = LaborTeam::where('is_active', true)->orderBy('name')->get();
        $profiles = FinancialProfile::where('type', 'biller')->orderBy('name')->get();
        $currentProfileId = LaborBillingSetting::current()->financial_profile_id;

        return view('labor.bills.index', compact('bills', 'teams', 'profiles', 'currentProfileId'));
    }

    public function store(Request $request, LaborBillService $service)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'labor_team_id' => ['required', 'exists:labor_teams,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'financial_profile_id' => ['nullable', 'exists:financial_profiles,id'],
        ]);

        $team = LaborTeam::findOrFail($validated['labor_team_id']);

        $service->generate(
            $team,
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
            $validated['financial_profile_id'] ?? null,
        );

        return back()->with('success', 'วางบิลเรียบร้อยแล้ว');
    }

    public function download(Request $request, LaborBill $bill)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        if (!$bill->pdf_path || !Storage::disk('public')->exists($bill->pdf_path)) {
            abort(404, 'ไม่พบไฟล์ PDF ของบิลนี้');
        }

        return Storage::disk('public')->download($bill->pdf_path, $bill->bill_no . '.pdf');
    }

    public function void(Request $request, LaborBill $bill, LaborBillService $service)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ]);

        $service->void($bill, $validated['void_reason']);

        return back()->with('success', 'ยกเลิกบิลเรียบร้อยแล้ว');
    }

    public function updateSettings(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'financial_profile_id' => ['nullable', 'exists:financial_profiles,id'],
        ]);

        $setting = LaborBillingSetting::current();
        $setting->update($validated);

        return back()->with('success', 'อัปเดตหัวบิลเรียบร้อยแล้ว');
    }
}

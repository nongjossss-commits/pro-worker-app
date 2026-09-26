<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborCustomer;
use App\Models\LaborTeam;
use Illuminate\Http\Request;

/**
 * A team's own external customer roster — recorded once by ProWalker's own
 * accounting (see LaborCustomer's docblock for why) and reused every time
 * that team needs an invoice placed with that customer, instead of retyping
 * their tax ID/address each time. Mirrors LaborTeamMemberController's shape
 * (team pairing fixed at creation, same permission gate).
 */
class LaborCustomerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $query = LaborCustomer::with('team')->withCount('taxInvoices')->orderBy('name');

        if ($request->filled('team_id')) {
            $query->where('labor_team_id', $request->team_id);
        }

        $customers = $query->paginate(30)->withQueryString();
        $teams = LaborTeam::where('is_active', true)->orderBy('name')->get();

        return view('labor.customers.index', compact('customers', 'teams'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'labor_team_id' => ['required', 'exists:labor_teams,id'],
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:15'],
            'branch' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        LaborCustomer::create($validated);

        return back()->with('success', 'เพิ่มลูกค้าเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborCustomer $customer)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        // Team pairing is fixed at creation (same rule as LaborTeamMember) —
        // an invoice already snapshots this customer's team, so moving a
        // customer to a different team afterward would make historical
        // invoices misleading about which team they belonged to at the time.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:15'],
            'branch' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $customer->update($validated);

        return back()->with('success', 'แก้ไขข้อมูลลูกค้าเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, LaborCustomer $customer)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        // Soft delete only — existing invoices keep their labor_customer_id
        // reference (see the migration's nullOnDelete for the hard-delete case).
        $customer->delete();

        return back()->with('success', 'ลบลูกค้าเรียบร้อยแล้ว');
    }
}

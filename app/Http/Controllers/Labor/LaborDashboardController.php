<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborTeam;
use Illuminate\Http\Request;

class LaborDashboardController extends Controller
{
    /**
     * `labor-team` users land straight on their own team's ledger.
     * Everyone else (super-admin, granted admin, accounting, shareholder)
     * sees the company-wide overview of every team.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('labor-team')) {
            if (!$user->labor_team_id) {
                abort(403, 'บัญชีนี้ยังไม่ได้ผูกกับทีมงานใด กรุณาติดต่อ Super Admin');
            }

            return redirect()->route('labor.teams.show', $user->labor_team_id);
        }

        $teams = LaborTeam::withSum('ledgerEntries as total_owed', 'amount')
            ->orderBy('name')
            ->get();

        $grandTotal = $teams->sum('total_owed');
        $canManage = $user->can('manage-labor-ledger');

        return view('labor.dashboard', compact('teams', 'grandTotal', 'canManage'));
    }
}

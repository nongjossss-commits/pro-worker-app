<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborAuditLog;
use Illuminate\Http\Request;

class LaborAuditLogController extends Controller
{
    /**
     * Super Admin only — the oversight trail for every change any Accounting
     * Staff (or admin, once granted) makes to any team's ledger.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        $logs = LaborAuditLog::with(['user', 'team'])
            ->latest()
            ->paginate(50);

        return view('labor.audit-log.index', compact('logs'));
    }
}

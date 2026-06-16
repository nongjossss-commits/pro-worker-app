<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LedgerEntry;
use App\Models\TaxInvoice;
use App\Models\User;
use App\Models\WhtCertificate;
use Illuminate\Http\Request;

/**
 * Audit log filtered to Ledger v2 entities only.
 *
 * Reuses the existing ActivityLog table — services call ActivityLogHelper
 * on mutations, this controller just scopes the listing to the financial
 * subject types so non-finance noise (employees, tickets, ...) doesn't
 * bury the rows the finance team actually cares about.
 */
class FinanceAuditController extends Controller
{
    public const SUBJECT_TYPES = [
        LedgerEntry::class,
        TaxInvoice::class,
        WhtCertificate::class,
    ];

    public function index(Request $request)
    {
        $query = ActivityLog::with('user')
            ->whereIn('subject_type', self::SUBJECT_TYPES)
            ->latest();

        if ($request->filled('entity')) {
            $entity = $request->entity;
            $class = match ($entity) {
                'ledger' => LedgerEntry::class,
                'invoice' => TaxInvoice::class,
                'wht' => WhtCertificate::class,
                default => null,
            };
            if ($class) {
                $query->where('subject_type', $class);
            }
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('description', 'like', "%{$s}%");
        }

        $logs = $query->paginate(50)->withQueryString();

        $users = User::orderBy('name')->get();
        $actions = ActivityLog::whereIn('subject_type', self::SUBJECT_TYPES)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return view('financial.audit.index', compact('logs', 'users', 'actions'));
    }
}

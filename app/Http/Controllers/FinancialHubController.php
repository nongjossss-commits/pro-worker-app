<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\FinancialTransaction;
use App\Models\ProductionOrder;
use App\Models\ProductionFinancialGroup;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialHubController extends Controller
{
    /**
     * Display the financial dashboard and transaction list.
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'overview');

        // Prepare variables that might be used across views
        $stats = [
            'income_today' => 0,
            'income_month' => 0,
            'pending_amount' => 0,
            'overdue_amount' => 0,
        ];
        $transactions = null;
        $orders = null;
        $filteredStats = null;

        if ($tab === 'overview') {
            // 1. Calculate Stats (Efficiently)
            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth();

            $stats = [
                'income_today' => FinancialTransaction::whereDate('paid_at', $today)->sum('paid_amount'),
                'income_month' => FinancialTransaction::whereDate('paid_at', '>=', $startOfMonth)->sum('paid_amount'),
                'pending_amount' => FinancialTransaction::whereIn('status', ['pending', 'partial'])->sum(DB::raw('amount - paid_amount')),
                'overdue_amount' => FinancialTransaction::where('status', 'overdue')->sum(DB::raw('amount - paid_amount')),
            ];

            // 2. Query Transactions
            $query = FinancialTransaction::query()->latest('created_at');

            // Search — id, notes, project, employer (Th/En/suffix), job owner
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhereHas('productionOrder', function($po) use ($search) {
                          $po->where('project_name', 'like', "%{$search}%")
                             ->orWhereHas('employer', function($e) use ($search) {
                                 $e->where('employerNameTh', 'like', "%{$search}%")
                                   ->orWhere('employerNameEn', 'like', "%{$search}%")
                                   ->orWhere('name_suffix', 'like', "%{$search}%")
                                   ->orWhereHas('jobOwner', function($jo) use ($search) {
                                       $jo->where('name', 'like', "%{$search}%");
                                   });
                             });
                      });
                });
            }

            // Filters
            if ($request->filled('status')) {
                // "outstanding" is a virtual status — the operator wants to see
                // every bill the customer still owes money on (unpaid + partial +
                // overdue) so they can total up the receivable in one view.
                if ($request->status === 'outstanding') {
                    $query->whereIn('status', ['pending', 'partial', 'overdue']);
                } else {
                    $query->where('status', $request->status);
                }
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filtered Summary — aggregate ของผลลัพธ์ทั้งหมด (ก่อน paginate) แสดงเฉพาะตอนมี filter
            $filteredStats = null;
            $hasFilter = $request->filled('search')
                || $request->filled('status')
                || $request->filled('date_from')
                || $request->filled('date_to');

            if ($hasFilter) {
                $agg = (clone $query)->reorder()->selectRaw("
                    COUNT(*) as total_count,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(SUM(paid_amount), 0) as total_paid,
                    COALESCE(SUM(amount - paid_amount), 0) as total_outstanding,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN status IN ('pending', 'partial') THEN 1 ELSE 0 END) as unpaid_count,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
                ")->first();

                $filteredStats = [
                    'total_count' => (int) ($agg->total_count ?? 0),
                    'total_amount' => (float) ($agg->total_amount ?? 0),
                    'total_paid' => (float) ($agg->total_paid ?? 0),
                    'total_outstanding' => (float) ($agg->total_outstanding ?? 0),
                    'paid_count' => (int) ($agg->paid_count ?? 0),
                    'unpaid_count' => (int) ($agg->unpaid_count ?? 0),
                    'overdue_count' => (int) ($agg->overdue_count ?? 0),
                ];
            }

            $transactions = $query->with(['productionOrder.employer.jobOwner', 'financialGroup'])
                ->paginate(20)->withQueryString();
        }
        elseif ($tab === 'workflow') {
            $baseQuery = ProductionOrder::whereNotIn('status', ['registration_resolution', 'registration_resolution_cancelled', 'renewal_resolution', 'renewal_resolution_cancelled'])
                ->whereNotNull('work_type_id');

            $stats = $this->getStatsForOrders($baseQuery);

            $query = (clone $baseQuery)->with(['employer', 'financialGroups.transactions', 'financialGroups.transactions.payments'])
                ->latest('created_at')
                ->withCount('items');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('project_name', 'like', "%{$search}%")
                      ->orWhereHas('employer', function($e) use ($search) {
                          $e->where('employerNameTh', 'like', "%{$search}%")
                            ->orWhere('employerNameEn', 'like', "%{$search}%")
                            ->orWhere('name_suffix', 'like', "%{$search}%");
                      });
                });
            }

            // Billing-status filter: "not_billed" = no transactions on any group yet;
            // "billed" = at least one transaction exists. Uses whereHas /
            // whereDoesntHave so pagination totals stay accurate at the DB layer.
            $billingStatus = $request->input('billing_status');
            if ($billingStatus === 'not_billed') {
                $query->whereDoesntHave('financialGroups.transactions');
            } elseif ($billingStatus === 'billed') {
                $query->whereHas('financialGroups.transactions');
            }

            $orders = $query->paginate(20)->withQueryString();
        }
        elseif ($tab === 'registration') {
            $baseQuery = ProductionOrder::where('status', 'registration_resolution');

            $stats = $this->getStatsForOrders($baseQuery);

            $query = (clone $baseQuery)->with(['employer'])
                ->latest('created_at');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('employer', function($e) use ($search) {
                    $e->where('employerNameTh', 'like', "%{$search}%")
                      ->orWhere('employerNameEn', 'like', "%{$search}%")
                      ->orWhere('name_suffix', 'like', "%{$search}%");
                });
            }

            // Batch fetch employee counts to avoid N+1
            $employerIds = (clone $baseQuery)->pluck('employer_id')->unique();
            $employeeCounts = \App\Models\Employee::whereIn('employer_id', $employerIds)
                ->whereIn('status', ['registration_pending', 'registration_completed'])
                ->select('employer_id', DB::raw('count(*) as count'))
                ->groupBy('employer_id')
                ->pluck('count', 'employer_id');

            // Optimize loading for stats
            $ordersForStats = (clone $baseQuery)->select('id', 'employer_id', 'status')
                ->with(['financialGroups' => function($q) {
                    $q->select('id', 'production_order_id', 'financial_data');
                }])
                ->get();

            $totalUnpriced = 0;
            foreach ($ordersForStats as $order) {
                $this->calculateOrderFinancials($order, $employeeCounts[$order->employer_id] ?? 0);
                $totalUnpriced += $order->unpriced_employees_count;
            }
            $stats['total_unpriced'] = $totalUnpriced;

            // Apply unpriced + billing-status filters. Both need the full
            // matching set with computed financials because "partial vs full
            // billed" depends on billed_amount / total_amount ratio that only
            // exists after calculateOrderFinancials() runs. Filtering before
            // pagination keeps the page totals honest.
            $billingStatus = $request->input('billing_status');
            $needsCollectionFilter = ($request->input('unpriced') == 1) || $billingStatus;

            if ($needsCollectionFilter) {
                $allMatching = $query->with([
                        'financialGroups' => function($q) {
                            $q->select('id', 'production_order_id', 'financial_data');
                        },
                        'financialGroups.transactions',
                        'financialGroups.transactions.payments',
                    ])->get();

                foreach($allMatching as $order) {
                    $this->calculateOrderFinancials($order, $employeeCounts[$order->employer_id] ?? 0);
                }

                $filtered = $allMatching;
                if ($request->input('unpriced') == 1) {
                    $filtered = $filtered->filter(fn($o) => $o->unpriced_employees_count > 0);
                }
                if ($billingStatus === 'not_billed') {
                    $filtered = $filtered->filter(fn($o) => (float)($o->billed_amount ?? 0) <= 0.005);
                } elseif ($billingStatus === 'partial_billed') {
                    $filtered = $filtered->filter(fn($o) =>
                        (float)($o->billed_amount ?? 0) > 0.005
                        && (float)($o->billed_amount ?? 0) < (float)($o->total_amount ?? 0) - 0.005
                    );
                } elseif ($billingStatus === 'fully_billed') {
                    $filtered = $filtered->filter(fn($o) =>
                        (float)($o->total_amount ?? 0) > 0.005
                        && (float)($o->billed_amount ?? 0) >= (float)($o->total_amount ?? 0) - 0.005
                    );
                }

                $page = $request->input('page', 1);
                $perPage = 20;
                $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                    $filtered->forPage($page, $perPage),
                    $filtered->count(),
                    $perPage,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                $orders = $query->paginate(20)->withQueryString();
                $orders->load(['financialGroups.transactions', 'financialGroups.transactions.payments', 'financialGroups.advanceItems']);
                foreach ($orders as $order) {
                    $this->calculateOrderFinancials($order, $employeeCounts[$order->employer_id] ?? 0);
                }
            }
        }
        elseif ($tab === 'renewal') {
            $baseQuery = ProductionOrder::where('status', 'renewal_resolution');

            $stats = $this->getStatsForOrders($baseQuery);

            $query = (clone $baseQuery)->with(['employer'])
                ->latest('created_at');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('employer', function($e) use ($search) {
                    $e->where('employerNameTh', 'like', "%{$search}%")
                      ->orWhere('employerNameEn', 'like', "%{$search}%")
                      ->orWhere('name_suffix', 'like', "%{$search}%");
                });
            }

            // Batch fetch employee counts
            $employerIds = (clone $baseQuery)->pluck('employer_id')->unique();
            $employeeCounts = \App\Models\Employee::whereIn('employer_id', $employerIds)
                ->whereIn('status', ['renewal_pending', 'renewal_completed'])
                ->select('employer_id', DB::raw('count(*) as count'))
                ->groupBy('employer_id')
                ->pluck('count', 'employer_id');

            // Optimize loading for stats
            $ordersForStats = (clone $baseQuery)->select('id', 'employer_id', 'status')
                ->with(['financialGroups' => function($q) {
                    $q->select('id', 'production_order_id', 'financial_data');
                }])
                ->get();

            $totalUnpriced = 0;
            foreach ($ordersForStats as $order) {
                $this->calculateOrderFinancials($order, $employeeCounts[$order->employer_id] ?? 0);
                $totalUnpriced += $order->unpriced_employees_count;
            }
            $stats['total_unpriced'] = $totalUnpriced;

            // Apply unpriced + billing-status filters — same collection-level
            // pattern as the registration tab.
            $billingStatus = $request->input('billing_status');
            $needsCollectionFilter = ($request->input('unpriced') == 1) || $billingStatus;

            if ($needsCollectionFilter) {
                $allMatching = $query->with([
                        'financialGroups' => function($q) {
                            $q->select('id', 'production_order_id', 'financial_data');
                        },
                        'financialGroups.transactions',
                        'financialGroups.transactions.payments',
                    ])->get();

                foreach($allMatching as $order) {
                    $this->calculateOrderFinancials($order, $employeeCounts[$order->employer_id] ?? 0);
                }

                $filtered = $allMatching;
                if ($request->input('unpriced') == 1) {
                    $filtered = $filtered->filter(fn($o) => $o->unpriced_employees_count > 0);
                }
                if ($billingStatus === 'not_billed') {
                    $filtered = $filtered->filter(fn($o) => (float)($o->billed_amount ?? 0) <= 0.005);
                } elseif ($billingStatus === 'partial_billed') {
                    $filtered = $filtered->filter(fn($o) =>
                        (float)($o->billed_amount ?? 0) > 0.005
                        && (float)($o->billed_amount ?? 0) < (float)($o->total_amount ?? 0) - 0.005
                    );
                } elseif ($billingStatus === 'fully_billed') {
                    $filtered = $filtered->filter(fn($o) =>
                        (float)($o->total_amount ?? 0) > 0.005
                        && (float)($o->billed_amount ?? 0) >= (float)($o->total_amount ?? 0) - 0.005
                    );
                }

                $page = $request->input('page', 1);
                $perPage = 20;
                $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                    $filtered->forPage($page, $perPage),
                    $filtered->count(),
                    $perPage,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                $orders = $query->paginate(20)->withQueryString();
                $orders->load(['financialGroups.transactions', 'financialGroups.transactions.payments', 'financialGroups.advanceItems']);
                foreach ($orders as $order) {
                    $this->calculateOrderFinancials($order, $employeeCounts[$order->employer_id] ?? 0);
                }
            }
        }
        elseif ($tab === 'manual') {
            $baseQuery = ProductionOrder::whereNull('work_type_id');

            $stats = $this->getStatsForOrders($baseQuery);

            $query = (clone $baseQuery)->with(['employer', 'financialGroups.transactions', 'financialGroups.transactions.payments'])
                ->latest('created_at')
                ->withCount('items');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('project_name', 'like', "%{$search}%")
                      ->orWhereHas('employer', function($e) use ($search) {
                          $e->where('employerNameTh', 'like', "%{$search}%")
                            ->orWhere('employerNameEn', 'like', "%{$search}%")
                            ->orWhere('name_suffix', 'like', "%{$search}%");
                      });
                });
            }
            $orders = $query->paginate(20)->withQueryString();
        }

        $expenses = null;

        if ($tab === 'wht') {
            // WHT Tab: transactions with pending WHT documents
            $query = FinancialTransaction::with(['productionOrder.employer', 'financialGroup'])
                ->where(function($q) {
                    $q->where('wht_status', 'pending')
                      ->orWhere(function($q2) {
                          $q2->whereIn('wht_status', ['pending', 'received'])
                             ->whereNull('wht_document_path');
                      });
                })
                ->latest('created_at');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhereHas('productionOrder', function($po) use ($search) {
                          $po->whereHas('employer', function($e) use ($search) {
                              $e->where('employerNameTh', 'like', "%{$search}%")
                                ->orWhere('employerNameEn', 'like', "%{$search}%")
                                ->orWhere('name_suffix', 'like', "%{$search}%");
                          });
                      });
                });
            }

            $transactions = $query->paginate(20)->withQueryString();

            // WHT stats
            $stats['pending_wht_count'] = FinancialTransaction::where('wht_status', 'pending')->count();
            $stats['pending_wht_amount'] = FinancialTransaction::where('wht_status', 'pending')->sum('withholding_tax_amount');
        }

        if ($tab === 'expenses') {
            $query = \App\Models\Expense::with(['category', 'bankAccount'])
                ->latest('expense_date');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhereHas('category', function($c) use ($search) {
                          $c->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('expense_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('expense_date', '<=', $request->date_to);
            }

            $expenses = $query->paginate(20)->withQueryString();
        }

        return view('financial.index', compact('tab', 'stats', 'transactions', 'orders', 'expenses', 'filteredStats'));
    }

    /**
     * Helper method to calculate stats for a given order query scope.
     */
    protected function getStatsForOrders($orderQuery)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // Use a clone of the query to not affect the original listing query
        $orderIdsSubQuery = (clone $orderQuery)->select('id');

        $stats = FinancialTransaction::whereIn('production_order_id', $orderIdsSubQuery)
            ->select([
                DB::raw("SUM(CASE WHEN DATE(paid_at) = '" . $today->toDateString() . "' THEN paid_amount ELSE 0 END) as income_today"),
                DB::raw("SUM(CASE WHEN paid_at >= '" . $startOfMonth->toDateTimeString() . "' THEN paid_amount ELSE 0 END) as income_month"),
                DB::raw("SUM(CASE WHEN status IN ('pending', 'partial') THEN amount - paid_amount ELSE 0 END) as pending_amount"),
                DB::raw("SUM(CASE WHEN status = 'overdue' THEN amount - paid_amount ELSE 0 END) as overdue_amount"),
            ])
            ->first();

        return [
            'income_today' => $stats->income_today ?? 0,
            'income_month' => $stats->income_month ?? 0,
            'pending_amount' => $stats->pending_amount ?? 0,
            'overdue_amount' => $stats->overdue_amount ?? 0,
        ];
    }

    /**
     * Helper method to calculate financial stats for a given order
     */
    protected function calculateOrderFinancials($order, $preCalculatedCount = null)
    {
        if ($preCalculatedCount !== null) {
            $order->total_employees = $preCalculatedCount;
        } else {
            $order->total_employees = \App\Models\Employee::where('employer_id', $order->employer_id)
                ->when($order->status === 'registration_resolution', function ($q) {
                    $q->whereIn('status', ['registration_pending', 'registration_completed']);
                })
                ->when($order->status === 'renewal_resolution', function ($q) {
                    $q->whereIn('status', ['renewal_pending', 'renewal_completed']);
                })
                ->count();
        }

        $pricedItemIds = [];
        $totalAmount = 0;

        $order->loadMissing(['financialGroups.transactions', 'financialGroups.transactions.payments']);

        foreach ($order->financialGroups as $group) {
            $financialData = $group->financial_data ?? [];
            if (isset($financialData['pricing_tiers']) && is_array($financialData['pricing_tiers'])) {
                foreach ($financialData['pricing_tiers'] as $tier) {
                    if (isset($tier['item_ids']) && is_array($tier['item_ids'])) {
                        $pricedItemIds = array_merge($pricedItemIds, $tier['item_ids']);
                        $totalAmount += (count($tier['item_ids']) * ($tier['price'] ?? 0));
                    }
                }
            }
            if (isset($financialData['advance_payments']) && is_array($financialData['advance_payments'])) {
                foreach ($financialData['advance_payments'] as $adv) {
                    $totalAmount += ($adv['amount'] ?? 0);
                }
            }
            // Add overall advance items
            foreach ($group->advanceItems ?? [] as $advItem) {
                $totalAmount += ($advItem->amount ?? 0);
            }
        }

        // Remove duplicates if any
        $pricedItemIds = array_unique($pricedItemIds);

        $order->priced_employees_count = count($pricedItemIds);
        $order->unpriced_employees_count = max(0, $order->total_employees - $order->priced_employees_count);
        $order->total_amount = $totalAmount;

        $billedAmount = 0;
        $paidAmount = 0;
        foreach ($order->financialGroups as $group) {
            foreach ($group->transactions as $txn) {
                $billedAmount += $txn->amount;
                $paidAmount += $txn->paid_amount;
            }
        }
        $order->billed_amount = $billedAmount;
        $order->paid_amount = $paidAmount;
        $order->pending_amount = max(0, $billedAmount - $paidAmount);

        // Custom total logic to handle cases where there are fixed amounts instead of per head
        // In reality, actual transaction amounts generated rule over expected calculations.
    }

    /**
     * Export the monthly report to a Zip file containing an Excel/CSV and attachments.
     */
    public function exportMonthly(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $billerId = $request->input('biller_id', 'all');
        $exportType = $request->input('export_type', 'all'); // 'all' or 'tax_only'

        ActivityLogHelper::logAction('export', 'ส่งออกรายงานการเงินรายเดือน (' . $month . ', ' . $exportType . ')', null, null, [
            'month' => $month,
            'biller_id' => $billerId,
            'export_type' => $exportType,
        ]);

        $startDate = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Query Incomes (Transactions that are paid)
        $incomesQuery = \App\Models\FinancialTransaction::with(['bankAccount', 'productionOrder'])
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate]);

        if ($billerId !== 'all') {
            $incomesQuery->where('financial_profile_id', $billerId);
        }

        if ($exportType === 'tax_only') {
            // Include only if there's WHT or if you define tax rules for income
            // For now, let's include all paid incomes if tax_only, or filter if requested.
            // A simple assumption: 'tax_only' might primarily affect expenses. We'll include all income.
        }

        $incomes = $incomesQuery->get();

        // Query Expenses
        $expensesQuery = \App\Models\Expense::with(['category', 'bankAccount'])
            ->whereBetween('expense_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($billerId !== 'all') {
            $expensesQuery->where('financial_profile_id', $billerId);
        }

        if ($exportType === 'tax_only') {
            $expensesQuery->where('is_tax_deductible', true);
        }

        $expenses = $expensesQuery->get();

        // Generate CSV data manually since phpspreadsheet may have conflicts
        $csvData = [];
        $csvData[] = ['Type', 'Date', 'Description/Order', 'Category', 'Bank Account', 'WHT Status', 'WHT Amount', 'Amount'];

        foreach ($incomes as $inc) {
            $csvData[] = [
                'Income',
                $inc->paid_at->format('Y-m-d H:i:s'),
                $inc->productionOrder ? $inc->productionOrder->project_name : 'Income',
                'Income',
                $inc->bankAccount ? $inc->bankAccount->bank_name : '',
                $inc->wht_status,
                $inc->withholding_tax_amount,
                $inc->paid_amount,
            ];
        }

        foreach ($expenses as $exp) {
            $csvData[] = [
                'Expense',
                $exp->expense_date->format('Y-m-d'),
                $exp->description ?? 'Expense',
                $exp->category ? $exp->category->name : '',
                $exp->bankAccount ? $exp->bankAccount->bank_name : '',
                'N/A', // wht for expense not tracked same way yet
                '0.00',
                -$exp->amount,
            ];
        }

        // Create temporary directory for Zip
        $tempDir = storage_path('app/temp_export_' . uniqid());
        \Illuminate\Support\Facades\File::makeDirectory($tempDir);

        // Save CSV
        $csvFilePath = $tempDir . '/financial_report_' . $month . '.csv';
        $fp = fopen($csvFilePath, 'w');
        // Add BOM for Excel UTF-8
        fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($csvData as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        // Copy attachments
        $attachmentsDir = $tempDir . '/attachments';
        \Illuminate\Support\Facades\File::makeDirectory($attachmentsDir);

        foreach ($incomes as $inc) {
            if ($inc->slip_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($inc->slip_path)) {
                $ext = pathinfo($inc->slip_path, PATHINFO_EXTENSION);
                \Illuminate\Support\Facades\File::copy(storage_path('app/public/' . $inc->slip_path), $attachmentsDir . '/income_slip_' . $inc->id . '.' . $ext);
            }
            if ($inc->wht_document_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($inc->wht_document_path)) {
                $ext = pathinfo($inc->wht_document_path, PATHINFO_EXTENSION);
                \Illuminate\Support\Facades\File::copy(storage_path('app/public/' . $inc->wht_document_path), $attachmentsDir . '/income_wht_' . $inc->id . '.' . $ext);
            }
        }

        foreach ($expenses as $exp) {
            if ($exp->receipt_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($exp->receipt_path)) {
                $ext = pathinfo($exp->receipt_path, PATHINFO_EXTENSION);
                \Illuminate\Support\Facades\File::copy(storage_path('app/public/' . $exp->receipt_path), $attachmentsDir . '/expense_receipt_' . $exp->id . '.' . $ext);
            }
            if ($exp->wht_document_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($exp->wht_document_path)) {
                $ext = pathinfo($exp->wht_document_path, PATHINFO_EXTENSION);
                \Illuminate\Support\Facades\File::copy(storage_path('app/public/' . $exp->wht_document_path), $attachmentsDir . '/expense_wht_' . $exp->id . '.' . $ext);
            }
        }

        // Create Zip
        $zipFileName = 'financial_export_' . $month . '_' . ($exportType === 'tax_only' ? 'tax' : 'full') . '.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($csvFilePath, basename($csvFilePath));

            $files = \Illuminate\Support\Facades\File::allFiles($attachmentsDir);
            foreach ($files as $file) {
                $zip->addFile($file->getRealPath(), 'attachments/' . $file->getFilename());
            }
            $zip->close();
        }

        // Cleanup Temp Dir
        \Illuminate\Support\Facades\File::deleteDirectory($tempDir);

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    /**
     * Show the form for creating a manual bill.
     */
    public function createManual(Request $request)
    {
        // For the employer dropdown. In a real scenario with thousands of employers,
        // we should use an AJAX search (like select2).
        // For now, I'll pass basic data or use the existing "searchable-select" component if available.
        // Assuming we can pass a few recent employers or rely on the frontend component to fetch.
        // I'll pass all for now as per ProductionController pattern, but lighter.
        $employers = Employer::select('id', 'employerNameTh', 'employerNameEn')
            ->orderBy('employerNameTh')
            ->limit(500) // Safety limit
            ->get();

        $selectedEmployees = collect();
        $employeeIds = $request->input('employee_ids', old('employee_ids'));
        if ($employeeIds && is_array($employeeIds)) {
            $selectedEmployees = \App\Models\Employee::whereIn('id', $employeeIds)
                ->select('id', 'employeeNameTh', 'employeeNameEn', 'name_suffix', 'employer_employee_id')
                ->get();
        }

        return view('financial.create_manual', compact('employers', 'selectedEmployees'));
    }

    /**
     * Store a manual bill (ProductionOrder) and redirect to its financial tab.
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'description' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'bill_date' => 'nullable|date',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        DB::beginTransaction();
        try {
            $employer = Employer::find($request->employer_id);
            $date = $request->bill_date ? Carbon::parse($request->bill_date) : now();

            // 1. Create Production Order (Type: Manual/General)
            // We use work_type_id = null to signify a generic/manual order.
            $order = ProductionOrder::create([
                'employer_id' => $employer->id,
                'work_type_id' => null,
                'type' => 'employer', // Standard type
                'project_name' => $request->description ?: ('Manual Bill - ' . $date->format('d/m/Y')),
                'description' => 'Generated via Finance Hub',
                'status' => 'active', // Active so it appears in lists if needed, or 'completed'
                'created_by' => auth()->id(),
                'financial_data' => [], // Empty init
            ]);

            // 2. Create Financial Group
            $group = $order->financialGroups()->create([
                'name' => 'General',
                'financial_data' => [],
                'production_order_id' => $order->id,
            ]);

            // 3. Attach Employees to the Order
            if ($request->has('employee_ids') && is_array($request->employee_ids)) {
                $employeeIds = $request->employee_ids;
                $itemsToInsert = [];
                foreach ($employeeIds as $empId) {
                    $itemsToInsert[] = [
                        'production_order_id' => $order->id,
                        'employee_id' => $empId,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($itemsToInsert)) {
                    \App\Models\ProductionItem::insert($itemsToInsert);

                    // Fetch the newly created items to get their IDs
                    $insertedItems = \App\Models\ProductionItem::where('production_order_id', $order->id)->pluck('id')->toArray();

                    // Update the group and order's financial_data to include these items in the default pricing tier
                    if (!empty($insertedItems)) {
                        $financialData = [
                            'pricing_mode' => 'per_head', // Default to per head if there are employees
                            'pricing_tiers' => [
                                [
                                    'id' => 'tier_' . time(),
                                    'name' => 'Default Tier',
                                    'price' => 0,
                                    'item_ids' => $insertedItems
                                ]
                            ]
                        ];

                        $group->update(['financial_data' => $financialData]);
                        $order->update(['financial_data' => $financialData]);
                    }
                }
            }

            // 4. (Optional) Create Initial Transaction
            if ($request->filled('amount') && $request->amount > 0) {
                FinancialTransaction::create([
                    'production_order_id' => $order->id,
                    'production_financial_group_id' => $group->id,
                    'type' => 'installment', // Default generic type
                    'amount' => $request->amount,
                    'due_date' => $date, // Due immediately or on date
                    'status' => 'pending',
                    'notes' => 'Initial amount',
                    'created_at' => $date,
                ]);
            }

            DB::commit();

            // Redirect to the Financial Tab of this Order
            // The route for editing production is usually production.index with query param or a specific edit route.
            // Wait, standard CRUD is production.edit?
            // Let's check routes again.
            // Route::resource('production', \App\Http\Controllers\ProductionController::class)
            // So 'production.edit' exists.
            // I'll redirect there with ?tab=financial to open the right tab (assuming frontend supports it).

            return redirect()->route('production.edit', ['production' => $order->id, 'tab' => 'financial'])
                             ->with('success', 'Manual bill created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create bill: ' . $e->getMessage())->withInput();
        }
    }
}

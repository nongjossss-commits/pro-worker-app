<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\LedgerEntry;
use App\Services\FinanceBookReportService;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "บันทึกรายรับรายจ่าย" — a Labor Company Books-styled front end over the
 * main app's existing BankAccount/LedgerEntry/LedgerService (see
 * app/Services/LedgerService.php) — deliberately reuses that backend as-is
 * (proven, transactional, VAT/WHT-aware) rather than a new parallel ledger.
 * Actual create/update/delete of transactions goes through the existing
 * finance.ledger.store/update/destroy routes (Finance\LedgerEntryController)
 * — this controller only supplies the account-centric list/detail/report
 * views that route mirrors resources/views/labor/books/*.blade.php.
 */
class FinanceBookController extends Controller
{
    public function index(Request $request, FinanceBookReportService $service)
    {
        $accounts = BankAccount::orderBy('account_type')->orderBy('bank_name')->get();
        $totalBalance = (float) $accounts->sum('current_balance');

        $activeAccounts = $accounts->where('is_active', true)->values();
        $incomeCategories = IncomeCategory::where('is_active', true)->orderBy('name')->get();
        $expenseCategories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        // "รายงาน" tab — same period-based report FinanceReportController
        // powers standalone (see resolveReportRange() below), merged in here
        // so operational staff don't need to leave this page to see it.
        [$reportFrom, $reportTo] = $this->resolveReportRange($request);
        $reportAccounts = $service->accountBalancesForRange($reportFrom, $reportTo);
        $reportCategoryTransactions = $service->categoryTransactions($reportFrom, $reportTo);
        $activePeriod = $request->input('period', 'month');
        $activeDate = $request->filled('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        return view('financial.books.index', compact(
            'accounts', 'totalBalance', 'activeAccounts', 'incomeCategories', 'expenseCategories',
            'reportAccounts', 'reportCategoryTransactions', 'reportFrom', 'reportTo', 'activePeriod', 'activeDate'
        ));
    }

    public function show(Request $request, BankAccount $account)
    {
        $query = $this->applyTransactionFilters(
            $account->ledgerEntries()->with(['creator', 'category']),
            $request
        );

        $transactions = (clone $query)->latest('entry_date')->latest('id')->paginate(30)->withQueryString();

        $incomeTotal = (float) $account->ledgerEntries()->where('type', 'income')->sum('net_amount');
        $expenseTotal = (float) $account->ledgerEntries()->where('type', 'expense')->sum('net_amount');
        $balance = (float) $account->current_balance;

        $reconciliation = $this->reconcile($request, $account);

        // incomeCategories/expenseCategories here are ALL categories (not
        // just active) so historical entries using a since-deactivated
        // category still resolve correctly in the correction modal; the
        // quick-entry partial filters to ->where('is_active', true) itself.
        $incomeCategories = IncomeCategory::orderBy('name')->get();
        $expenseCategories = ExpenseCategory::orderBy('name')->get();
        $activeAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('financial.books.show', compact(
            'account', 'transactions', 'incomeTotal', 'expenseTotal', 'balance',
            'reconciliation', 'incomeCategories', 'expenseCategories', 'activeAccounts'
        ));
    }

    /**
     * Shared GET-filter logic for show() and export() — same shape as
     * LaborBookController::applyTransactionFilters().
     */
    protected function applyTransactionFilters($query, Request $request)
    {
        if ($request->filled('category_id') && $request->filled('category_type')) {
            $query->where('category_id', $request->input('category_id'))
                ->where('category_type', $request->input('category_type'));
        }
        if ($request->filled('type') && in_array($request->type, ['income', 'expense'])) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('entry_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('entry_date', '<=', $request->input('to'));
        }

        return $query;
    }

    /**
     * Stateless reconciliation — same approach as LaborBookController::
     * reconcile(): nothing persisted, BankAccount.current_balance is
     * already kept in sync transactionally by LedgerService, so a mismatch
     * here means either the typed statement figure is wrong or an entry is
     * missing. Deliberately doesn't touch BankReconciliationService (that
     * one is lifetime-only, not date-scoped, and drives the existing
     * Reconciliation page's own "repair" flow).
     */
    protected function reconcile(Request $request, BankAccount $account): ?array
    {
        if (!$request->filled('reconcile_date') || !$request->filled('statement_balance')) {
            return null;
        }

        $asOf = Carbon::parse($request->input('reconcile_date'))->endOfDay();

        $income = (float) $account->ledgerEntries()->where('type', 'income')
            ->whereDate('entry_date', '<=', $asOf)->sum('net_amount');
        $expense = (float) $account->ledgerEntries()->where('type', 'expense')
            ->whereDate('entry_date', '<=', $asOf)->sum('net_amount');
        $expected = (float) $account->initial_balance + $income - $expense;

        $statement = (float) $request->input('statement_balance');

        return [
            'as_of' => $asOf,
            'expected' => $expected,
            'statement' => $statement,
            'diff' => round($statement - $expected, 2),
        ];
    }

    /**
     * Export one account's transactions (respecting the same filters as
     * show()) to Excel — same PhpSpreadsheet pattern as
     * LaborBookController::exportTransactions().
     */
    public function export(Request $request, BankAccount $account)
    {
        $query = $this->applyTransactionFilters(
            $account->ledgerEntries()->with(['creator', 'category'])->orderBy('entry_date')->orderBy('id'),
            $request
        );

        $transactions = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Book Transactions');

        $accountLabel = $account->account_name ?: $account->bank_name;
        $sheet->setCellValue('A1', \App\Services\BrandService::current()['app_name']);
        $sheet->setCellValue('A2', $accountLabel . ' — ' . __('Transaction History'));
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A1:A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $columns = ['วันที่', 'ประเภท', 'หมวดหมู่', 'รายละเอียด', 'จำนวนเงิน', 'ไฟล์แนบ'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 4])->setValue($col);
        }
        $sheet->getStyle('A4:F4')->applyFromArray(['font' => ['bold' => true]]);

        $row = 5;
        $runningBalance = (float) $account->initial_balance;
        foreach ($transactions as $t) {
            $signedAmount = $t->type === 'income' ? (float) $t->net_amount : -(float) $t->net_amount;
            $runningBalance += $signedAmount;

            $sheet->setCellValue("A{$row}", $t->entry_date->format('d/m/Y'));
            $sheet->setCellValue("B{$row}", $t->type === 'income' ? 'รับ' : 'จ่าย');
            $sheet->setCellValue("C{$row}", $t->category->name ?? '-');
            $sheet->setCellValue("D{$row}", $t->description);
            $sheet->setCellValue("E{$row}", $signedAmount);
            $sheet->setCellValue("F{$row}", $t->receipt_path ? 'มี' : '-');
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'ยอดคงเหลือรวม');
        $sheet->setCellValue("E{$row}", $runningBalance);
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray(['font' => ['bold' => true]]);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf('finance-book_%s_%s.xlsx', \Illuminate\Support\Str::slug($accountLabel), now()->format('Ymd_His'));

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Same `period`+`date` (preset buttons) / `from`+`to` (plain range)
     * resolution as FinanceReportController::resolveRange() — kept as an
     * identical copy (not shared) since that controller's standalone
     * page/PDF/Excel routes stay independent per the "รายงาน" tab plan.
     */
    protected function resolveReportRange(Request $request): array
    {
        if ($request->filled('period') && $request->filled('date')) {
            return $this->resolveReportPeriodRange($request->input('period'), Carbon::parse($request->input('date')));
        }

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))
            : Carbon::now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))
            : Carbon::now();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }

    protected function resolveReportPeriodRange(string $period, Carbon $date): array
    {
        return match ($period) {
            'day' => [$date->copy()->startOfDay(), $date->copy()->startOfDay()],
            'week' => [$date->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay(), $date->copy()->endOfWeek(Carbon::SATURDAY)->startOfDay()],
            'month' => [$date->copy()->startOfMonth()->startOfDay(), $date->copy()->endOfMonth()->startOfDay()],
            'quarter' => [$date->copy()->firstOfQuarter()->startOfDay(), $date->copy()->lastOfQuarter()->startOfDay()],
            'year' => [$date->copy()->startOfYear()->startOfDay(), $date->copy()->endOfYear()->startOfDay()],
            default => [$date->copy()->startOfDay(), $date->copy()->startOfDay()],
        };
    }

    /**
     * Quick-entry expense form, reachable from anywhere (mirrors
     * labor.expenses.create) — posts straight to the existing
     * finance.ledger.store route (type=expense hidden field), so no new
     * store method is needed here.
     */
    public function createExpense(Request $request)
    {
        $accounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        $expenseCategories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('financial.books.expense_create', compact('accounts', 'expenseCategories'));
    }

    /**
     * Super-Admin-only correction for an entry whose accounting day has
     * already closed (see AccountingPeriodService / LedgerService::
     * createCorrection()) — the original entry is never touched; this
     * posts a reversal + corrected replacement dated today instead.
     */
    public function correctEntry(Request $request, LedgerEntry $ledger, LedgerService $service)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'category_id' => ['nullable', 'integer'],
            'category_type' => ['nullable', 'in:income,expense'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (!empty($validated['category_id']) && empty($validated['category_type'])) {
            $validated['category_type'] = $validated['type'];
        }
        $reason = $validated['reason'];
        unset($validated['reason']);

        $service->createCorrection($ledger, $validated, $reason);

        return redirect()->route('finance.books.show', $ledger->bank_account_id)
            ->with('success', __('Correction recorded — the original entry was kept, a reversal + corrected replacement were posted today.'));
    }
}

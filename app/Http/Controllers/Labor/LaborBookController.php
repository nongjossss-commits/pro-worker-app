<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborBookAccount;
use App\Models\LaborBookTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "สมุดบัญชี" — Pro Walker Labor's own company books (separate from the
 * per-team billing ledger in LaborLedgerEntry, and separate from the main
 * app's bank_accounts). View access is intentionally broader than
 * manage-labor-ledger: labor-shareholder must be able to see this (per
 * business requirement — shareholders get read-only visibility into
 * company-wide financials), while labor-team gets none of it. Since
 * `view-labor-ledger` is held by BOTH shareholder and labor-team (see
 * RoleAndPermissionSeeder), it can't be used to gate this — the visibility
 * check here mirrors LaborReportController's role-based gate instead.
 */
class LaborBookController extends Controller
{
    /** Preset category options — kept as a small fixed list for now; a
     *  fuller category-based summary/filter UI is planned as a follow-up. */
    public const CATEGORIES = [
        'income' => [
            'team_payment' => 'Team Payment',
            'capital' => 'Capital',
            'other_income' => 'Other Income',
        ],
        'expense' => [
            'operating_expense' => 'Operating Expense',
            'other_expense' => 'Other Expense',
        ],
    ];

    protected function ensureCanView(Request $request): void
    {
        abort_if($request->user()->hasRole('labor-team'), 403);
    }

    protected function ensureCanManage(Request $request): void
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
    }

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $accounts = LaborBookAccount::withSum(
            ['transactions as income_total' => fn ($q) => $q->where('type', 'income')],
            'amount'
        )->withSum(
            ['transactions as expense_total' => fn ($q) => $q->where('type', 'expense')],
            'amount'
        )->orderBy('name')->get();

        $accounts->each(function ($account) {
            $account->computed_balance = (float) $account->opening_balance
                + (float) ($account->income_total ?? 0)
                - (float) ($account->expense_total ?? 0);
        });

        $totalBalance = $accounts->sum('computed_balance');

        // Category breakdown — company-wide, over a date range (default this
        // month), so "how much came in as team payments vs. other income
        // this month" is visible at a glance without opening every account.
        [$from, $to] = $this->resolveRange($request);
        $categorySummary = LaborBookTransaction::whereBetween('transaction_date', [$from, $to])
            ->selectRaw('type, category, SUM(amount) as total')
            ->groupBy('type', 'category')
            ->orderBy('type')
            ->orderByDesc('total')
            ->get();

        return view('labor.books.index', compact('accounts', 'totalBalance', 'categorySummary', 'from', 'to'));
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['created_by'] = $request->user()->id;

        LaborBookAccount::create($validated);

        return back()->with('success', 'เพิ่มสมุดบัญชีเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborBookAccount $account)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $account->update($validated);

        return back()->with('success', 'อัปเดตสมุดบัญชีเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, LaborBookAccount $account)
    {
        $this->ensureCanManage($request);

        if ($account->transactions()->exists()) {
            return back()->withErrors(['error' => 'ลบไม่ได้เพราะมีประวัติรายการอยู่ — ปิดการใช้งาน (Deactivate) แทน']);
        }

        $account->delete();

        return redirect()->route('labor.books.index')->with('success', 'ลบสมุดบัญชีเรียบร้อยแล้ว');
    }

    public function show(Request $request, LaborBookAccount $account)
    {
        $this->ensureCanView($request);

        $query = $account->transactions()->with(['creator', 'source']);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('type') && in_array($request->type, ['income', 'expense'])) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('transaction_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('transaction_date', '<=', $request->input('to'));
        }

        $transactions = (clone $query)->latest('transaction_date')->latest('id')->paginate(30)->withQueryString();

        $incomeTotal = (float) $account->transactions()->where('type', 'income')->sum('amount');
        $expenseTotal = (float) $account->transactions()->where('type', 'expense')->sum('amount');
        $balance = (float) $account->opening_balance + $incomeTotal - $expenseTotal;

        $reconciliation = $this->reconcile($request, $account);

        return view('labor.books.show', compact('account', 'transactions', 'incomeTotal', 'expenseTotal', 'balance', 'reconciliation'));
    }

    /**
     * Simple, stateless reconciliation: compare what the books say the
     * balance was as of a given date against a statement balance typed in
     * by staff. Nothing is persisted — LaborBookAccount's balance is always
     * computed live (see getCurrentBalanceAttribute()), so there is no
     * stored value that can drift; a mismatch here means either the
     * statement figure is wrong, or a transaction is missing from the books
     * and needs to be added. Mirrors the expected-vs-actual comparison in
     * Finance\ReconciliationController, simplified since there's no stored
     * balance to "repair".
     */
    protected function reconcile(Request $request, LaborBookAccount $account): ?array
    {
        if (!$request->filled('reconcile_date') || !$request->filled('statement_balance')) {
            return null;
        }

        $asOf = Carbon::parse($request->input('reconcile_date'))->endOfDay();

        $income = (float) $account->transactions()->where('type', 'income')
            ->whereDate('transaction_date', '<=', $asOf)->sum('amount');
        $expense = (float) $account->transactions()->where('type', 'expense')
            ->whereDate('transaction_date', '<=', $asOf)->sum('amount');
        $expected = (float) $account->opening_balance + $income - $expense;

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
     * LaborReportController::export().
     */
    public function exportTransactions(Request $request, LaborBookAccount $account)
    {
        $this->ensureCanView($request);

        $query = $account->transactions()->with('creator')->orderBy('transaction_date')->orderBy('id');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('type') && in_array($request->type, ['income', 'expense'])) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('transaction_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('transaction_date', '<=', $request->input('to'));
        }

        $transactions = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Book Transactions');

        $sheet->setCellValue('A1', 'Pro Walker Labor — ' . $account->name . ' — ' . __('Transaction History'));
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $columns = ['วันที่', 'ประเภท', 'หมวดหมู่', 'รายละเอียด', 'จำนวนเงิน'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 3])->setValue($col);
        }
        $sheet->getStyle('A3:E3')->applyFromArray(['font' => ['bold' => true]]);

        $row = 4;
        $runningBalance = (float) $account->opening_balance;
        foreach ($transactions as $t) {
            $signedAmount = $t->type === 'income' ? (float) $t->amount : -(float) $t->amount;
            $runningBalance += $signedAmount;

            $sheet->setCellValue("A{$row}", $t->transaction_date->format('d/m/Y'));
            $sheet->setCellValue("B{$row}", $t->type === 'income' ? 'รับ' : 'จ่าย');
            $sheet->setCellValue("C{$row}", self::CATEGORIES[$t->type][$t->category] ?? $t->category ?? '-');
            $sheet->setCellValue("D{$row}", $t->description);
            $sheet->setCellValue("E{$row}", $signedAmount);
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'ยอดคงเหลือรวม');
        $sheet->setCellValue("E{$row}", $runningBalance);
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray(['font' => ['bold' => true]]);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf('labor-book_%s_%s.xlsx', \Illuminate\Support\Str::slug($account->name), now()->format('Ymd_His'));

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function resolveRange(Request $request): array
    {
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

    public function storeTransaction(Request $request, LaborBookAccount $account)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'category' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
        ]);
        $validated['labor_book_account_id'] = $account->id;
        $validated['created_by'] = $request->user()->id;

        LaborBookTransaction::create($validated);

        return back()->with('success', 'บันทึกรายการเรียบร้อยแล้ว');
    }

    public function updateTransaction(Request $request, LaborBookAccount $account, LaborBookTransaction $transaction)
    {
        $this->ensureCanManage($request);
        abort_unless($transaction->labor_book_account_id === $account->id, 404);

        if ($transaction->isAutoGenerated()) {
            return back()->withErrors(['error' => 'รายการนี้เกิดจากระบบอัตโนมัติ แก้ไขจากหน้านี้ไม่ได้']);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'category' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $transaction->update($validated);

        return back()->with('success', 'อัปเดตรายการเรียบร้อยแล้ว');
    }

    public function destroyTransaction(Request $request, LaborBookAccount $account, LaborBookTransaction $transaction)
    {
        $this->ensureCanManage($request);
        abort_unless($transaction->labor_book_account_id === $account->id, 404);

        if ($transaction->isAutoGenerated()) {
            return back()->withErrors(['error' => 'รายการนี้เกิดจากระบบอัตโนมัติ ลบจากหน้านี้ไม่ได้']);
        }

        $transaction->delete();

        return back()->with('success', 'ลบรายการเรียบร้อยแล้ว');
    }
}

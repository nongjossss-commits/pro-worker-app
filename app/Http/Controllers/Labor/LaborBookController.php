<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborBookAccount;
use App\Models\LaborBookTransaction;
use App\Models\LaborChargeType;
use App\Models\LaborExpenseCategory;
use App\Services\LaborReportService;
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
    protected function ensureCanView(Request $request): void
    {
        abort_if($request->user()->hasRole('labor-team'), 403);
    }

    protected function ensureCanManage(Request $request): void
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
    }

    public function index(Request $request, LaborReportService $service)
    {
        $this->ensureCanView($request);

        $accounts = $service->accountBalances();
        $totalBalance = $accounts->sum('computed_balance');

        // Category breakdown — company-wide, over a date range (default this
        // month), so "how much came in as team payments vs. other income
        // this month" is visible at a glance without opening every account.
        [$from, $to] = $this->resolveRange($request);
        $categorySummary = $service->categorySummary($from, $to);
        $teamSummary = $service->teamPaymentSummary($from, $to);
        $expenseCategories = LaborExpenseCategory::orderBy('name')->get();

        return view('labor.books.index', compact('accounts', 'totalBalance', 'categorySummary', 'teamSummary', 'expenseCategories', 'from', 'to'));
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

        $query = $this->applyTransactionFilters($account->transactions()->with(['creator', 'source.bill', 'chargeType', 'expenseCategory']), $request);

        $transactions = (clone $query)->latest('transaction_date')->latest('id')->paginate(30)->withQueryString();

        $incomeTotal = (float) $account->transactions()->where('type', 'income')->sum('amount');
        $expenseTotal = (float) $account->transactions()->where('type', 'expense')->sum('amount');
        $balance = (float) $account->opening_balance + $incomeTotal - $expenseTotal;

        $reconciliation = $this->reconcile($request, $account);

        $chargeTypes = LaborChargeType::orderBy('name')->get();
        $expenseCategories = LaborExpenseCategory::orderBy('name')->get();

        return view('labor.books.show', compact('account', 'transactions', 'incomeTotal', 'expenseTotal', 'balance', 'reconciliation', 'chargeTypes', 'expenseCategories'));
    }

    /**
     * Shared GET-filter logic for show() and exportTransactions() — kept in
     * one place since both need the exact same filter behavior.
     */
    protected function applyTransactionFilters($query, Request $request)
    {
        if ($request->filled('labor_charge_type_id')) {
            $query->where('labor_charge_type_id', $request->input('labor_charge_type_id'));
        }
        if ($request->filled('labor_expense_category_id')) {
            $query->where('labor_expense_category_id', $request->input('labor_expense_category_id'));
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

        return $query;
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

        $query = $this->applyTransactionFilters(
            $account->transactions()->with(['creator', 'chargeType', 'expenseCategory'])->orderBy('transaction_date')->orderBy('id'),
            $request
        );

        $transactions = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Book Transactions');

        $sheet->setCellValue('A1', 'Pro Walker Labor — ' . $account->name . ' — ' . __('Transaction History'));
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $columns = ['วันที่', 'ประเภท', 'หมวดหมู่', 'รายละเอียด', 'จำนวน', 'จำนวนเงิน', 'ไฟล์แนบ'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 3])->setValue($col);
        }
        $sheet->getStyle('A3:G3')->applyFromArray(['font' => ['bold' => true]]);

        $row = 4;
        $runningBalance = (float) $account->opening_balance;
        foreach ($transactions as $t) {
            $signedAmount = $t->type === 'income' ? (float) $t->amount : -(float) $t->amount;
            $runningBalance += $signedAmount;

            $sheet->setCellValue("A{$row}", $t->transaction_date->format('d/m/Y'));
            $sheet->setCellValue("B{$row}", $t->type === 'income' ? 'รับ' : 'จ่าย');
            $sheet->setCellValue("C{$row}", $t->category_label);
            $sheet->setCellValue("D{$row}", $t->description);
            $sheet->setCellValue("E{$row}", $t->quantity ?? '-');
            $sheet->setCellValue("F{$row}", $signedAmount);
            $sheet->setCellValue("G{$row}", $t->attachment_path ? 'มี' : '-');
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'ยอดคงเหลือรวม');
        $sheet->setCellValue("F{$row}", $runningBalance);
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray(['font' => ['bold' => true]]);

        foreach (range('A', 'G') as $col) {
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
            'labor_charge_type_id' => ['nullable', 'required_if:type,income', 'exists:labor_charge_types,id'],
            'labor_expense_category_id' => ['nullable', 'required_if:type,expense', 'exists:labor_expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ]);
        $validated['labor_charge_type_id'] = $validated['type'] === 'income' ? $validated['labor_charge_type_id'] : null;
        $validated['labor_expense_category_id'] = $validated['type'] === 'expense' ? $validated['labor_expense_category_id'] : null;
        $validated['labor_book_account_id'] = $account->id;
        $validated['created_by'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('labor/book_attachments', 'public');
        }
        unset($validated['attachment']);

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
            'labor_charge_type_id' => ['nullable', 'required_if:type,income', 'exists:labor_charge_types,id'],
            'labor_expense_category_id' => ['nullable', 'required_if:type,expense', 'exists:labor_expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ]);

        // Editing between income<->expense must clear the other type's FK,
        // otherwise a stale labor_charge_type_id/labor_expense_category_id
        // from before the switch would linger and mislabel the row.
        $validated['labor_charge_type_id'] = $validated['type'] === 'income' ? $validated['labor_charge_type_id'] : null;
        $validated['labor_expense_category_id'] = $validated['type'] === 'expense' ? $validated['labor_expense_category_id'] : null;

        if ($request->hasFile('attachment')) {
            if ($transaction->attachment_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($transaction->attachment_path);
            }
            $validated['attachment_path'] = $request->file('attachment')->store('labor/book_attachments', 'public');
        }
        unset($validated['attachment']);

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

        if ($transaction->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($transaction->attachment_path);
        }

        $transaction->delete();

        return back()->with('success', 'ลบรายการเรียบร้อยแล้ว');
    }

    /**
     * Quick-entry expense form — accessible from every Labor page's nav
     * (unlike the per-account "Add Transaction" modal, which requires
     * navigating into a specific account first), since accounting staff
     * asked to be able to record a company expense from wherever they are.
     */
    public function createExpense(Request $request)
    {
        $this->ensureCanManage($request);

        $accounts = LaborBookAccount::where('is_active', true)->orderBy('name')->get();
        $expenseCategories = LaborExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('labor.expenses.create', compact('accounts', 'expenseCategories'));
    }

    public function storeExpense(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'labor_book_account_id' => ['required', 'exists:labor_book_accounts,id'],
            'labor_expense_category_id' => ['required', 'exists:labor_expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ]);

        $validated['type'] = 'expense';
        $validated['created_by'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('labor/book_attachments', 'public');
        }
        unset($validated['attachment']);

        $account = LaborBookAccount::findOrFail($validated['labor_book_account_id']);

        LaborBookTransaction::create($validated);

        return redirect()->route('labor.books.show', $account)->with('success', 'บันทึกรายจ่ายเรียบร้อยแล้ว');
    }
}

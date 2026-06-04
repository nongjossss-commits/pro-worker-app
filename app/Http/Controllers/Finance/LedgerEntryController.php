<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\LedgerEntry;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LedgerEntryController extends Controller
{
    public function __construct(protected LedgerService $service)
    {
    }

    public function index(Request $request)
    {
        $query = LedgerEntry::with(['bankAccount', 'creator'])
            ->latest('entry_date')
            ->latest('id');

        // Filters
        if ($request->filled('type') && in_array($request->type, ['income', 'expense'])) {
            $query->where('type', $request->type);
        }

        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->filled('account_type') && in_array($request->account_type, ['personal', 'company'])) {
            $query->whereHas('bankAccount', fn($q) => $q->where('account_type', $request->account_type));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('entry_no', 'like', "%{$s}%")
                  ->orWhere('counterparty_name', 'like', "%{$s}%")
                  ->orWhere('counterparty_tax_id', 'like', "%{$s}%")
                  ->orWhere('tax_invoice_no', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $entries = $query->paginate(25)->withQueryString();

        $accounts = BankAccount::where('is_active', true)->orderBy('account_type')->orderBy('bank_name')->get();
        $incomeCategories = IncomeCategory::where('is_active', true)->orderBy('name')->get();
        $expenseCategories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('financial.ledger.index', compact('entries', 'accounts', 'incomeCategories', 'expenseCategories'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $data = $this->handleFileUploads($request, $data);

        $entry = $this->service->createEntry($data);

        return redirect()->route('finance.ledger.show', $entry)
            ->with('success', __('Ledger entry :no saved.', ['no' => $entry->entry_no]));
    }

    public function show(LedgerEntry $ledger)
    {
        $ledger->load(['bankAccount.financialProfile', 'creator', 'updater']);
        return view('financial.ledger.show', ['entry' => $ledger]);
    }

    public function update(Request $request, LedgerEntry $ledger)
    {
        $data = $this->validatePayload($request);
        $data = $this->handleFileUploads($request, $data, $ledger);

        $this->service->updateEntry($ledger, $data);

        return redirect()->route('finance.ledger.show', $ledger)
            ->with('success', __('Ledger entry updated.'));
    }

    public function destroy(LedgerEntry $ledger)
    {
        $this->service->deleteEntry($ledger);
        return redirect()->route('finance.ledger.index')
            ->with('success', __('Ledger entry deleted.'));
    }

    protected function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'type' => 'required|in:income,expense',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'category_id' => 'nullable|integer',
            'category_type' => 'nullable|in:income,expense',
            'counterparty_name' => 'nullable|string|max:255',
            'counterparty_tax_id' => 'nullable|string|max:15',
            'gross_amount' => 'required|numeric|min:0',
            'vat_treatment' => 'nullable|in:none,taxable,exempt,zero_rate',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'vat_inclusive' => 'nullable|boolean',
            'wht_type' => 'nullable|in:none,pnd3,pnd53',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'tax_invoice_no' => 'nullable|string|max:50',
            'tax_invoice_date' => 'nullable|date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'receipt' => 'nullable|file|max:20480',
            'attachments.*' => 'nullable|file|max:20480',
        ]);

        // Default category_type จาก type ถ้าไม่ระบุ
        if (!empty($data['category_id']) && empty($data['category_type'])) {
            $data['category_type'] = $data['type'];
        }

        // Default vat_treatment
        if (empty($data['vat_treatment'])) {
            $data['vat_treatment'] = 'none';
        }
        if (empty($data['wht_type'])) {
            $data['wht_type'] = 'none';
        }

        return $data;
    }

    protected function handleFileUploads(Request $request, array $data, ?LedgerEntry $existing = null): array
    {
        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('ledger/receipts', 'public');
        }

        if ($request->hasFile('attachments')) {
            $existingFiles = $existing?->attached_files ?? [];
            foreach ($request->file('attachments') as $file) {
                $existingFiles[] = [
                    'path' => $file->store('ledger/attachments', 'public'),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
            $data['attached_files'] = $existingFiles;
        }

        // Remove keys ที่ไม่ใช่ DB columns
        unset($data['receipt'], $data['attachments'], $data['vat_inclusive']);

        return $data;
    }
}

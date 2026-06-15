<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialProfile;
use App\Models\TaxInvoice;
use App\Services\TaxInvoiceService;
use Illuminate\Http\Request;

class TaxInvoiceController extends Controller
{
    public function __construct(protected TaxInvoiceService $service)
    {
    }

    public function index(Request $request)
    {
        $query = TaxInvoice::with(['issuerProfile', 'creator'])
            ->latest('invoice_date')
            ->latest('id');

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('issuer_profile_id')) {
            $query->where('issuer_profile_id', $request->issuer_profile_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_tax_id', 'like', "%{$s}%");
            });
        }

        $invoices = $query->paginate(25)->withQueryString();

        $profiles = FinancialProfile::orderBy('name')->get();
        $fiscalYears = TaxInvoice::select('fiscal_year')->distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year');

        return view('financial.tax_invoices.index', compact('invoices', 'profiles', 'fiscalYears'));
    }

    public function create()
    {
        $profiles = FinancialProfile::orderBy('name')->get();
        return view('financial.tax_invoices.create', compact('profiles'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $invoice = $this->service->create($data);

        if ($request->input('action') === 'issue') {
            $invoice = $this->service->issue($invoice);
        }

        return redirect()->route('finance.tax-invoices.show', $invoice)
            ->with('success', __('Tax invoice :no created.', ['no' => $invoice->invoice_no]));
    }

    public function show(TaxInvoice $taxInvoice)
    {
        $taxInvoice->load(['issuerProfile', 'ledgerEntry.bankAccount', 'creator', 'updater']);
        return view('financial.tax_invoices.show', ['invoice' => $taxInvoice]);
    }

    public function update(Request $request, TaxInvoice $taxInvoice)
    {
        if ($request->has('action_void')) {
            $reason = $request->input('void_reason', '');
            $this->service->void($taxInvoice, $reason);
            return redirect()->route('finance.tax-invoices.show', $taxInvoice)
                ->with('success', __('Tax invoice voided.'));
        }

        if ($request->has('action_issue')) {
            $this->service->issue($taxInvoice);
            return redirect()->route('finance.tax-invoices.show', $taxInvoice)
                ->with('success', __('Tax invoice issued.'));
        }

        $data = $this->validatePayload($request);
        $this->service->update($taxInvoice, $data);
        return redirect()->route('finance.tax-invoices.show', $taxInvoice)
            ->with('success', __('Tax invoice updated.'));
    }

    public function destroy(TaxInvoice $taxInvoice)
    {
        if ($taxInvoice->isLocked()) {
            return back()->withErrors(['error' => __('Cannot delete a locked invoice. Void it instead.')]);
        }
        $taxInvoice->delete();
        return redirect()->route('finance.tax-invoices.index')
            ->with('success', __('Draft tax invoice deleted.'));
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'invoice_date' => 'required|date',
            'fiscal_year' => 'nullable|integer|min:2000|max:2100',
            'issuer_profile_id' => 'required|exists:financial_profiles,id',
            'customer_name' => 'required|string|max:255',
            'customer_tax_id' => 'nullable|string|max:15',
            'customer_branch' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'vat_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
    }
}

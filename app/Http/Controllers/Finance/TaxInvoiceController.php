<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialProfile;
use App\Models\TaxInvoice;
use App\Services\TaxInvoicePdfService;
use App\Services\TaxInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaxInvoiceController extends Controller
{
    public function __construct(
        protected TaxInvoiceService $service,
        protected TaxInvoicePdfService $pdfService,
    ) {
    }

    /**
     * Stream the invoice PDF. Draft = regenerate every time (preview).
     * Issued/Void = serve persisted copy from storage if available.
     * `?copy=copy` to mark "สำเนา" instead of "ต้นฉบับ".
     */
    public function pdf(Request $request, TaxInvoice $taxInvoice)
    {
        $taxInvoice->loadMissing('issuerProfile');
        $copyLabel = $request->input('copy') === 'copy' ? 'copy' : 'original';

        // Issued/void invoices have an immutable persisted PDF in `notes` metadata path
        $persistedPath = $this->resolvePersistedPath($taxInvoice);
        if ($persistedPath && Storage::disk('public')->exists($persistedPath)) {
            $binary = Storage::disk('public')->get($persistedPath);
        } else {
            $binary = $this->pdfService->generate($taxInvoice, $copyLabel);
        }

        $filename = $taxInvoice->invoice_no . '.pdf';
        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Derive the storage path used by TaxInvoicePdfService::generateAndStore().
     */
    protected function resolvePersistedPath(TaxInvoice $invoice): string
    {
        return sprintf('tax_invoices/%04d/%s.pdf', $invoice->fiscal_year, $invoice->invoice_no);
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
        $profiles = FinancialProfile::with('bankAccounts')->orderBy('name')->get();
        $thaiBanks = config('thai_banks', []);
        return view('financial.tax_invoices.create', compact('profiles', 'thaiBanks'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $invoice = $this->service->create($data);

        if ($request->input('action') === 'issue') {
            $invoice = $this->service->issue($invoice);
            $this->persistPdf($invoice);
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
            $invoice = $this->service->issue($taxInvoice);
            $this->persistPdf($invoice);
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

    /**
     * Snapshot the issued invoice to storage. Soft-failure: log + continue.
     */
    protected function persistPdf(TaxInvoice $invoice): void
    {
        try {
            $this->pdfService->generateAndStore($invoice);
        } catch (\Throwable $e) {
            \Log::warning('Tax invoice PDF persist failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function validatePayload(Request $request): array
    {
        // payment_methods arrives as a JSON string from the Alpine hidden input.
        // Decode it before validation so the array rules apply, and so the
        // controller hands a real array to TaxInvoiceService.
        if ($request->has('payment_methods') && is_string($request->payment_methods)) {
            $decoded = json_decode($request->payment_methods, true);
            $request->merge(['payment_methods' => is_array($decoded) ? $decoded : []]);
        }

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
            'payment_methods' => 'nullable|array',
            'payment_methods.*.type' => 'required_with:payment_methods|in:cash,transfer,promptpay,other',
            'payment_methods.*.bank_name' => 'nullable|string|max:255',
            'payment_methods.*.bank_code' => 'nullable|string|max:20',
            'payment_methods.*.account_name' => 'nullable|string|max:255',
            'payment_methods.*.account_number' => 'nullable|string|max:50',
            'payment_methods.*.promptpay_id' => 'nullable|string|max:20',
            'payment_methods.*.note' => 'nullable|string|max:255',
        ]);
    }
}

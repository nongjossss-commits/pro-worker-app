<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\FinancialProfile;
use App\Models\LaborBill;
use App\Models\LaborTaxInvoice;
use App\Services\LaborTaxInvoicePdfService;
use App\Services\LaborTaxInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ใบกำกับภาษี for Pro Walker Labor — mirrors Finance\TaxInvoiceController,
 * gated the same way as the rest of Central Billing (manage-labor-ledger via
 * inline abort_unless, not constructor middleware — Labor module convention).
 */
class LaborTaxInvoiceController extends Controller
{
    public function __construct(
        protected LaborTaxInvoiceService $service,
        protected LaborTaxInvoicePdfService $pdfService,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $query = LaborTaxInvoice::with(['bill.team', 'issuerProfile', 'creator'])
            ->latest('invoice_date')
            ->latest('id');

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%");
            });
        }

        $invoices = $query->paginate(25)->withQueryString();
        $fiscalYears = LaborTaxInvoice::select('fiscal_year')->distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year');

        return view('labor.tax-invoices.index', compact('invoices', 'fiscalYears'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $profiles = FinancialProfile::with('bankAccounts')->orderBy('name')->get();
        $bills = LaborBill::with('team')->active()->orderByDesc('issued_at')->limit(100)->get();
        $thaiBanks = config('thai_banks', []);

        $prefill = null;
        if ($request->filled('labor_bill_id')) {
            $bill = LaborBill::with('team', 'financialProfile')->find($request->labor_bill_id);
            if ($bill) {
                $prefill = $this->service->previewFromBill($bill);
            }
        }

        return view('labor.tax-invoices.create', compact('profiles', 'bills', 'thaiBanks', 'prefill'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $data = $this->validatePayload($request);
        $invoice = $this->service->create($data);

        if ($request->input('action') === 'issue') {
            $invoice = $this->service->issue($invoice);
            $this->persistPdf($invoice);
        }

        return redirect()->route('labor.tax-invoices.show', $invoice)
            ->with('success', __('Tax invoice :no created.', ['no' => $invoice->invoice_no]));
    }

    public function show(Request $request, LaborTaxInvoice $taxInvoice)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $taxInvoice->load(['bill.team', 'issuerProfile', 'creator', 'updater']);
        return view('labor.tax-invoices.show', ['invoice' => $taxInvoice]);
    }

    public function update(Request $request, LaborTaxInvoice $taxInvoice)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        if ($request->has('action_void')) {
            $this->service->void($taxInvoice, $request->input('void_reason', ''));
            return redirect()->route('labor.tax-invoices.show', $taxInvoice)->with('success', __('Tax invoice voided.'));
        }

        if ($request->has('action_issue')) {
            $invoice = $this->service->issue($taxInvoice);
            $this->persistPdf($invoice);
            return redirect()->route('labor.tax-invoices.show', $taxInvoice)->with('success', __('Tax invoice issued.'));
        }

        $data = $this->validatePayload($request);
        $this->service->update($taxInvoice, $data);
        return redirect()->route('labor.tax-invoices.show', $taxInvoice)->with('success', __('Tax invoice updated.'));
    }

    public function destroy(Request $request, LaborTaxInvoice $taxInvoice)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        if ($taxInvoice->isLocked()) {
            return back()->withErrors(['error' => __('Cannot delete a locked invoice. Void it instead.')]);
        }
        $taxInvoice->delete();
        return redirect()->route('labor.tax-invoices.index')->with('success', __('Draft tax invoice deleted.'));
    }

    public function pdf(Request $request, LaborTaxInvoice $taxInvoice)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $taxInvoice->loadMissing('issuerProfile', 'bill');
        $copyLabel = $request->input('copy') === 'copy' ? 'copy' : 'original';

        $persistedPath = sprintf('labor_tax_invoices/%04d/%s.pdf', $taxInvoice->fiscal_year, $taxInvoice->invoice_no);
        if (Storage::disk('public')->exists($persistedPath)) {
            $binary = Storage::disk('public')->get($persistedPath);
        } else {
            $binary = $this->pdfService->generate($taxInvoice, $copyLabel);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $taxInvoice->invoice_no . '.pdf"',
        ]);
    }

    protected function persistPdf(LaborTaxInvoice $invoice): void
    {
        try {
            $this->pdfService->generateAndStore($invoice);
        } catch (\Throwable $e) {
            \Log::warning('Labor tax invoice PDF persist failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function validatePayload(Request $request): array
    {
        if ($request->has('payment_methods') && is_string($request->payment_methods)) {
            $decoded = json_decode($request->payment_methods, true);
            $request->merge(['payment_methods' => is_array($decoded) ? $decoded : []]);
        }

        return $request->validate([
            'invoice_date' => 'required|date',
            'fiscal_year' => 'nullable|integer|min:2000|max:2100',
            'labor_bill_id' => 'nullable|exists:labor_bills,id',
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

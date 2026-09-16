<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\FinancialProfile;
use App\Models\FinancialTransaction;
use App\Services\CreditNotePdfService;
use App\Services\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CreditNoteController extends Controller
{
    public function __construct(
        protected CreditNoteService $service,
        protected CreditNotePdfService $pdfService,
    ) {
    }

    /**
     * Stream the credit note PDF. Draft = regenerate every time (preview).
     * Issued/Void = serve persisted copy from storage if available.
     */
    public function pdf(Request $request, CreditNote $creditNote)
    {
        $creditNote->loadMissing('issuerProfile');
        $copyLabel = $request->input('copy') === 'copy' ? 'copy' : 'original';

        $persistedPath = sprintf('credit_notes/%04d/%s.pdf', $creditNote->fiscal_year, $creditNote->credit_note_no);
        if (Storage::disk('public')->exists($persistedPath)) {
            $binary = Storage::disk('public')->get($persistedPath);
        } else {
            $binary = $this->pdfService->generate($creditNote, $copyLabel);
        }

        $filename = $creditNote->credit_note_no . '.pdf';
        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function index(Request $request)
    {
        $query = CreditNote::with(['issuerProfile', 'creator', 'financialTransaction'])
            ->latest('credit_note_date')
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
                $q->where('credit_note_no', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_tax_id', 'like', "%{$s}%");
            });
        }

        $creditNotes = $query->paginate(25)->withQueryString();
        $fiscalYears = CreditNote::select('fiscal_year')->distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year');

        return view('financial.credit_notes.index', compact('creditNotes', 'fiscalYears'));
    }

    public function create(Request $request)
    {
        $profiles = FinancialProfile::orderBy('name')->get();

        $transaction = null;
        if ($request->filled('financial_transaction_id')) {
            $transaction = FinancialTransaction::with('productionOrder.employer')
                ->find($request->financial_transaction_id);
        }

        return view('financial.credit_notes.create', compact('profiles', 'transaction'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $note = $this->service->create($data);

        if ($request->input('action') === 'issue') {
            $note = $this->service->issue($note);
            $this->persistPdf($note);
        }

        return redirect()->route('finance.credit-notes.show', $note)
            ->with('success', __('Credit note :no created.', ['no' => $note->credit_note_no]));
    }

    public function show(CreditNote $creditNote)
    {
        $creditNote->load(['issuerProfile', 'financialTransaction.productionOrder.employer', 'relatedTaxInvoice', 'creator', 'updater']);
        return view('financial.credit_notes.show', ['note' => $creditNote]);
    }

    public function issue(CreditNote $creditNote)
    {
        $note = $this->service->issue($creditNote);
        $this->persistPdf($note);
        return redirect()->route('finance.credit-notes.show', $creditNote)
            ->with('success', __('Credit note issued.'));
    }

    public function void(Request $request, CreditNote $creditNote)
    {
        $request->validate(['void_reason' => 'required|string|max:255']);
        $this->service->void($creditNote, $request->void_reason);
        return redirect()->route('finance.credit-notes.show', $creditNote)
            ->with('success', __('Credit note voided.'));
    }

    public function destroy(CreditNote $creditNote)
    {
        if ($creditNote->isLocked()) {
            return back()->withErrors(['error' => __('Cannot delete a locked credit note. Void it instead.')]);
        }
        $creditNote->delete();
        return redirect()->route('finance.credit-notes.index')
            ->with('success', __('Draft credit note deleted.'));
    }

    /**
     * Snapshot the issued credit note to storage. Soft-failure: log + continue
     * — same discipline as TaxInvoiceController::persistPdf().
     */
    protected function persistPdf(CreditNote $note): void
    {
        try {
            $this->pdfService->generateAndStore($note);
        } catch (\Throwable $e) {
            \Log::warning('Credit note PDF persist failed', [
                'credit_note_id' => $note->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'credit_note_date' => 'required|date',
            'fiscal_year' => 'nullable|integer|min:2000|max:2100',
            'financial_transaction_id' => 'required|exists:financial_transactions,id',
            'related_tax_invoice_id' => 'nullable|exists:tax_invoices,id',
            'issuer_profile_id' => 'nullable|exists:financial_profiles,id',
            'customer_name' => 'required|string|max:255',
            'customer_tax_id' => 'nullable|string|max:15',
            'customer_branch' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0.01',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'vat_amount' => 'required|numeric|min:0',
            'total_credit' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:2000',
            'notes' => 'nullable|string',
        ]);
    }
}

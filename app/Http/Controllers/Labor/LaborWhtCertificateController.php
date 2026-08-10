<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborBill;
use App\Models\LaborWhtCertificate;
use App\Services\LaborWhtCertificatePdfService;
use App\Services\LaborWhtCertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ใบหัก ณ ที่จ่าย for Pro Walker Labor — mirrors Finance\WhtCertificateController.
 */
class LaborWhtCertificateController extends Controller
{
    public function __construct(
        protected LaborWhtCertificateService $service,
        protected LaborWhtCertificatePdfService $pdfService,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $query = LaborWhtCertificate::with(['bill.team', 'creator'])
            ->latest('paid_at')
            ->latest('id');

        if ($request->filled('wht_type') && in_array($request->wht_type, ['pnd3', 'pnd53'])) {
            $query->where('wht_type', $request->wht_type);
        }
        if ($request->filled('year')) {
            $query->where('tax_period_year', $request->year);
        }
        if ($request->filled('month')) {
            $query->where('tax_period_month', $request->month);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('cert_no', 'like', "%{$s}%")
                  ->orWhere('payer_name', 'like', "%{$s}%")
                  ->orWhere('payee_name', 'like', "%{$s}%");
            });
        }

        $certificates = $query->paginate(25)->withQueryString();

        return view('labor.wht-certificates.index', compact('certificates'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $bills = LaborBill::with('team', 'financialProfile')->active()->orderByDesc('issued_at')->limit(100)->get();
        return view('labor.wht-certificates.create', compact('bills'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $data = $this->validatePayload($request);
        $data = $this->handleFileUpload($request, $data);

        $cert = $this->service->create($data);

        if ($request->input('action') === 'issue') {
            $cert = $this->service->issue($cert);
            $cert = $this->persistPdf($cert);
        }

        return redirect()->route('labor.wht-certificates.show', $cert)
            ->with('success', __('WHT certificate :no created.', ['no' => $cert->cert_no]));
    }

    public function show(Request $request, LaborWhtCertificate $whtCertificate)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $whtCertificate->load(['bill.team', 'creator', 'updater']);
        return view('labor.wht-certificates.show', ['cert' => $whtCertificate]);
    }

    public function update(Request $request, LaborWhtCertificate $whtCertificate)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        if ($request->has('action_issue')) {
            $cert = $this->service->issue($whtCertificate);
            $this->persistPdf($cert);
            return redirect()->route('labor.wht-certificates.show', $whtCertificate)->with('success', __('WHT certificate issued.'));
        }

        if ($request->has('action_submitted')) {
            $this->service->markSubmitted($whtCertificate);
            return redirect()->route('labor.wht-certificates.show', $whtCertificate)->with('success', __('Marked as submitted.'));
        }

        $data = $this->validatePayload($request);
        $data = $this->handleFileUpload($request, $data, $whtCertificate);
        $this->service->update($whtCertificate, $data);
        return redirect()->route('labor.wht-certificates.show', $whtCertificate)->with('success', __('WHT certificate updated.'));
    }

    public function destroy(Request $request, LaborWhtCertificate $whtCertificate)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        if ($whtCertificate->isLocked()) {
            return back()->withErrors(['error' => __('Cannot delete a submitted certificate.')]);
        }
        $whtCertificate->delete();
        return redirect()->route('labor.wht-certificates.index')->with('success', __('WHT certificate deleted.'));
    }

    public function pdf(Request $request, LaborWhtCertificate $whtCertificate)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        if ($whtCertificate->certificate_path && Storage::disk('public')->exists($whtCertificate->certificate_path)) {
            $binary = Storage::disk('public')->get($whtCertificate->certificate_path);
        } else {
            $binary = $this->pdfService->generate($whtCertificate);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $whtCertificate->cert_no . '.pdf"',
        ]);
    }

    protected function persistPdf(LaborWhtCertificate $cert): LaborWhtCertificate
    {
        if ($cert->certificate_path) {
            return $cert;
        }
        try {
            $path = $this->pdfService->generateAndStore($cert);
            $cert->update(['certificate_path' => $path]);
        } catch (\Throwable $e) {
            \Log::warning('Labor WHT cert PDF persist failed', [
                'cert_id' => $cert->id,
                'error' => $e->getMessage(),
            ]);
        }
        return $cert->fresh();
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'type' => 'required|in:issued,received',
            'wht_type' => 'required|in:pnd3,pnd53',
            'tax_period_year' => 'nullable|integer|min:2000|max:2100',
            'tax_period_month' => 'nullable|integer|min:1|max:12',
            'labor_bill_id' => 'nullable|exists:labor_bills,id',
            'payer_name' => 'required|string|max:255',
            'payer_tax_id' => 'nullable|string|max:15',
            'payee_name' => 'required|string|max:255',
            'payee_tax_id' => 'nullable|string|max:15',
            'income_type' => 'nullable|string|max:50',
            'amount_paid' => 'required|numeric|min:0',
            'wht_rate' => 'required|numeric|min:0|max:100',
            'wht_amount' => 'required|numeric|min:0',
            'paid_at' => 'required|date',
            'certificate' => 'nullable|file|max:20480',
            'notes' => 'nullable|string',
        ]);
    }

    protected function handleFileUpload(Request $request, array $data, ?LaborWhtCertificate $existing = null): array
    {
        if ($request->hasFile('certificate')) {
            $data['certificate_path'] = $request->file('certificate')->store('labor/wht_certificates', 'public');
        }
        unset($data['certificate']);
        return $data;
    }
}

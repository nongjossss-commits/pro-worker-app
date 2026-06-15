<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\WhtCertificate;
use App\Services\WhtCertificateService;
use Illuminate\Http\Request;

class WhtCertificateController extends Controller
{
    public function __construct(protected WhtCertificateService $service)
    {
    }

    public function index(Request $request)
    {
        $query = WhtCertificate::with('creator')
            ->latest('paid_at')
            ->latest('id');

        if ($request->filled('type') && in_array($request->type, ['issued', 'received'])) {
            $query->where('type', $request->type);
        }

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
                  ->orWhere('payer_tax_id', 'like', "%{$s}%")
                  ->orWhere('payee_name', 'like', "%{$s}%")
                  ->orWhere('payee_tax_id', 'like', "%{$s}%");
            });
        }

        $certificates = $query->paginate(25)->withQueryString();

        // Summary per period
        $periods = WhtCertificate::select('tax_period_year', 'tax_period_month')
            ->distinct()
            ->orderByDesc('tax_period_year')
            ->orderByDesc('tax_period_month')
            ->get();

        return view('financial.wht_certificates.index', compact('certificates', 'periods'));
    }

    public function create()
    {
        return view('financial.wht_certificates.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $data = $this->handleFileUpload($request, $data);

        $cert = $this->service->create($data);

        if ($request->input('action') === 'issue') {
            $cert = $this->service->issue($cert);
        }

        return redirect()->route('finance.wht-certificates.show', $cert)
            ->with('success', __('WHT certificate :no created.', ['no' => $cert->cert_no]));
    }

    public function show(WhtCertificate $whtCertificate)
    {
        $whtCertificate->load(['creator', 'updater']);
        return view('financial.wht_certificates.show', ['cert' => $whtCertificate]);
    }

    public function update(Request $request, WhtCertificate $whtCertificate)
    {
        if ($request->has('action_issue')) {
            $this->service->issue($whtCertificate);
            return redirect()->route('finance.wht-certificates.show', $whtCertificate)
                ->with('success', __('WHT certificate issued.'));
        }

        if ($request->has('action_submitted')) {
            $this->service->markSubmitted($whtCertificate);
            return redirect()->route('finance.wht-certificates.show', $whtCertificate)
                ->with('success', __('Marked as submitted.'));
        }

        $data = $this->validatePayload($request);
        $data = $this->handleFileUpload($request, $data, $whtCertificate);
        $this->service->update($whtCertificate, $data);
        return redirect()->route('finance.wht-certificates.show', $whtCertificate)
            ->with('success', __('WHT certificate updated.'));
    }

    public function destroy(WhtCertificate $whtCertificate)
    {
        if ($whtCertificate->isLocked()) {
            return back()->withErrors(['error' => __('Cannot delete a submitted certificate.')]);
        }
        $whtCertificate->delete();
        return redirect()->route('finance.wht-certificates.index')
            ->with('success', __('WHT certificate deleted.'));
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'type' => 'required|in:issued,received',
            'wht_type' => 'required|in:pnd3,pnd53',
            'tax_period_year' => 'nullable|integer|min:2000|max:2100',
            'tax_period_month' => 'nullable|integer|min:1|max:12',
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

    protected function handleFileUpload(Request $request, array $data, ?WhtCertificate $existing = null): array
    {
        if ($request->hasFile('certificate')) {
            $data['certificate_path'] = $request->file('certificate')->store('wht/certificates', 'public');
        }
        unset($data['certificate']);
        return $data;
    }
}

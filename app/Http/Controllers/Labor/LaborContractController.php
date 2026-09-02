<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\LaborTeam;
use App\Models\ProWorkerContract;
use App\Models\ProWorkerContractTemplate;
use App\Services\ProWorkerContractPdfService;
use App\Services\ProWorkerContractService;
use App\Services\ProWorkerFormFieldsResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Issuing a Pro Worker <-> Employer contract — reachable by anyone who
 * passed the `labor.access` route middleware (see routes/labor.php), but
 * ALSO requires labor_team_id to be set (checked here, not in the
 * middleware) since a Labor-access user with no team yet should still see
 * the page with a "contact Super Admin" banner rather than a blanket 403.
 */
class LaborContractController extends Controller
{
    public function __construct(protected ProWorkerFormFieldsResolver $formFields)
    {
    }

    public function create()
    {
        $template = ProWorkerContractTemplate::latest()->first();

        return view('labor.contracts.create', [
            'template' => $template,
            'formItems' => $template ? $this->formFields->unifiedItems($template) : [],
        ]);
    }

    public function store(Request $request, ProWorkerContractService $service)
    {
        $user = Auth::user();
        abort_unless($user->labor_team_id, 403, __('You have not been assigned to a Pro Walker Labour team yet. Please contact a Super Admin.'));

        $template = ProWorkerContractTemplate::findOrFail($request->input('template_id'));
        $fields = $request->input('fields', []);

        if ($error = $this->validateFields($template, $fields)) {
            return back()->withErrors($error)->withInput();
        }

        $fields = $this->resolveFeeGroupValues($template, $fields);

        $contract = $service->issue($template, $user, $fields);

        return redirect()->route('labor.contracts.show', $contract)
            ->with('success', __('Contract issued successfully.'));
    }

    /**
     * Renders a check-the-layout PDF preview from whatever is currently in
     * the form — no persistence, no contract_no consumed (see
     * ProWorkerContractPdfService::preview()). Reachable from both the
     * create and edit forms via a second submit button
     * (formaction+formtarget="_blank") so the original form/tab keeps its
     * filled-in state no matter what the preview shows.
     */
    public function preview(Request $request)
    {
        $template = ProWorkerContractTemplate::findOrFail($request->input('template_id'));
        $fields = $this->resolveFeeGroupValues($template, $request->input('fields', []));

        $pdfBytes = app(ProWorkerContractPdfService::class)->preview($template, $fields);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }

    /**
     * The same "sees every team's contracts, or only their own issuances"
     * split used by index()'s query scope, applied here so a direct-URL
     * visit to another team's contract can't bypass that list-level
     * filtering. See index()'s $seesAllTeams for why this exact role list.
     */
    protected function assertCanAccessContract(ProWorkerContract $contract): void
    {
        $user = Auth::user();
        $seesAllTeams = $user->hasAnyRole(['super-admin', 'admin', 'labor-accounting', 'labor-shareholder']);

        abort_if(!$seesAllTeams && $contract->issued_by !== $user->id, 403);
    }

    public function show(ProWorkerContract $contract)
    {
        $this->assertCanAccessContract($contract);

        return view('labor.contracts.show', compact('contract'));
    }

    /**
     * Corrections to an already-issued contract — see ProWorkerContract's
     * docblock: field_values/file_path/worker_count can be fixed, but
     * contract_no/issued_at/issued_by/labor_team_id never change and there
     * is no delete/cancel route anywhere for this model.
     */
    public function edit(ProWorkerContract $contract)
    {
        $this->assertCanAccessContract($contract);

        $template = $contract->template;

        return view('labor.contracts.edit', [
            'contract' => $contract,
            'template' => $template,
            'formItems' => $this->formFields->unifiedItems($template),
        ]);
    }

    public function update(Request $request, ProWorkerContract $contract, ProWorkerContractService $service)
    {
        $this->assertCanAccessContract($contract);

        $template = $contract->template;
        $fields = $request->input('fields', []);

        if ($error = $this->validateFields($template, $fields)) {
            return back()->withErrors($error)->withInput();
        }

        $fields = $this->resolveFeeGroupValues($template, $fields);

        $service->update($contract, $fields);

        return redirect()->route('labor.contracts.show', $contract)
            ->with('success', __('Contract updated successfully.'));
    }

    /**
     * A contract can be issued completely blank and filled in later via
     * the correction flow — no field is required, contract_no generation
     * is unaffected either way. The only thing still checked here is data
     * CONSISTENCY for whatever was voluntarily filled in (see below), not
     * completeness. Shared by store() and update() so both stay in sync.
     */
    protected function validateFields(ProWorkerContractTemplate $template, array $fields): ?array
    {
        // A Thai Soi/Road with no English counterpart would otherwise
        // leave that detail silently missing from the composed English
        // address (see _address_group.blade.php/proworker-address-picker.js
        // — a blank EN part is OMITTED, never falls back to untranslated
        // Thai text). This does NOT require Soi/Road to be filled in at
        // all — only that if the Thai half was typed, the English half was
        // too, since the picker already enforces that pairing client-side.
        foreach (array_keys($this->formFields->addressGroups($template)) as $groupId) {
            foreach (['soi', 'road'] as $part) {
                $th = trim((string) ($fields["{$groupId}_{$part}"] ?? ''));
                $en = trim((string) ($fields["{$groupId}_{$part}_en"] ?? ''));
                if ($th !== '' && $en === '') {
                    return ['fields' => __('Please fill in the English Soi/Road for every address that has a Thai Soi/Road.')];
                }
            }
        }

        return null;
    }

    /**
     * The `include_signature` choice is asked fresh every time via a
     * confirmation modal on the triggering page (see
     * _download_choice_modal.blade.php) — defaults to true (include) if
     * the param is somehow missing, so an old bookmarked/shared link still
     * behaves like the original always-full download. When false, the
     * PDF is regenerated on the fly with the Contractor's signature/stamp
     * fields skipped (see ProWorkerContractPdfService::renderVariant())
     * and streamed directly — the CANONICAL stored file from issue()/
     * update() is never touched, so re-downloading "with signature"
     * afterwards is unaffected.
     */
    public function download(Request $request, ProWorkerContract $contract, ProWorkerContractPdfService $pdfService)
    {
        $this->assertCanAccessContract($contract);

        if (!Storage::disk('public')->exists($contract->file_path)) {
            abort(404);
        }

        if ($request->boolean('include_signature', true)) {
            return response()->download(Storage::disk('public')->path($contract->file_path), $contract->contract_no . '.pdf');
        }

        $pdfBytes = $pdfService->renderVariant($contract->template, $contract->field_values ?? [], $contract->contract_no, includeSignatureStamp: false);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $contract->contract_no . '.pdf"',
        ]);
    }

    /**
     * Inline, in-browser view of an already-issued contract's STORED file
     * (always the full version, signature/stamp included — this is for
     * looking at it, not for handing it out, so the signature choice from
     * download() doesn't apply here).
     */
    public function view(ProWorkerContract $contract)
    {
        $this->assertCanAccessContract($contract);

        if (!Storage::disk('public')->exists($contract->file_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($contract->file_path), [
            'Content-Disposition' => 'inline; filename="' . $contract->contract_no . '.pdf"',
        ]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ProWorkerContract::with(['issuer', 'team', 'template'])->latest('issued_at');

        $seesAllTeams = $user->hasAnyRole(['super-admin', 'admin', 'labor-accounting', 'labor-shareholder']);
        if (!$seesAllTeams) {
            $query->where('issued_by', $user->id);
        }

        if ($request->filled('q')) {
            $search = '%' . trim($request->input('q')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('employer_name_snapshot', 'like', $search)
                    ->orWhere('contract_no', 'like', $search);
            });
        }

        // Only offered to roles that see every team's contracts anyway —
        // everyone else already sees only their own team's issuances (via
        // the issued_by scope above), so a team dropdown would have at
        // most one meaningful option for them.
        if ($seesAllTeams && $request->filled('team_id')) {
            $query->where('labor_team_id', $request->input('team_id'));
        }

        $contracts = $query->paginate(20)->withQueryString();
        $teams = $seesAllTeams ? LaborTeam::orderBy('name')->get(['id', 'name']) : collect();

        return view('labor.contracts.index', compact('contracts', 'teams', 'seesAllTeams'));
    }

    /**
     * Public, no-login QR-code target (see routes/labor.php's unauthenticated
     * route group) — confirms a contract number is genuine and shows the
     * bare minimum: employer name + issuing company. Deliberately never
     * exposes field_values, the issuing staff member's name, or the
     * internal team, per the confidentiality requirement between
     * contracting parties.
     */
    public function publicVerify(string $contractNo)
    {
        $contract = ProWorkerContract::where('contract_no', trim($contractNo))->first();
        $companyProfile = CompanyProfile::where('is_default', true)->first() ?? CompanyProfile::first();

        return view('labor.contracts.public_verify', [
            'contract' => $contract,
            'companyProfile' => $companyProfile,
        ]);
    }

    /**
     * "เช็คสัญญาของจริง" — authenticity lookup by contract number. Requires
     * Labor access (route middleware), per the user's explicit answer that
     * this should NOT be a public page. Only shows non-sensitive metadata
     * (never field_values) since the person checking may not be the one
     * who issued it.
     */
    public function verifyForm()
    {
        return view('labor.contracts.verify');
    }

    public function verify(Request $request)
    {
        $request->validate(['contract_no' => 'required|string']);

        $contract = ProWorkerContract::with(['team', 'issuer'])
            ->where('contract_no', trim($request->input('contract_no')))
            ->first();

        return view('labor.contracts.verify', [
            'searched' => $request->input('contract_no'),
            'contract' => $contract,
        ]);
    }

    /**
     * Computes the two spelled-out amount fields for every "ค่าบริการ"
     * group on the template from the single number the issuer typed —
     * called right before the fields array reaches ProWorkerContractService,
     * in both store() and update(), so a correction to the amount also
     * regenerates the spelled-out text. Reuses the existing Thai converter
     * (already used elsewhere for financial documents) and the new
     * EnglishBahtHelper built for this feature.
     */
    protected function resolveFeeGroupValues(ProWorkerContractTemplate $template, array $fields): array
    {
        foreach ($this->formFields->feeGroups($template) as $group) {
            if (empty($group['numeralKey'])) {
                continue;
            }
            $amount = $fields[$group['numeralKey']] ?? null;
            if ($amount === null || $amount === '') {
                continue;
            }
            $amount = (float) str_replace(',', '', $amount);

            if (!empty($group['thTextKey'])) {
                $fields[$group['thTextKey']] = \App\Helpers\ThaiBahtHelper::toText($amount);
            }
            if (!empty($group['enTextKey'])) {
                $fields[$group['enTextKey']] = \App\Helpers\EnglishBahtHelper::toText($amount);
            }
        }

        return $fields;
    }
}

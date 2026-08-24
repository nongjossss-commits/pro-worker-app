<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ConfineToLaborModule;
use App\Models\CompanyProfile;
use App\Models\Employer;
use App\Models\LaborTeam;
use App\Models\ProWorkerContract;
use App\Models\ProWorkerContractTemplate;
use App\Services\ProWorkerContractPdfService;
use App\Services\ProWorkerContractService;
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
    public function create()
    {
        $template = ProWorkerContractTemplate::latest()->first();

        return view('labor.contracts.create', [
            'template' => $template,
            'addressGroups' => $template ? $this->addressGroups($template) : [],
            'canLinkEmployer' => $this->canLinkEmployer(Auth::user()),
        ]);
    }

    public function store(Request $request, ProWorkerContractService $service)
    {
        $user = Auth::user();
        abort_unless($user->labor_team_id, 403, __('You have not been assigned to a Pro Walker Labor team yet. Please contact a Super Admin.'));

        $template = ProWorkerContractTemplate::findOrFail($request->input('template_id'));
        $fields = $request->input('fields', []);

        if ($error = $this->validateFields($template, $fields)) {
            return back()->withErrors($error)->withInput();
        }

        [$employerError, $employerId, $employerName] = $this->resolveEmployerLink($request, $user);
        if ($employerError) {
            return back()->withErrors($employerError)->withInput();
        }

        $contract = $service->issue($template, $user, $fields, $employerId, $employerName);

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
        $fields = $request->input('fields', []);

        $pdfBytes = app(ProWorkerContractPdfService::class)->preview($template, $fields);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }

    public function show(ProWorkerContract $contract)
    {
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
        $template = $contract->template;

        return view('labor.contracts.edit', [
            'contract' => $contract,
            'template' => $template,
            'addressGroups' => $this->addressGroups($template),
            'canLinkEmployer' => $this->canLinkEmployer(Auth::user()),
        ]);
    }

    public function update(Request $request, ProWorkerContract $contract, ProWorkerContractService $service)
    {
        $template = $contract->template;
        $fields = $request->input('fields', []);

        if ($error = $this->validateFields($template, $fields)) {
            return back()->withErrors($error)->withInput();
        }

        [$employerError, $employerId, $employerName] = $this->resolveEmployerLink($request, Auth::user());
        if ($employerError) {
            return back()->withErrors($employerError)->withInput();
        }

        $service->update($contract, $fields, $employerId, $employerName);

        return redirect()->route('labor.contracts.show', $contract)
            ->with('success', __('Contract updated successfully.'));
    }

    /**
     * True for anyone who isn't one of ConfineToLaborModule's dedicated
     * external-team roles — those roles ARE the definition of "has no
     * access to the main app's Employer records" (see that middleware's
     * docblock: "super-admin and admin are untouched here"), so reusing
     * that exact list keeps the two rules from ever drifting apart.
     */
    protected function canLinkEmployer($user): bool
    {
        return !$user->hasAnyRole(ConfineToLaborModule::LABOR_ROLES);
    }

    /**
     * Resolves the employer-link fields submitted alongside the template's
     * own fields[] — deliberately a SEPARATE top-level request input
     * (`employer_id`/`employer_name`), never mixed into fields[]/
     * field_values, so it can never collide with or be mistaken for a
     * template-defined field (see ProWorkerContract's docblock on why this
     * is kept independent). Returns [errorArrayOrNull, employerId,
     * employerNameSnapshot].
     *
     * A submitted employer_id is only ever trusted when the user actually
     * has Employer-picker access (canLinkEmployer()) — even if a confined
     * external-team user somehow POSTs one, it's ignored, and the name is
     * always re-derived from the real Employer record rather than trusting
     * client-submitted text, so the two can never mismatch.
     */
    protected function resolveEmployerLink(Request $request, $user): array
    {
        if ($this->canLinkEmployer($user)) {
            $employerId = $request->input('employer_id');
            if (!$employerId) {
                return [['employer' => __('Please select an employer.')], null, null];
            }

            $employer = Employer::find($employerId);
            if (!$employer) {
                return [['employer' => __('Please select an employer.')], null, null];
            }

            $name = trim((string) ($employer->employerNameTh ?: $employer->employerNameEn));

            return [null, (int) $employer->id, $name];
        }

        $name = trim((string) $request->input('employer_name'));
        if ($name === '') {
            return [['employer' => __('Please enter the employer name.')], null, null];
        }

        return [null, null, $name];
    }

    /**
     * Only text/address/worker_count fields are ever rendered as inputs
     * on the issuance/edit form (see create.blade.php/edit.blade.php) —
     * image/stamp/signature/mark fields are fixed at template-build time
     * and carry no per-issuance value, so they're excluded from this
     * required check. Shared by store() and update() so both stay in sync.
     */
    protected function validateFields(ProWorkerContractTemplate $template, array $fields): ?array
    {
        $requiredKeys = collect($template->field_mapping ?? [])
            ->whereIn('type', ['text', 'worker_count', 'address_th', 'address_en'])
            ->pluck('key');

        foreach ($requiredKeys as $key) {
            if (!isset($fields[$key]) || $fields[$key] === '') {
                return ['fields' => __('Please fill in every field.')];
            }
        }

        // A Thai Soi/Road with no English counterpart would otherwise
        // leave that detail silently missing from the composed English
        // address (see _address_group.blade.php/proworker-address-picker.js
        // — a blank EN part is OMITTED, never falls back to untranslated
        // Thai text). The picker already makes the EN input required
        // client-side once its Thai counterpart is typed; this repeats the
        // same rule server-side in case JS is bypassed.
        foreach (array_keys($this->addressGroups($template)) as $groupId) {
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
     * Group a template's field_mapping into distinct address blocks
     * (paired address_th/address_en items sharing an addressGroup) plus
     * the remaining plain text fields — used by create.blade.php to render
     * one _address_group partial per block and a plain input per text field.
     */
    protected function addressGroups(ProWorkerContractTemplate $template): array
    {
        $groups = [];
        foreach (($template->field_mapping ?? []) as $item) {
            if (($item['type'] ?? null) === 'address_th' && !empty($item['addressGroup'])) {
                $groups[$item['addressGroup']]['keyTh'] = $item['key'];
                $groups[$item['addressGroup']]['labelTh'] = $item['label'];
            } elseif (($item['type'] ?? null) === 'address_en' && !empty($item['addressGroup'])) {
                $groups[$item['addressGroup']]['keyEn'] = $item['key'];
                $groups[$item['addressGroup']]['labelEn'] = $item['label'];
            }
        }

        return $groups;
    }
}

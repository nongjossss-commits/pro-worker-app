<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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

    /**
     * Editing (correcting) a contract's DATA is deliberately narrower than
     * viewing it — only the original issuer may ever save changes, with no
     * role exception at all (not even super-admin or a team lead — they
     * can still open/view via assertCanAccessContract() above, just never
     * save). Attaching the signed-copy scan back onto the record is a
     * SEPARATE, looser action (see uploadSignedCopy()) that anyone who can
     * view the contract may do — only correcting the issuance data itself
     * is issuer-only.
     */
    protected function assertCanEditContract(ProWorkerContract $contract): void
    {
        abort_if($contract->issued_by !== Auth::id(), 403);
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
        $this->assertCanEditContract($contract);

        $template = $contract->template;

        return view('labor.contracts.edit', [
            'contract' => $contract,
            'template' => $template,
            'formItems' => $this->formFields->unifiedItems($template),
        ]);
    }

    public function update(Request $request, ProWorkerContract $contract, ProWorkerContractService $service)
    {
        $this->assertCanEditContract($contract);

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
     * Attaches the scanned/photographed copy of the contract the employer
     * actually signed — usually done well after issuance, once it's been
     * handed over, signed, and brought back. Gated by
     * assertCanAccessContract() (same viewers as show()), NOT
     * assertCanEditContract() — deliberately looser than correcting the
     * contract's data, since anyone on the team who can see the contract
     * may help attach the signed copy, not just the original issuer (see
     * assertCanEditContract()'s docblock). Presence of signed_copy_path is
     * what the "สัญญาสมบูรณ์" (Complete Contract) badge/filter checks —
     * this write is auto-logged by ProWorkerContract's LogActivity trait,
     * same as any other correction.
     */
    public function uploadSignedCopy(Request $request, ProWorkerContract $contract)
    {
        $this->assertCanAccessContract($contract);

        $request->validate([
            // Mirrors DelegateController::DELEGATE_RULES' 30MB cap
            // (max is in KB) and LaborCompanyDocumentController's mime
            // list — the scanner produces images, but a already-scanned
            // PDF can also be uploaded directly without going through it.
            'signed_copy' => 'required|mimes:jpeg,jpg,png,pdf|max:30720',
        ]);

        if ($contract->signed_copy_path && Storage::disk('public')->exists($contract->signed_copy_path)) {
            Storage::disk('public')->delete($contract->signed_copy_path);
        }

        $path = $request->file('signed_copy')->store('proworker_contracts/signed_copies', 'public');
        $contract->update(['signed_copy_path' => $path]);

        // back() rather than a hardcoded route so this same form works both
        // from show.blade.php (its original home) and the inline per-row
        // copy on index.blade.php (added later) — each just lands back
        // wherever it was submitted from.
        return redirect()->back()
            ->with('success', __('Signed copy attached successfully.'));
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

    /**
     * "ดูประวัติการแก้ไข" — every create/update ActivityLog entry for this
     * contract (see ProWorkerContract's `use LogActivity`), rendered as
     * plain, immediately-readable Thai sentences via
     * describeContractChanges() below, newest first. Gated the same as
     * show() — whoever can view the contract (accounting, Super Admin, the
     * issuer themselves) can see its history; editing stays issuer-only
     * regardless (assertCanEditContract()).
     */
    public function history(ProWorkerContract $contract)
    {
        $this->assertCanAccessContract($contract);

        $logs = ActivityLog::where('subject_type', ProWorkerContract::class)
            ->where('subject_id', $contract->id)
            ->with('user')
            ->latest()
            ->get()
            ->map(fn (ActivityLog $log) => [
                'log' => $log,
                'changes' => $this->describeContractChanges($contract, $log),
            ])
            // A log entry with zero readable changes (e.g. only
            // updated_at moved, already filtered out below) would just be
            // visual noise — skip it.
            ->filter(fn (array $entry) => !empty($entry['changes']))
            ->values();

        $editCount = $logs->filter(fn (array $entry) => $entry['log']->action === 'update')->count();

        return view('labor.contracts.history', compact('contract', 'logs', 'editCount'));
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

        // "สัญญาสมบูรณ์" (has the employer's signed copy attached) vs. not
        // — see uploadSignedCopy(). Applies on top of the scoping above,
        // same as the search/team filters.
        if ($request->input('status') === 'complete') {
            $query->whereNotNull('signed_copy_path');
        } elseif ($request->input('status') === 'incomplete') {
            $query->whereNull('signed_copy_path');
        }

        $contracts = $query->paginate(20)->withQueryString();
        $teams = $seesAllTeams ? LaborTeam::orderBy('name')->get(['id', 'name']) : collect();
        $summary = $this->buildCompletionSummary($user, $seesAllTeams);

        return view('labor.contracts.index', compact('contracts', 'teams', 'seesAllTeams', 'summary'));
    }

    /**
     * Bulk-download the tick-selected contracts from the list page as one
     * .zip — `variant=original` zips each contract's own generated PDF
     * (`file_path`, always present since issue()); `variant=signed` zips
     * only the employer-signed scan (`signed_copy_path`), silently skipping
     * any selected contract that doesn't have one yet (counted and
     * reported back in the flash message rather than failing the whole
     * download). Deliberately synchronous (build the zip, stream it back
     * immediately) rather than the Employee module's async
     * DownloadTask/ProcessDownload Job+polling system — that system is
     * built around many file TYPES + stamping per employee and would need
     * either duplicating or awkwardly generalizing; a contract is always
     * exactly one small PDF, so a direct zip is simpler and safer here
     * without touching that Employee-only code at all.
     *
     * Re-runs index()'s own exact visibility scope against the submitted
     * ids (never trusts the client's checkbox selection alone) — a
     * $seesAllTeams role may bulk-download any contract, everyone else
     * only their own issuances, identical to what index() lets them see
     * and select in the first place.
     */
    public function bulkDownload(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:pro_worker_contracts,id',
            'variant' => 'required|in:original,signed',
        ]);

        $user = Auth::user();
        $seesAllTeams = $user->hasAnyRole(['super-admin', 'admin', 'labor-accounting', 'labor-shareholder']);

        $query = ProWorkerContract::whereIn('id', $request->input('ids'));
        if (!$seesAllTeams) {
            $query->where('issued_by', $user->id);
        }

        $variant = $request->input('variant');
        if ($variant === 'signed') {
            $query->whereNotNull('signed_copy_path');
        }

        $contracts = $query->get();

        if ($contracts->isEmpty()) {
            // A real redirect (not the download response below), so this
            // actually reaches labor.layout's existing $errors->any()
            // block — a response()->download() never navigates the
            // browser, so anything flashed alongside IT would go unseen
            // until some unrelated later page load. The "some selected
            // rows were skipped" case (partial success) is instead warned
            // about client-side in the bulk-download modal BEFORE
            // submitting, precisely to avoid that same invisible-flash
            // problem for a response that never becomes a page load.
            $message = $variant === 'signed'
                ? __('None of the selected contracts have a signed copy attached yet.')
                : __('No accessible contracts were found among the ones selected.');
            return back()->withErrors(['bulk_download' => $message]);
        }

        $zipFileName = 'pro_worker_contracts_' . $variant . '_' . now()->format('Ymd_His') . '.zip';
        $zipPath = tempnam(sys_get_temp_dir(), 'pwc_zip_') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            return back()->with('danger', __('Could not create the zip file.'));
        }

        $missingFileCount = 0;
        $usedNames = [];

        foreach ($contracts as $contract) {
            $sourcePath = $variant === 'signed' ? $contract->signed_copy_path : $contract->file_path;

            if (!$sourcePath || !Storage::disk('public')->exists($sourcePath)) {
                $missingFileCount++;
                continue;
            }

            // {sanitized employer name}_{contract no}.pdf — contract_no
            // alone is already unique/filesystem-safe, the employer name
            // is just there to make the zip's file listing scannable at a
            // glance; falls back to contract_no alone when there's no
            // employer name snapshot. Same sanitizing regex as
            // ProcessDownload::sanitizeFileName() (no shared helper for
            // this exists in the codebase yet).
            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'pdf';
            $employerPart = trim((string) $contract->employer_name_snapshot);
            $baseName = $employerPart !== ''
                ? preg_replace('/[^a-zA-Z0-9\-\_\p{Thai}]/u', '_', $employerPart) . '_' . $contract->contract_no
                : $contract->contract_no;
            $entryName = $baseName . '.' . $extension;

            // Guard against two contracts sanitizing to the same name
            // (e.g. same employer, and somehow the same contract_no can't
            // happen since it's unique — but stay defensive regardless).
            if (isset($usedNames[$entryName])) {
                $usedNames[$entryName]++;
                $entryName = $baseName . '_' . $usedNames[$entryName] . '.' . $extension;
            } else {
                $usedNames[$entryName] = 1;
            }

            $zip->addFile(Storage::disk('public')->path($sourcePath), $entryName);
        }

        $zip->close();

        // $missingFileCount (the file row exists in the DB but the actual
        // file is gone from storage) isn't surfaced anywhere further —
        // it's the same silent-skip behavior download()/view() already
        // have for a single contract's missing file, just applied per-item
        // here instead of a hard 404, so one bad file doesn't block the
        // rest of the batch.
        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Total/complete/incomplete counts for the summary cards above the
     * list — strictly bounded to each viewer's own existing access tier,
     * never wider (per the user's explicit requirement — "จะไม่มีใครมองเห็น
     * ยอดของคนอื่นหรือมากกว่าสิทธิ์ที่ตัวเองได้มอบหมาย"):
     *   - $seesAllTeams roles (super-admin/admin/labor-accounting/
     *     labor-shareholder): one row PER TEAM, every team.
     *   - labor-team (a team lead): one row for their WHOLE team — wider
     *     than the individual contract rows the list itself still shows
     *     them (scoped to issued_by = own id, unchanged) — deliberately,
     *     per the user's explicit "หัวหน้าทีมเห็นยอดรวมทั้งทีม" instruction.
     *   - everyone else (e.g. labor-member): their own issuances only,
     *     matching the list's existing issued_by scope exactly.
     */
    protected function buildCompletionSummary($user, bool $seesAllTeams): array
    {
        $counts = fn ($query) => $query
            ->selectRaw('count(*) as total, sum(case when signed_copy_path is not null then 1 else 0 end) as complete')
            ->first();

        if ($seesAllTeams) {
            $rows = ProWorkerContract::query()
                ->selectRaw('labor_team_id, count(*) as total, sum(case when signed_copy_path is not null then 1 else 0 end) as complete')
                ->groupBy('labor_team_id')
                ->get()
                ->keyBy('labor_team_id');

            $teams = LaborTeam::orderBy('name')->get(['id', 'name']);

            return [
                'scope' => 'all_teams',
                'rows' => $teams->map(function ($team) use ($rows) {
                    $row = $rows->get($team->id);
                    return [
                        'label' => $team->name,
                        'total' => (int) ($row->total ?? 0),
                        'complete' => (int) ($row->complete ?? 0),
                    ];
                })->values(),
            ];
        }

        if ($user->hasRole('labor-team') && $user->labor_team_id) {
            $row = $counts(ProWorkerContract::where('labor_team_id', $user->labor_team_id));

            return [
                'scope' => 'own_team',
                'rows' => [[
                    'label' => $user->laborTeam->name ?? __('Your Team'),
                    'total' => (int) ($row->total ?? 0),
                    'complete' => (int) ($row->complete ?? 0),
                ]],
            ];
        }

        $row = $counts(ProWorkerContract::where('issued_by', $user->id));

        return [
            'scope' => 'own',
            'rows' => [[
                'label' => __('You'),
                'total' => (int) ($row->total ?? 0),
                'complete' => (int) ($row->complete ?? 0),
            ]],
        ];
    }

    /**
     * Public, no-login QR-code target (see routes/labor.php's unauthenticated
     * route group) — confirms a contract number is genuine and shows enough
     * to cross-check against the physical document someone scanned:
     * employer name, the issuing team, and the issuing company — so a
     * forged document can't just reuse a real contract number with fake
     * details printed on it and still pass a scan. Deliberately never
     * exposes field_values or the issuing staff member's own name, per the
     * confidentiality requirement between contracting parties.
     */
    public function publicVerify(string $contractNo)
    {
        $contract = ProWorkerContract::with(['team', 'template'])->where('contract_no', trim($contractNo))->first();
        $companyProfile = CompanyProfile::where('is_default', true)->first() ?? CompanyProfile::first();

        // Only the fields a Super Admin explicitly opted into via the
        // Template Builder's "Show on the public verification page"
        // toggle — see ProWorkerFormFieldsResolver::verifyVisibleItems().
        // $contract->template can be null (the template was deleted after
        // this contract was issued — templates aren't soft-deleted), in
        // which case there's nothing to resolve labels/values against.
        $verifyItems = ($contract && $contract->template)
            ? $this->formFields->verifyVisibleItems($contract->template, $contract->field_values ?? [])
            : [];

        return view('labor.contracts.public_verify', [
            'contract' => $contract,
            'companyProfile' => $companyProfile,
            'verifyItems' => $verifyItems,
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
    /**
     * Turns one ActivityLog entry's raw create/update diff into a list of
     * plain, immediately-readable Thai sentences — deliberately NOT
     * reusing ActivityLogHelper::generateReadableChanges() (used by every
     * other model's history), since that method would dump `field_values`
     * (one JSON blob holding every issuance-form answer) as raw pretty-
     * printed JSON whenever it changes — unreadable at a glance, and
     * defeats the whole point of this screen. Every OTHER column here is a
     * plain scalar, so those are described directly without needing
     * ActivityLogHelper at all. ActivityLogHelper/LogActivity/ActivityLog
     * themselves are left untouched — they're shared with Employee/
     * Employer/User/etc., so any change there risks all of them.
     */
    protected function describeContractChanges(ProWorkerContract $contract, ActivityLog $log): array
    {
        if ($log->action === 'create') {
            return [__('Contract created (first issuance).')];
        }

        if ($log->action !== 'update') {
            return [];
        }

        $properties = $log->properties ?? [];
        $old = $properties['old'] ?? [];
        $new = $properties['attributes'] ?? [];
        $changes = [];

        // Local to this method — specific to ProWorkerContract, not
        // shared with ActivityLogHelper::FIELD_LABELS (which serves
        // several other models).
        $labels = [
            'file_path' => __('Document File'),
            'signed_copy_path' => __('Signed Copy Attachment'),
            'worker_count' => __('Worker Count'),
            'employer_name_snapshot' => __('Employer Name'),
            'employer_id' => __('Employer'),
        ];
        // Storage-path values are never shown raw — just note a file was
        // attached/replaced.
        $pathFields = ['file_path', 'signed_copy_path'];

        foreach ($new as $key => $newVal) {
            if ($key === 'field_values') {
                continue; // handled separately below — needs a per-key diff, not a scalar compare
            }
            $oldVal = $old[$key] ?? null;
            if ($oldVal == $newVal) {
                continue;
            }
            $label = $labels[$key] ?? $key;
            if (in_array($key, $pathFields, true)) {
                $changes[] = $oldVal
                    ? __(':label: a new file replaced the previous one.', ['label' => $label])
                    : __(':label: a new file was attached.', ['label' => $label]);
                continue;
            }
            $oldText = ($oldVal === null || $oldVal === '') ? '-' : $oldVal;
            $newText = ($newVal === null || $newVal === '') ? '-' : $newVal;
            $changes[] = "เปลี่ยน \"{$label}\" จาก \"{$oldText}\" เป็น \"{$newText}\"";
        }

        // field_values holds every issuance-form answer as one JSON blob —
        // diff it key-by-key and translate each key to the SAME label
        // already shown on the issuance form/Template Builder (via
        // contractFieldValueLabels() below) instead of the raw storage
        // key (e.g. "field_mt5i3f14mgsv").
        if (array_key_exists('field_values', $new)) {
            $oldFields = $this->decodeFieldValuesSnapshot($old['field_values'] ?? null);
            $newFields = $this->decodeFieldValuesSnapshot($new['field_values'] ?? null);
            $fieldLabels = $contract->template ? $this->contractFieldValueLabels($contract->template) : [];

            foreach (array_unique(array_merge(array_keys($oldFields), array_keys($newFields))) as $key) {
                $oldVal = $oldFields[$key] ?? '';
                $newVal = $newFields[$key] ?? '';
                if ($oldVal == $newVal) {
                    continue;
                }
                $label = $fieldLabels[$key] ?? $key;
                $oldText = ($oldVal === null || $oldVal === '') ? '-' : $oldVal;
                $newText = ($newVal === null || $newVal === '') ? '-' : $newVal;
                $changes[] = "เปลี่ยน \"{$label}\" จาก \"{$oldText}\" เป็น \"{$newText}\"";
            }
        }

        return $changes;
    }

    /**
     * LogActivity stores field_values (a JSON-cast column) as whatever
     * getAttributes()/getOriginal() hand back for it — which is the raw
     * JSON-encoded STRING Eloquent keeps internally for json-cast columns,
     * not the already-decoded array. Handles both shapes defensively
     * since that internal detail isn't guaranteed API.
     */
    protected function decodeFieldValuesSnapshot($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode((string) $value, true) ?: [];
    }

    /**
     * Maps every field_values key on a template to the SAME human label
     * already shown on the issuance form / Template Builder (via
     * ProWorkerFormFieldsResolver::unifiedItems()) — so
     * describeContractChanges() names a field_values change exactly the
     * way a Super Admin already recognizes it.
     */
    protected function contractFieldValueLabels(ProWorkerContractTemplate $template): array
    {
        $labels = [];

        foreach ($this->formFields->unifiedItems($template) as $item) {
            switch ($item['kind']) {
                case 'text':
                case 'worker_count':
                    $labels[$item['key']] = $item['label'] ?? $item['key'];
                    break;
                case 'address':
                    if (!empty($item['keyTh'])) {
                        $labels[$item['keyTh']] = ($item['labelTh'] ?? __('Address')) . ' (' . __('Thai') . ')';
                    }
                    if (!empty($item['keyEn'])) {
                        $labels[$item['keyEn']] = ($item['labelEn'] ?? __('Address')) . ' (' . __('English') . ')';
                    }
                    break;
                case 'business_type':
                    if (!empty($item['keyTh'])) {
                        $labels[$item['keyTh']] = ($item['labelTh'] ?? __('Business Type')) . ' (' . __('Thai') . ')';
                    }
                    if (!empty($item['keyEn'])) {
                        $labels[$item['keyEn']] = ($item['labelEn'] ?? __('Business Type')) . ' (' . __('English') . ')';
                    }
                    break;
                case 'nationality':
                    if (!empty($item['keyTh'])) {
                        $labels[$item['keyTh']] = ($item['labelTh'] ?? __('Nationality')) . ' (' . __('Thai') . ')';
                    }
                    if (!empty($item['keyEn'])) {
                        $labels[$item['keyEn']] = ($item['labelEn'] ?? __('Nationality')) . ' (' . __('English') . ')';
                    }
                    break;
                case 'fee':
                    if (!empty($item['numeralKey'])) {
                        $labels[$item['numeralKey']] = $item['label'] ?? __('Service Fee');
                    }
                    if (!empty($item['thTextKey'])) {
                        $labels[$item['thTextKey']] = ($item['label'] ?? __('Service Fee')) . ' (' . __('Thai spelled-out') . ')';
                    }
                    if (!empty($item['enTextKey'])) {
                        $labels[$item['enTextKey']] = ($item['label'] ?? __('Service Fee')) . ' (' . __('English spelled-out') . ')';
                    }
                    break;
            }
        }

        return $labels;
    }

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

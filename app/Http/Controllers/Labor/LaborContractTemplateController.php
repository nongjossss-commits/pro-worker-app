<?php

namespace App\Http\Controllers\Labor;

use App\Helpers\PdfHelper;
use App\Http\Controllers\Controller;
use App\Models\ProWorkerContractTemplate;
use App\Services\PdfGeneratorService;
use App\Services\ProWorkerFormFieldsResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Templates for the Pro Worker <-> Employer contract — deliberately separate
 * from Admin\PdfTemplateController (Employee/Employer data-bound templates
 * used elsewhere in the app). Every field here (except the fixed address
 * pair) is a free-form label the admin invents when building the template;
 * see builder.blade.php in resources/views/labor/contract_templates/.
 * Super-Admin-only (see routes/labor.php), same tier as charge-types/
 * expense-categories/users.
 */
class LaborContractTemplateController extends Controller
{
    public function index()
    {
        $templates = ProWorkerContractTemplate::latest()->paginate(15);
        return view('labor.contract_templates.index', compact('templates'));
    }

    /**
     * The blank, bilingual (Thai/English) master source document for the
     * standard contract — an HTML page (not a server-generated PDF; see
     * resources/views/labor/contract_templates/master_template.blade.php's
     * docblock for why) that the admin views and prints/"Save as PDF" from
     * their own browser, then re-uploads here as a real Contract Template.
     * Admin OR Super Admin only (see routes/labor.php — deliberately NOT
     * nested inside the role:super-admin-only group above).
     */
    public function masterTemplate()
    {
        return view('labor.contract_templates.master_template');
    }

    public function create()
    {
        return view('labor.contract_templates.create');
    }

    public function store(Request $request, PdfGeneratorService $pdfService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|mimes:pdf|max:10240',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $isCompatible = false;
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pdf->setSourceFile($filePath);
            $isCompatible = true;
        } catch (\Exception $e) {
            $isCompatible = false;
        }

        if (!$isCompatible) {
            try {
                $normalizedPath = $pdfService->tryNormalizePdf($filePath);
                if (!$normalizedPath || !file_exists($normalizedPath)) {
                    throw new \Exception('Normalization process failed to produce a valid file.');
                }

                $storePath = 'proworker_contract_templates/' . $file->hashName();
                Storage::disk('public')->put($storePath, file_get_contents($normalizedPath));
                @unlink($normalizedPath);
                $finalPath = $storePath;
            } catch (\Exception $e) {
                $version = PdfHelper::getVersion($filePath) ?? 'Unknown';
                return back()->withErrors([
                    'file' => "The uploaded PDF is version {$version}. The system attempted to repair it but failed: " . $e->getMessage(),
                ])->withInput();
            }
        } else {
            $finalPath = $file->store('proworker_contract_templates', 'public');
        }

        $template = ProWorkerContractTemplate::create([
            'name' => $request->name,
            'file_path' => $finalPath,
            'created_by' => Auth::id(),
            'field_mapping' => [],
            'meta_data' => [
                'original_filename' => $file->getClientOriginalName(),
            ],
        ]);

        return redirect()->route('labor.contract-templates.builder', $template)
            ->with('success', __('Template uploaded. Please configure fields.'));
    }

    public function builder(ProWorkerContractTemplate $proworkerContractTemplate)
    {
        return view('labor.contract_templates.builder', ['template' => $proworkerContractTemplate]);
    }

    public function update(Request $request, ProWorkerContractTemplate $proworkerContractTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'field_mapping' => 'nullable|array',
            'meta_data' => 'nullable|array',
        ]);

        $proworkerContractTemplate->update([
            'name' => $request->name,
            'field_mapping' => $request->field_mapping ?? [],
            'meta_data' => $request->meta_data ?? [],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Uploads an image for an image/stamp/signature field in the builder —
     * mirrors Admin\PdfTemplateController::uploadImage() (the main PDF
     * Template Builder's identical helper), kept as its own copy since this
     * is a deliberately separate system. `kind` is only used to pick a
     * subfolder for tidiness — the field's `type` on the frontend is what
     * actually controls how it renders in the generated contract.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'kind' => 'nullable|in:image,stamp,signature',
        ]);

        $folder = match ($request->input('kind')) {
            'stamp' => 'proworker_contract_templates/stamps',
            'signature' => 'proworker_contract_templates/signatures',
            default => 'proworker_contract_templates/images',
        };

        $path = $request->file('image')->store($folder, 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function file(Request $request, ProWorkerContractTemplate $proworkerContractTemplate)
    {
        $path = $proworkerContractTemplate->file_path;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }

    public function destroy(ProWorkerContractTemplate $proworkerContractTemplate)
    {
        $proworkerContractTemplate->delete();

        return redirect()->route('labor.contract-templates.index')
            ->with('success', __('Template deleted successfully.'));
    }

    /**
     * "จัดลำดับฟอร์ม" — lets a Super Admin freely reorder the sequence the
     * issuance form (labor/contracts/_fields.blade.php) asks for fields in,
     * completely independent of each field's physical page/x/y position on
     * the PDF canvas (that stays the builder's job). See
     * ProWorkerFormFieldsResolver::unifiedItems() for the formOrder concept
     * this reads/writes.
     */
    public function formOrder(ProWorkerContractTemplate $proworkerContractTemplate, ProWorkerFormFieldsResolver $formFields)
    {
        // `displayLabel` is whichever label the issuance form actually
        // shows for this row — text/worker_count/fee use `label`, while
        // address/business_type/nationality groups use `labelTh` (see
        // _fields.blade.php) — computed once here so the view/JS below
        // doesn't need to know that distinction itself.
        $items = collect($formFields->unifiedItems($proworkerContractTemplate))
            ->map(function ($item) {
                $item['displayLabel'] = in_array($item['kind'], ['address', 'business_type', 'nationality'], true)
                    ? ($item['labelTh'] ?? '')
                    : ($item['label'] ?? '');

                return $item;
            })
            ->values();

        return view('labor.contract_templates.form_order', [
            'template' => $proworkerContractTemplate,
            'items' => $items,
        ]);
    }

    /**
     * Writes the new order, width, and optionally a new display label —
     * this is also where the "two Service Fee groups showed an identical
     * label" bug gets fixed, since a Super Admin can now name each group
     * here — back onto the SAME field_mapping the builder manages. Only
     * `formOrder`/`formWidth`/`label` of the matching item(s) are touched;
     * every other property (x/y/w/h/page/fontSize/align/etc.) is left
     * exactly as the builder set it.
     */
    public function updateFormOrder(Request $request, ProWorkerContractTemplate $proworkerContractTemplate)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.kind' => 'required|string',
            'order.*.key' => 'nullable|string',
            'order.*.groupId' => 'nullable|string',
            'order.*.label' => 'nullable|string',
            'order.*.width' => 'nullable|integer|min:1|max:12',
        ]);

        // Only the ONE item type actually read as a group's display label
        // by ProWorkerFormFieldsResolver's groupX() methods gets the new
        // label — e.g. an address group's paired English-position item
        // keeps its own internal-only label (shown in the builder's
        // "Placed Fields" sidebar, never on the issuance form).
        $labelableTypes = ['text', 'worker_count', 'address_th', 'business_type_th', 'nationality_th', 'fee_number'];

        $mapping = $proworkerContractTemplate->field_mapping ?? [];

        foreach ($request->input('order') as $sequence => $row) {
            $kind = $row['kind'];
            $key = $row['key'] ?? null;
            $groupId = $row['groupId'] ?? null;
            $label = $row['label'] ?? null;
            $width = $row['width'] ?? 12;

            foreach ($mapping as &$item) {
                $belongsToRow = match ($kind) {
                    'text', 'worker_count' => ($item['key'] ?? null) === $key,
                    'address' => ($item['addressGroup'] ?? null) === $groupId,
                    'business_type' => ($item['businessTypeGroup'] ?? null) === $groupId,
                    'nationality' => ($item['nationalityGroup'] ?? null) === $groupId,
                    'fee' => ($item['feeGroup'] ?? null) === $groupId,
                    default => false,
                };

                if (!$belongsToRow) {
                    continue;
                }

                $item['formOrder'] = $sequence;

                // Unlike `label` (only meaningful on ONE representative
                // item type per group — see $labelableTypes), `formWidth`
                // is written to EVERY item sharing this key/group, since
                // ProWorkerFormFieldsResolver's groupX() methods read it
                // via `??=` from whichever item is encountered first —
                // that must resolve to the same value no matter which one
                // that happens to be.
                $item['formWidth'] = $width ?: 12;

                if ($label !== null && $label !== '' && in_array($item['type'] ?? null, $labelableTypes, true)) {
                    $item['label'] = $label;
                }
            }
            unset($item);
        }

        $proworkerContractTemplate->update(['field_mapping' => $mapping]);

        return response()->json(['success' => true]);
    }

    /**
     * Export the selected templates' field settings as one downloadable
     * JSON file — same purpose/shape as Admin\PdfTemplateController::export()
     * (see its docblock), kept as its own copy since this is a deliberately
     * separate system. This system has no employer_id/type/witness FK to
     * worry about (see the class docblock — every field is a free-form
     * label, not data-bound), only the `image`/`stamp`/`signature` field
     * types carry a stored picture that's specific to this install.
     */
    public function export(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:pro_worker_contract_templates,id',
        ]);

        $payload = [
            'app' => 'pro-worker-v2',
            'export_type' => 'pro_worker_contract_templates',
            'exported_at' => now()->toIso8601String(),
            'templates' => [],
        ];

        foreach (ProWorkerContractTemplate::whereIn('id', $request->input('ids'))->get() as $template) {
            if (!Storage::disk('public')->exists($template->file_path)) {
                continue; // nothing usable to export without the background file
            }

            $fieldMapping = is_array($template->field_mapping) ? $template->field_mapping : [];
            $imageFieldsNeedReupload = [];
            foreach ($fieldMapping as &$item) {
                if (in_array($item['type'] ?? null, ['image', 'stamp', 'signature'], true)) {
                    $imageFieldsNeedReupload[] = $item['label'] ?? ($item['key'] ?? __('Image field'));
                    unset($item['path'], $item['url']);
                }
            }
            unset($item);

            $metaData = is_array($template->meta_data) ? $template->meta_data : [];

            $payload['templates'][] = [
                'name' => $template->name,
                'field_mapping' => $fieldMapping,
                'meta_data' => $metaData,
                'file_original_name' => $metaData['original_filename'] ?? ($template->name . '.pdf'),
                'file_base64' => base64_encode(Storage::disk('public')->get($template->file_path)),
                'image_fields_need_reupload' => $imageFieldsNeedReupload,
            ];
        }

        if (empty($payload['templates'])) {
            return back()->with('danger', __('No templates could be exported (the background file is gone).'));
        }

        $filename = 'contract_templates_export_' . now()->format('Ymd_His') . '.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * Import a JSON file produced by export() above — see
     * Admin\PdfTemplateController::import()'s docblock for the shared
     * behavior (always creates new rows, skips a name that already exists).
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:json,txt|max:51200',
        ]);

        $data = json_decode(file_get_contents($request->file('file')->getRealPath()), true);

        if (!is_array($data) || ($data['export_type'] ?? null) !== 'pro_worker_contract_templates' || !is_array($data['templates'] ?? null)) {
            return back()->withErrors(['file' => __('This is not a valid Contract Templates export file.')]);
        }

        $imported = 0;
        $skipped = [];
        $imageWarnings = [];

        DB::transaction(function () use ($data, &$imported, &$skipped, &$imageWarnings) {
            foreach ($data['templates'] as $item) {
                $name = trim($item['name'] ?? '');
                if ($name === '' || empty($item['file_base64'])) {
                    continue;
                }

                if (ProWorkerContractTemplate::where('name', $name)->exists()) {
                    $skipped[] = $name;
                    continue;
                }

                $bytes = base64_decode($item['file_base64'], true);
                if ($bytes === false) {
                    $skipped[] = $name . ' (' . __('corrupted file') . ')';
                    continue;
                }

                $storePath = 'proworker_contract_templates/' . Str::random(40) . '.pdf';
                Storage::disk('public')->put($storePath, $bytes);

                $metaData = is_array($item['meta_data'] ?? null) ? $item['meta_data'] : [];
                $metaData['original_filename'] = $item['file_original_name'] ?? ($name . '.pdf');
                $metaData['imported_at'] = now()->toIso8601String();

                ProWorkerContractTemplate::create([
                    'name' => $name,
                    'file_path' => $storePath,
                    'created_by' => Auth::id(),
                    'field_mapping' => is_array($item['field_mapping'] ?? null) ? $item['field_mapping'] : [],
                    'meta_data' => $metaData,
                ]);

                $imported++;
                if (!empty($item['image_fields_need_reupload'])) {
                    $imageWarnings[$name] = $item['image_fields_need_reupload'];
                }
            }
        });

        $message = __(':count template(s) imported successfully.', ['count' => $imported]);
        if (!empty($skipped)) {
            $message .= ' ' . __('Skipped (name already exists or file corrupted): :names', ['names' => implode(', ', $skipped)]);
        }
        if (!empty($imageWarnings)) {
            $notes = [];
            foreach ($imageWarnings as $tplName => $fields) {
                $notes[] = "\"{$tplName}\": " . implode(', ', $fields);
            }
            $message .= ' ' . __('These templates have image fields that need to be re-uploaded manually: :notes', ['notes' => implode(' | ', $notes)]);
        }

        return redirect()->route('labor.contract-templates.index')->with('success', $message);
    }
}

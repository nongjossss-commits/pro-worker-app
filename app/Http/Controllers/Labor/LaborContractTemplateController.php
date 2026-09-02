<?php

namespace App\Http\Controllers\Labor;

use App\Helpers\PdfHelper;
use App\Http\Controllers\Controller;
use App\Models\ProWorkerContractTemplate;
use App\Services\PdfGeneratorService;
use App\Services\ProWorkerFormFieldsResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
}

<?php

namespace App\Http\Controllers\Labor;

use App\Helpers\PdfHelper;
use App\Http\Controllers\Controller;
use App\Models\ProWorkerContractTemplate;
use App\Services\PdfGeneratorService;
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
}

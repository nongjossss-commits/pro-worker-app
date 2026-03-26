<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\PdfTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Helpers\PdfHelper;
use App\Services\PdfGeneratorService;

class PdfTemplateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-pdf-templates');

        $query = PdfTemplate::query();

        $user = Auth::user();

        // Admin/Staff/SuperAdmin filtering logic
        if ($user->hasRole('super-admin') || $user->hasRole('admin') || $user->hasRole('staff')) {
            if ($request->filled('employer_id')) {
                if ($request->employer_id === 'global') {
                    $query->where('type', 'global');
                } else {
                    $query->where('employer_id', $request->employer_id);
                }
            }
        }
        // Employer Role Logic
        elseif ($user->hasRole('employer')) {
            $query->where(function($q) use ($user) {
                $q->where('type', 'global')
                  ->orWhere('employer_id', $user->employer->id);
            });
            // Employer can also filter their own view if they want, but usually they just see theirs + global.
            // If they select 'global' filter:
            if ($request->employer_id === 'global') {
                 $query->where('type', 'global');
            } elseif ($request->employer_id === 'my_org') {
                 $query->where('employer_id', $user->employer->id);
            }
        }
        // Caretaker Role Logic
        elseif ($user->hasRole('caretaker')) {
             $query->where(function($q) use ($user) {
                 $q->where('type', 'global')
                   ->orWhereIn('employer_id', Employer::where('assigned_staff_id', $user->id)->pluck('id'));
             });

            if ($request->filled('employer_id')) {
                if ($request->employer_id === 'global') {
                    $query->where('type', 'global');
                } else {
                    // verify it's an allowed employer
                    $allowed = Employer::where('assigned_staff_id', $user->id)->where('id', $request->employer_id)->exists();
                    if ($allowed) {
                        $query->where('employer_id', $request->employer_id);
                    }
                }
            }
        }

        $templates = $query->latest()->paginate(10)->withQueryString();

        // Prepare employers list for filter dropdown (if admin/staff)
        $employers = [];
        if ($user->hasRole('super-admin') || $user->hasRole('admin') || $user->hasRole('staff') || $user->hasRole('caretaker')) {
             // Optimize: only select necessary columns
             $empQuery = Employer::select('id', 'employerNameTh', 'employerNameEn');
             if ($user->hasRole('caretaker')) {
                 $empQuery->where('assigned_staff_id', $user->id);
             }
             $employers = $empQuery->orderBy('employerNameTh')->get();
        }

        $importers = \App\Models\Importer::select('id', 'importerNameTh', 'importerNameEn')->orderBy('importerNameTh')->get();
        $delegates = \App\Models\Delegate::select('id', 'delegateNameTh', 'delegateNameEn')->orderBy('delegateNameTh')->get();

        return view('pdf_templates.index', compact('templates', 'employers', 'importers', 'delegates'));
    }

    public function listTemplates(Request $request)
    {
        $this->authorize('view-pdf-templates');

        $user = Auth::user();
        $query = PdfTemplate::query();
        $employerId = $request->query('employer_id');

        // Logic:
        // If employer_id is provided (and valid), show Global + That Employer's templates.
        // If employer_id is 'global' or empty, show ONLY Global templates.

        // However, user said: "When selecting employer... show that employer's templates... (and imply global too)".
        // Wait, strictly speaking: "Select employer... show that employer's templates...".
        // Usually you always want access to Global templates too.

        if ($employerId && $employerId !== 'global') {
            $query->where(function($q) use ($employerId) {
                $q->where('type', 'global')
                  ->orWhere('employer_id', $employerId);
            });
        } else {
            $query->where('type', 'global');
        }

        // Additional Security Scope
        if ($user->hasRole('employer')) {
            // Can only see global or OWN employer
            $query->where(function($q) use ($user) {
                 $q->where('type', 'global')
                   ->orWhere('employer_id', $user->employer->id);
            });
        }

        $templates = $query->latest()->get(['id', 'name', 'type', 'meta_data']);

        return response()->json($templates);
    }

    public function create()
    {
        $this->authorize('create-pdf-templates');

        // Admin can choose employer, Employer is fixed
        $employers = Employer::all(); // Should be optimized for large datasets

        return view('pdf_templates.create', compact('employers'));
    }

    public function store(Request $request, PdfGeneratorService $pdfService)
    {
        $this->authorize('create-pdf-templates');

        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|mimes:pdf|max:10240', // 10MB max
            'type' => 'required|in:global,employer',
            'employer_id' => 'required_if:type,employer|nullable|exists:employers,id',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        // Validate compatibility by attempting to parse with FPDI
        // This allows compatible 1.7 PDFs (without compressed streams) to pass
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
                // Attempt to normalize the temp file directly
                $normalizedPath = $pdfService->tryNormalizePdf($filePath);

                // If normalization fails, tryNormalizePdf might return null depending on implementation
                // (Though currently it throws Exception on failure, adding check for safety)
                if (!$normalizedPath || !file_exists($normalizedPath)) {
                     throw new \Exception("Normalization process failed to produce a valid file.");
                }

                // If normalization returned a path, we use that for storage
                // Manual storage logic for normalized file
                $filename = $file->hashName();
                $storePath = 'pdf_templates/' . $filename;

                // Move normalized file to public storage
                Storage::disk('public')->put($storePath, file_get_contents($normalizedPath));

                // Clean up temp normalized file
                @unlink($normalizedPath);

                // Use this path for the model creation
                $finalPath = $storePath;

            } catch (\Exception $e) {
                // If normalization fails, fall back to the error message
                $version = PdfHelper::getVersion($filePath) ?? 'Unknown';
                return back()->withErrors([
                    'file' => "The uploaded PDF is version {$version}. The system attempted to repair it but failed: " . $e->getMessage()
                ])->withInput();
            }
        } else {
            // Compatible, store normally
            $finalPath = $file->store('pdf_templates', 'public');
        }

        $template = PdfTemplate::create([
            'name' => $request->name,
            'file_path' => $finalPath,
            'type' => $request->type,
            'employer_id' => $request->type === 'employer' ? $request->employer_id : null,
            'created_by' => Auth::id(),
            'field_mapping' => [], // Initialize empty
            'meta_data' => [
                'auto_prefix_titles' => false,
                'original_filename' => $file->getClientOriginalName(),
            ],
        ]);

        return redirect()->route('admin.pdf-templates.builder', $template)
            ->with('success', 'Template uploaded. Please configure fields.');
    }

    public function builder(PdfTemplate $pdf_template)
    {
        $this->authorize('edit-pdf-templates', $pdf_template);

        return view('pdf_templates.builder', ['template' => $pdf_template]);
    }

    public function update(Request $request, PdfTemplate $pdf_template)
    {
        $this->authorize('edit-pdf-templates', $pdf_template);

        $request->validate([
            'name' => 'required|string|max:255',
            'field_mapping' => 'nullable|array',
            'meta_data' => 'nullable|array',
        ]);

        $pdf_template->update([
            'name' => $request->name,
            'field_mapping' => $request->field_mapping ?? [],
            'meta_data' => $request->meta_data ?? [],
        ]);

        return response()->json(['success' => true]);
    }

    public function uploadImage(Request $request)
    {
        $this->authorize('create-pdf-templates');

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max (FPDF doesn't support WebP natively)
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('pdf_templates/images', 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No image uploaded'], 400);
    }

    public function destroy(PdfTemplate $pdf_template)
    {
        $this->authorize('delete-pdf-templates', $pdf_template);

        if (Storage::disk('public')->exists($pdf_template->file_path)) {
            // Soft delete, so maybe don't delete file immediately?
            // Model uses SoftDeletes, so we just delete the record.
        }

        $pdf_template->delete();

        return redirect()->route('admin.pdf-templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    public function preview(Request $request, PdfTemplate $pdf_template, PdfGeneratorService $pdfService)
    {
        $this->authorize('view-pdf-templates');

        try {
            $content = $pdfService->generatePreviewPdf($pdf_template);

            $filename = 'preview_' . ($pdf_template->meta_data['original_filename'] ?? ($pdf_template->name . '.pdf'));
            if (!str_ends_with($filename, '.pdf')) {
                $filename .= '.pdf';
            }

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $filename, ['Content-Type' => 'application/pdf']);
        } catch (\Exception $e) {
            \Log::error("PDF Preview Error: " . $e->getMessage());
            return back()->with('danger', 'Failed to generate preview: ' . $e->getMessage());
        }
    }

    public function file(Request $request, PdfTemplate $pdf_template)
    {
        $this->authorize('view-pdf-templates');

        $path = $pdf_template->file_path;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        if ($request->query('download')) {
            $filename = $pdf_template->meta_data['original_filename'] ?? ($pdf_template->name . '.pdf');
            if (!str_ends_with($filename, '.pdf')) {
                $filename .= '.pdf';
            }
            return response()->download(Storage::disk('public')->path($path), $filename);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}

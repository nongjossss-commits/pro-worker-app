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
    public function index()
    {
        $this->authorize('view-pdf-templates');

        $query = PdfTemplate::query();

        // Filter by type or employer if needed
        // Admin sees all, Staff sees all, Employer sees theirs + global

        $user = Auth::user();
        if ($user->hasRole('employer')) {
             $query->where(function($q) use ($user) {
                 $q->where('type', 'global')
                   ->orWhere('employer_id', $user->employer->id);
             });
        } elseif ($user->hasRole('caretaker')) {
             // Caretaker sees global + their assigned employers
             $query->where(function($q) use ($user) {
                 $q->where('type', 'global')
                   ->orWhereIn('employer_id', Employer::where('assigned_staff_id', $user->id)->pluck('id'));
             });
        }

        $templates = $query->latest()->paginate(10);

        return view('pdf_templates.index', compact('templates'));
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

        // Validate PDF Version (Must be <= 1.4 for FPDI compatibility)
        // If not compatible, try to normalize immediately
        if (!PdfHelper::isCompatible($filePath, 1.4)) {
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
            'field_mapping' => 'nullable|array',
        ]);

        $pdf_template->update([
            'field_mapping' => $request->field_mapping ?? [],
        ]);

        return response()->json(['success' => true]);
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

    public function file(PdfTemplate $pdf_template)
    {
        $this->authorize('view-pdf-templates');

        $path = $pdf_template->file_path;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}

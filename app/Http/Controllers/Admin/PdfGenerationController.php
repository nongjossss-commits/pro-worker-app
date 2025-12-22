<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PdfTemplate;
use App\Models\Employer;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ZipArchive;
use Illuminate\Support\Facades\Storage;

class PdfGenerationController extends Controller
{
    protected $pdfService;

    public function __construct(PdfGeneratorService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function showGenerateModal(Request $request)
    {
        $this->authorize('view-pdf-templates'); // Or a generic permission

        $employeeIds = $request->input('employees', []);

        if (empty($employeeIds)) {
            return redirect()->back()->with('error', 'No employees selected.');
        }

        // Fetch Templates
        $query = PdfTemplate::query();
        $user = Auth::user();
        if ($user->hasRole('employer')) {
             $query->where(function($q) use ($user) {
                 $q->where('type', 'global')
                   ->orWhere('employer_id', $user->employer->id);
             });
        } elseif ($user->hasRole('caretaker')) {
             $query->where(function($q) use ($user) {
                 $q->where('type', 'global')
                   ->orWhereIn('employer_id', Employer::where('assigned_staff_id', $user->id)->pluck('id'));
             });
        }
        $templates = $query->latest()->get();

        // Fetch existing slot names for autocomplete
        // We can look at EmployeeGeneratedDocument for distinct names
        $existingSlots = \App\Models\EmployeeGeneratedDocument::distinct()->pluck('document_name');

        return view('pdf_templates.generate_modal', [
            'employees' => $employeeIds,
            'templates' => $templates,
            'existingSlots' => $existingSlots
        ]);
    }

    public function process(Request $request)
    {
        $this->authorize('view-pdf-templates');

        $request->validate([
            'employees' => 'required|array',
            'employees.*' => 'exists:employees,id',
            'template_id' => 'required|exists:pdf_templates,id',
            'output_type' => 'required|in:download,save_to_slot',
            'slot_name' => 'required_if:output_type,save_to_slot',
        ]);

        $employees = Employee::with('employer')->whereIn('id', $request->employees)->get();
        $template = PdfTemplate::findOrFail($request->template_id);

        try {
            $results = $this->pdfService->generateForEmployees($template, $employees, [
                'output_type' => $request->output_type,
                'slot_name' => $request->slot_name ?? null,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('danger', $e->getMessage());
        }

        if ($request->output_type === 'save_to_slot') {
            return redirect()->route('employees.index')
                ->with('success', 'Documents generated and saved to ' . count($results) . ' employees.');
        } else {
            // Handle Download
            if (count($results) === 1) {
                // Single File
                $file = $results[0];
                return response($file['content'])
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $file['filename'] . '"');
            } else {
                // Zip Multiple Files
                $zipName = 'generated_docs_' . time() . '.zip';
                $zipPath = storage_path('app/public/temp/' . $zipName);

                // Ensure temp dir exists
                if (!file_exists(dirname($zipPath))) {
                    mkdir(dirname($zipPath), 0755, true);
                }

                $zip = new ZipArchive;
                if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                    foreach ($results as $file) {
                        $zip->addFromString($file['filename'], $file['content']);
                    }
                    $zip->close();
                }

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }
        }
    }
}

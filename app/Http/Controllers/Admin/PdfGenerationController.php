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
use Illuminate\Support\Str;

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

        // existingSlots no longer needed with hardcoded select
        $existingSlots = [];

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
            // Generate content only (don't save via service)
            $results = $this->pdfService->generateForEmployees($template, $employees, [
                'output_type' => 'raw_content', // Change to get raw content
            ]);

            if ($request->output_type === 'save_to_slot') {
                $count = 0;
                $slotName = $request->slot_name;

                foreach ($results as $result) {
                    $employee = Employee::find($result['employee_id']);
                    if (!$employee) continue;

                    // Determine target model and file path
                    if (str_starts_with($slotName, 'employee_doc_')) {
                        // Save to Employee
                        $filename = 'employee_files/' . $employee->id . '/' . $slotName . '_' . time() . '.pdf';
                        Storage::disk('public')->put($filename, $result['content']);

                        // Update Employee Record
                        $employee->update([
                            $slotName => $filename,
                        ]);

                        // Try to update description if possible (e.g. employee_doc_1 -> other_doc_1_desc)
                        if (preg_match('/employee_doc_(\d+)/', $slotName, $matches)) {
                            $index = $matches[1];
                            // Check if description column exists (1-10)
                            if ($index >= 1 && $index <= 10) {
                                $descCol = "other_doc_{$index}_desc";
                                $employee->update([
                                    $descCol => "Auto-generated: " . $template->name
                                ]);
                            }
                        }

                        $count++;

                    } elseif (str_starts_with($slotName, 'employer_doc_other_')) {
                        // Save to Employer
                        if ($employee->employer) {
                            $employer = $employee->employer;
                            $filename = 'employer_documents/' . $employer->id . '/' . $slotName . '_' . $employee->id . '_' . time() . '.pdf';
                            Storage::disk('public')->put($filename, $result['content']);

                            $employer->update([
                                $slotName => $filename,
                            ]);
                             // Try to update description
                             if (preg_match('/employer_doc_other_(\d+)/', $slotName, $matches)) {
                                $index = $matches[1];
                                $descCol = "employer_doc_other_{$index}_desc";
                                $employer->update([
                                    $descCol => "Auto-generated for " . $employee->employeeNameEn . ": " . $template->name
                                ]);
                            }
                            $count++;
                        }
                    }
                }

                return redirect()->route('employees.index')
                    ->with('success', 'Documents generated and saved for ' . $count . ' employees.');

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

        } catch (\Exception $e) {
            return redirect()->back()->with('danger', $e->getMessage());
        }
    }
}

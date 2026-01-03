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

        // Fetch Employers for filtering (if admin/staff)
        $user = Auth::user();
        $employers = collect();
        if ($user->hasRole('admin') || $user->hasRole('staff') || $user->hasRole('caretaker')) {
             $query = Employer::select('id', 'employerNameTh', 'employerNameEn');
             if ($user->hasRole('caretaker')) {
                 $query->where('assigned_staff_id', $user->id);
             }
             $employers = $query->orderBy('employerNameTh')->get();
        }

        // Fetch Initial Templates (Global by default or user specific)
        $query = PdfTemplate::query();
        if ($user->hasRole('employer')) {
             $query->where(function($q) use ($user) {
                 $q->where('type', 'global')
                   ->orWhere('employer_id', $user->employer->id);
             });
        } elseif ($user->hasRole('caretaker')) {
             // By default show global + their employers
             $query->where(function($q) use ($user) {
                 $q->where('type', 'global')
                   ->orWhereIn('employer_id', Employer::where('assigned_staff_id', $user->id)->pluck('id'));
             });
        } else {
            // Admin/Staff: Default to Global only to avoid clutter, or all?
            // Let's default to global to match the "filter" UX.
            $query->where('type', 'global');
        }
        $templates = $query->latest()->get();

        return view('pdf_templates.generate_modal', [
            'employees' => $employeeIds,
            'templates' => $templates,
            'employers' => $employers
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
            // Check if Zip extension is loaded before proceeding (only for download/zip mode, but good to check generally)
            if (!extension_loaded('zip') && $request->output_type === 'download' && count($employees) > 1) {
                throw new \Exception('PHP Zip extension is not loaded. Cannot generate ZIP file.');
            }

            // Generate content only (don't save via service)
            $results = $this->pdfService->generateForEmployees($template, $employees, [
                'output_type' => 'raw_content', // Change to get raw content
            ]);

            if (empty($results)) {
                throw new \Exception('No documents were generated. Please check the template mapping and employee data.');
            }

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
                        $updated = $employee->update([
                            $slotName => $filename,
                        ]);

                        // Handle Description Update:
                        // Mapping:
                        // employee_doc_9  -> other_doc_1_desc
                        // ...
                        // employee_doc_18 -> other_doc_10_desc

                        if (preg_match('/employee_doc_(\d+)/', $slotName, $matches)) {
                            $index = (int)$matches[1];
                            // Check if it falls in the "Other Document" range (9-18)
                            if ($index >= 9 && $index <= 18) {
                                $descIndex = $index - 8; // 9->1, 10->2...
                                $descCol = "other_doc_{$descIndex}_desc";

                                // Check if description exists in fillable is not easily possible here without loading model details
                                // But based on code review, other_doc_1_desc to other_doc_10_desc exist.
                                $employee->update([
                                    $descCol => $template->name // Use Template Name as description
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

                if ($count === 0) {
                     return redirect()->route('employees.index')->with('warning', 'No documents were saved. Please check if the selected slot matches the target (Employee vs Employer).');
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
                    $openResult = $zip->open($zipPath, ZipArchive::CREATE);
                    if ($openResult === TRUE) {
                        foreach ($results as $file) {
                            $zip->addFromString($file['filename'], $file['content']);
                        }
                        $zip->close();
                    } else {
                        throw new \Exception('Failed to create ZIP archive. Error Code: ' . $openResult);
                    }

                    if (!file_exists($zipPath)) {
                        throw new \Exception('ZIP file was not created successfully.');
                    }

                    return response()->download($zipPath)->deleteFileAfterSend(true);
                }
            }

        } catch (\Throwable $e) {
            return redirect()->route('employees.index')->with('danger', 'Error generating documents: ' . $e->getMessage());
        }
    }
}

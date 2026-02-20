<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PdfTemplate;
use App\Models\Employer;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ZipArchive;

class PdfGenerationController extends Controller
{
    protected $pdfService;

    public function __construct(PdfGeneratorService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function showGenerateModal(Request $request)
    {
        $this->authorize('view-pdf-templates');

        $employeeIds = $request->input('employees', []);

        if (empty($employeeIds)) {
            return redirect()->back()->with('error', 'No employees selected.');
        }

        // Fetch Employers for filtering (if admin/staff) and for Target Employer selection
        $user = Auth::user();
        $employers = collect();
        if ($user->hasRole('admin') || $user->hasRole('staff') || $user->hasRole('caretaker') || $user->hasRole('super-admin')) {
             $query = Employer::select('id', 'employerNameTh', 'employerNameEn');
             if ($user->hasRole('caretaker')) {
                 $query->where('assigned_staff_id', $user->id);
             }
             $employers = $query->orderBy('employerNameTh')->get();
        }

        // Fetch Initial Templates
        $query = PdfTemplate::query();
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
        } else {
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
            'target_employer_id' => 'nullable|exists:employers,id',
        ]);

        // Pre-flight check for Zip extension if download mode is selected
        if ($request->output_type === 'download' && !extension_loaded('zip')) {
             return redirect()->back()->with('danger', 'PHP Zip extension is not loaded. Cannot generate ZIP file.');
        }

        $employeeIds = $request->employees;
        $templateId = $request->template_id;
        $outputType = $request->output_type;
        $slotName = $request->slot_name;
        $targetEmployerId = $request->input('target_employer_id');

        // Force Synchronous Processing for ALL counts
        return $this->processSynchronously($employeeIds, $templateId, $outputType, $slotName, $targetEmployerId);
    }

    protected function processSynchronously($employeeIds, $templateId, $outputType, $slotName, $targetEmployerId = null)
    {
        // Increase limits for large batches (e.g. 500 records)
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        try {
            $template = PdfTemplate::findOrFail($templateId);

            // Determine if we should use Empty Employer (Global Template + No Target Selected)
            $useEmptyEmployer = false;
            if ($template->type === 'global' && empty($targetEmployerId)) {
                $useEmptyEmployer = true;
            }

            // Efficiently fetch employees with relationships (include Trashed for consistency)
            $employees = Employee::withTrashed()->with('employer')->whereIn('id', $employeeIds)->get();

            if ($employees->isEmpty()) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json([], 200); // Empty result is valid but means nothing happened
                }
                return redirect()->route('employees.index')->with('warning', 'No valid employees found for generation.');
            }

            if ($outputType === 'save_to_slot') {
                // 'save_to_slot' is already memory efficient in service
                $results = $this->pdfService->generateForEmployees($template, $employees, [
                    'output_type' => 'save_to_slot',
                    'slot_name' => $slotName,
                    'target_employer_id' => $targetEmployerId,
                    'use_empty_employer' => $useEmptyEmployer
                ]);

                // Detect AJAX request (using checks like expectsJson or X-Requested-With header)
                // In Laravel, request()->expectsJson() or request()->ajax() handles this.
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json($results);
                }

                // Fallback for standard form submit
                // Filter out errors
                $successCount = collect($results)->where('status', 'saved')->count();
                $errorCount = count($results) - $successCount;

                $msg = "Successfully attached {$successCount} documents.";
                if ($errorCount > 0) {
                    $msg .= " (Failed: {$errorCount})";
                }

                return redirect()->route('employees.index')->with($errorCount > 0 ? 'warning' : 'success', $msg);

            } else {
                // Download Mode: Stream content to ZIP to save memory
                // Do NOT use generateForEmployees (which accumulates content in array)

                // Fetch Target Employer Model if needed
                $targetEmployerModel = null;
                if ($targetEmployerId) {
                    $targetEmployerModel = Employer::find($targetEmployerId);
                }

                $zipName = 'export_' . date('Ymd_His') . '.zip';
                $zipPath = storage_path('app/public/temp/' . $zipName);
                if (!is_dir(dirname($zipPath))) mkdir(dirname($zipPath), 0755, true);

                $zip = new ZipArchive;
                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

                    $errorLog = "";
                    $generatedCount = 0;

                    foreach ($employees as $employee) {
                        try {
                            // Generate Single PDF
                            $content = $this->pdfService->generateSinglePdf($template, $employee, $targetEmployerModel, $useEmptyEmployer);
                            $filename = $this->pdfService->generateFilename($template, $employee);

                            // Add to Zip
                            $zip->addFromString($filename, $content);
                            $generatedCount++;

                            // Explicitly free memory if possible (though PHP 8 is usually smart)
                            unset($content);

                        } catch (\Throwable $e) {
                            $errorLog .= "Error for Employee ID {$employee->id} ({$employee->employeeNameEn}): " . $e->getMessage() . "\n";
                        }
                    }

                    // Add error log if any
                    if (!empty($errorLog)) {
                        $zip->addFromString('_generation_errors.txt', $errorLog);
                    }

                    $zip->close();
                } else {
                    throw new \Exception("Could not create ZIP file.");
                }

                if ($generatedCount === 0) {
                     // Expose the error log in the flash message so the user knows WHY it failed.
                     $errorMessage = "No documents could be generated.";
                     if (!empty($errorLog)) {
                         $errorMessage .= " Details:\n" . $errorLog;
                     }
                     return redirect()->route('employees.index')->with('danger', $errorMessage);
                }

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }

        } catch (\Throwable $e) {
            \Log::error("Sync PDF Gen Error: " . $e->getMessage());

            if (request()->expectsJson() || request()->ajax()) {
                 return response()->json([
                     ['status' => 'error', 'message' => $e->getMessage()]
                 ], 500);
            }

            return redirect()->route('employees.index')->with('danger', 'Generation Failed: ' . $e->getMessage());
        }
    }
}

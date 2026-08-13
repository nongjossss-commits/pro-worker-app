<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ActivityLogHelper;
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
        $redirectUrl = $request->input('redirect_url', url()->previous());

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

        $importers = \App\Models\Importer::select('id', 'importerNameTh', 'importerNameEn')->orderBy('importerNameTh')->get();
        $delegates = \App\Models\Delegate::select('id', 'delegateNameTh', 'delegateNameEn')->orderBy('delegateNameTh')->get();

        return view('pdf_templates.generate_modal', [
            'employees' => $employeeIds,
            'templates' => $templates,
            'employers' => $employers,
            'importers' => $importers,
            'delegates' => $delegates,
            'redirect_url' => $redirectUrl
        ]);
    }

    /**
     * Quick print for templates that don't require employee data.
     * Accepts optional target_employer_id / target_importer_id / target_delegate_id.
     */
    public function quickPrint(Request $request, PdfTemplate $pdf_template)
    {
        $this->authorize('view-pdf-templates');

        $request->validate([
            'target_employer_id' => 'nullable|exists:employers,id',
            'target_importer_id' => 'nullable|exists:importers,id',
            'target_importer_source' => 'nullable|in:importer,employer',
            'target_importer_employer_id' => 'nullable|exists:employers,id|different:target_employer_id',
            'target_delegate_id' => 'nullable|exists:delegates,id',
            'target_employee_id' => 'nullable|exists:employees,id',
            'mode' => 'nullable|in:preview,download',
        ]);

        $targetEmployer = $request->target_employer_id ? Employer::find($request->target_employer_id) : null;
        $targetImporter = \App\Models\EmployerBackedImporter::resolveTarget(
            $request->input('target_importer_source'),
            $request->input('target_importer_id'),
            $request->input('target_importer_employer_id'),
        );
        $targetDelegate = $request->target_delegate_id ? \App\Models\Delegate::find($request->target_delegate_id) : null;
        $targetEmployee = $request->target_employee_id ? \App\Models\Employee::find($request->target_employee_id) : null;

        try {
            $content = $this->pdfService->generateForOfficeUse($pdf_template, $targetEmployer, $targetImporter, $targetDelegate, $targetEmployee);
        } catch (\Throwable $e) {
            \Log::error('QuickPrint PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('danger', 'Quick print failed: ' . $e->getMessage());
        }

        $base = $pdf_template->meta_data['original_filename'] ?? ($pdf_template->name . '.pdf');
        if (!Str::endsWith($base, '.pdf')) $base .= '.pdf';
        $filename = 'quickprint_' . $base;

        $mode = $request->input('mode', 'preview');
        $disposition = $mode === 'download' ? 'attachment' : 'inline';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
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
            'target_importer_id' => 'nullable|exists:importers,id',
            'target_importer_source' => 'nullable|in:importer,employer',
            'target_importer_employer_id' => 'nullable|exists:employers,id|different:target_employer_id',
            'target_delegate_id' => 'nullable|exists:delegates,id',
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
        $targetImporterId = $request->input('target_importer_id');
        $targetImporterSource = $request->input('target_importer_source');
        $targetImporterEmployerId = $request->input('target_importer_employer_id');
        $targetDelegateId = $request->input('target_delegate_id');
        $redirectUrl = $request->input('redirect_url', route('employees.index'));

        // Force Synchronous Processing for ALL counts
        return $this->processSynchronously($employeeIds, $templateId, $outputType, $slotName, $targetEmployerId, $targetImporterId, $targetDelegateId, $redirectUrl, $targetImporterSource, $targetImporterEmployerId);
    }

    protected function processSynchronously($employeeIds, $templateId, $outputType, $slotName, $targetEmployerId = null, $targetImporterId = null, $targetDelegateId = null, $redirectUrl = null, $targetImporterSource = null, $targetImporterEmployerId = null)
    {
        if (!$redirectUrl) {
            $redirectUrl = route('employees.index');
        }

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
                return redirect($redirectUrl)->with('warning', 'No valid employees found for generation.');
            }

            if ($outputType === 'save_to_slot') {
                // 'save_to_slot' is already memory efficient in service
                $results = $this->pdfService->generateForEmployees($template, $employees, [
                    'output_type' => 'save_to_slot',
                    'slot_name' => $slotName,
                    'target_employer_id' => $targetEmployerId,
                    'target_importer_id' => $targetImporterId,
                    'target_importer_source' => $targetImporterSource,
                    'target_importer_employer_id' => $targetImporterEmployerId,
                    'target_delegate_id' => $targetDelegateId,
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

                return redirect($redirectUrl)->with($errorCount > 0 ? 'warning' : 'success', $msg);

            } else {
                // Download Mode: Stream content to ZIP to save memory
                // Do NOT use generateForEmployees (which accumulates content in array)

                // Fetch Target Employer Model if needed
                $targetEmployerModel = null;
                if ($targetEmployerId) {
                    $targetEmployerModel = Employer::find($targetEmployerId);
                }

                $targetImporterModel = \App\Models\EmployerBackedImporter::resolveTarget(
                    $targetImporterSource,
                    $targetImporterId,
                    $targetImporterEmployerId,
                );

                $targetDelegateModel = null;
                if ($targetDelegateId) {
                    $targetDelegateModel = \App\Models\Delegate::find($targetDelegateId);
                }

                $zipName = 'export_' . date('Ymd_His') . '.zip';
                $zipPath = storage_path('app/public/temp/' . $zipName);
                if (!is_dir(dirname($zipPath))) mkdir(dirname($zipPath), 0755, true);

                $zip = new ZipArchive;
                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

                    $errorLog = "";
                    $generatedCount = 0;

                    $chunkSize = $template->meta_data['employees_per_page'] ?? 1;
                    $chunkSize = max(1, (int)$chunkSize);

                    if ($chunkSize > 1) {
                        $chunks = $employees->chunk($chunkSize);
                        foreach ($chunks as $chunkIndex => $chunkEmployees) {
                            try {
                                $content = $this->pdfService->generateChunkedPdf($template, $chunkEmployees, $targetEmployerModel, $useEmptyEmployer, $targetImporterModel, $targetDelegateModel);
                                $firstEmp = $chunkEmployees->first();
                                $filename = "chunk_" . ($chunkIndex + 1) . "_" . $this->pdfService->generateFilename($template, $firstEmp);

                                $zip->addFromString($filename, $content);
                                $generatedCount += $chunkEmployees->count();
                                unset($content);
                            } catch (\Throwable $e) {
                                $errorLog .= "Error for Chunk " . ($chunkIndex + 1) . ": " . $e->getMessage() . "\n";
                            }
                        }
                    } else {
                        foreach ($employees as $employee) {
                            try {
                                // Generate Single PDF
                                $content = $this->pdfService->generateSinglePdf($template, $employee, $targetEmployerModel, $useEmptyEmployer, $targetImporterModel, $targetDelegateModel);
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
                     return redirect($redirectUrl)->with('danger', $errorMessage);
                }

                ActivityLogHelper::logAction('generate_document', 'สร้างเอกสาร PDF อัตโนมัติ จำนวน ' . $generatedCount . ' ไฟล์', null, null, [
                    'generated_count' => $generatedCount,
                ]);

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }

        } catch (\Throwable $e) {
            \Log::error("Sync PDF Gen Error: " . $e->getMessage());

            if (request()->expectsJson() || request()->ajax()) {
                 return response()->json([
                     ['status' => 'error', 'message' => $e->getMessage()]
                 ], 500);
            }

            return redirect($redirectUrl)->with('danger', 'Generation Failed: ' . $e->getMessage());
        }
    }
}

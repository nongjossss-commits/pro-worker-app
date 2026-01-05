<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FinalizePdfBatch;
use App\Jobs\ProcessPdfGenerationBatch;
use App\Models\Employee;
use App\Models\PdfTemplate;
use App\Models\Employer;
use App\Services\PdfGeneratorService;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
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
        ]);

        // Pre-flight check for Zip extension if download mode is selected
        if ($request->output_type === 'download' && !extension_loaded('zip')) {
             return redirect()->back()->with('danger', 'PHP Zip extension is not loaded. Cannot generate ZIP file.');
        }

        $employeeIds = $request->employees;
        $templateId = $request->template_id;
        $outputType = $request->output_type;
        $slotName = $request->slot_name;
        $userId = Auth::id();
        $batchId = (string) Str::uuid(); // Unique ID for this operation

        // Hybrid Strategy:
        // If count < 25, run SYNCHRONOUSLY to ensure immediate feedback/success.
        // If count >= 25, run via QUEUE/BATCH to prevent timeout.
        if (count($employeeIds) < 25) {
            return $this->processSynchronously($employeeIds, $templateId, $outputType, $slotName);
        }

        // --- ASYNC BATCH MODE (For > 25 employees) ---

        // Chunk size for batch processing
        $chunkSize = 50;
        $chunks = array_chunk($employeeIds, $chunkSize);
        $totalCount = count($employeeIds);

        $jobs = [];
        foreach ($chunks as $chunk) {
            $jobs[] = new ProcessPdfGenerationBatch(
                $chunk,
                $templateId,
                $outputType,
                $slotName,
                $userId,
                $batchId
            );
        }

        try {
            Bus::batch($jobs)
                ->name('PDF Generation - ' . $batchId)
                ->then(function (Batch $batch) use ($batchId, $userId, $outputType, $totalCount) {
                    dispatch(new FinalizePdfBatch($batchId, $userId, $outputType, $totalCount));
                })
                ->dispatch();

            $message = $outputType === 'download'
                ? 'Processing started. You will receive a notification with the download link shortly.'
                : 'Processing started. Documents will be attached to employee records in the background.';

            return redirect()->route('employees.index')->with('success', $message);

        } catch (\Throwable $e) {
            return redirect()->route('employees.index')->with('danger', 'Error starting batch process: ' . $e->getMessage());
        }
    }

    protected function processSynchronously($employeeIds, $templateId, $outputType, $slotName)
    {
        try {
            $employees = Employee::with('employer')->whereIn('id', $employeeIds)->get();
            $template = PdfTemplate::findOrFail($templateId);

            if ($outputType === 'save_to_slot') {
                $results = $this->pdfService->generateForEmployees($template, $employees, [
                    'output_type' => 'save_to_slot',
                    'slot_name' => $slotName
                ]);

                // Filter out errors
                $successCount = collect($results)->where('status', 'saved')->count();
                $errorCount = count($results) - $successCount;

                $msg = "Successfully attached {$successCount} documents.";
                if ($errorCount > 0) {
                    $msg .= " (Failed: {$errorCount})";
                }

                return redirect()->route('employees.index')->with($errorCount > 0 ? 'warning' : 'success', $msg);

            } else {
                // Download Mode: Generate content and ZIP immediately
                $results = $this->pdfService->generateForEmployees($template, $employees, [
                    'output_type' => 'raw_content'
                ]);

                if (empty($results)) {
                    return redirect()->back()->with('danger', 'Failed to generate any documents.');
                }

                // Create Zip
                $zipName = 'export_' . date('Ymd_His') . '.zip';
                $zipPath = storage_path('app/public/temp/' . $zipName);
                if (!is_dir(dirname($zipPath))) mkdir(dirname($zipPath), 0755, true);

                $zip = new ZipArchive;
                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    foreach ($results as $item) {
                        if (isset($item['filename']) && isset($item['content'])) {
                            $zip->addFromString($item['filename'], $item['content']);
                        }
                    }
                    $zip->close();
                } else {
                    throw new \Exception("Could not create ZIP file.");
                }

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }

        } catch (\Throwable $e) {
            \Log::error("Sync PDF Gen Error: " . $e->getMessage());
            return redirect()->route('employees.index')->with('danger', 'Generation Failed: ' . $e->getMessage());
        }
    }
}

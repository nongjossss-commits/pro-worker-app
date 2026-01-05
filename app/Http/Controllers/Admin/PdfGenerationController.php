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
}

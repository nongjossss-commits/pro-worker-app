<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\PdfTemplate;
use App\Services\PdfGeneratorService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessPdfGenerationBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employeeIds;
    protected $templateId;
    protected $outputType; // 'download' or 'save_to_slot'
    protected $slotName;
    protected $userId;
    protected $customBatchId; // Custom UUID for temp folder organization

    public function __construct($employeeIds, $templateId, $outputType, $slotName, $userId, $customBatchId)
    {
        $this->employeeIds = $employeeIds;
        $this->templateId = $templateId;
        $this->outputType = $outputType;
        $this->slotName = $slotName;
        $this->userId = $userId;
        $this->customBatchId = $customBatchId;
    }

    public function handle(PdfGeneratorService $pdfService)
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Fetch Employees
        $employees = Employee::with('employer')->whereIn('id', $this->employeeIds)->get();
        if ($employees->isEmpty()) return;

        $template = PdfTemplate::findOrFail($this->templateId);

        if ($this->outputType === 'save_to_slot') {
            // Delegate completely to Service (which now handles pathing and DB updates correctly)
            $pdfService->generateForEmployees($template, $employees, [
                'output_type' => 'save_to_slot',
                'slot_name' => $this->slotName
            ]);
        } else {
            // Download Mode: Get raw content and save to batch temp folder
            $results = $pdfService->generateForEmployees($template, $employees, [
                'output_type' => 'raw_content'
            ]);

            foreach ($results as $result) {
                if (isset($result['content']) && isset($result['filename'])) {
                    $this->handleDownloadStorage($result['content'], $result['filename']);
                }
            }
        }
    }

    protected function handleDownloadStorage($content, $filename)
    {
        // Store in a temp folder dedicated to this batch UUID
        // storage/app/public/temp/batches/{customBatchId}/{filename}
        $path = 'temp/batches/' . $this->customBatchId . '/' . $filename;
        Storage::disk('public')->put($path, $content);
    }
}

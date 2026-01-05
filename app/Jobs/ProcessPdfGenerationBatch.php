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
    protected $batchId; // Custom UUID for temp folder organization

    public function __construct($employeeIds, $templateId, $outputType, $slotName, $userId, $batchId)
    {
        $this->employeeIds = $employeeIds;
        $this->templateId = $templateId;
        $this->outputType = $outputType;
        $this->slotName = $slotName;
        $this->userId = $userId;
        $this->batchId = $batchId;
    }

    public function handle(PdfGeneratorService $pdfService)
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $employees = Employee::with('employer')->whereIn('id', $this->employeeIds)->get();
        $template = PdfTemplate::findOrFail($this->templateId);

        foreach ($employees as $employee) {
            try {
                // Generate Content (Using Raw Content Mode)
                $result = $pdfService->generateForEmployees($template, collect([$employee]), [
                    'output_type' => 'raw_content'
                ]);

                if (empty($result)) continue;

                $fileData = $result[0];
                $content = $fileData['content'];

                // Fix: Ensure filename is unique by appending Employee ID
                // Original: TemplateName-EmployeeName.pdf
                // New: TemplateName-EmployeeName-ID.pdf
                $baseFilename = Str::slug($template->name . '-' . $employee->employeeNameEn . '-' . $employee->id) . '.pdf';

                if ($this->outputType === 'save_to_slot') {
                    $this->handleSaveToSlot($employee, $content, $template);
                } else {
                    $this->handleDownloadStorage($content, $baseFilename);
                }

            } catch (\Exception $e) {
                // Log error but continue batch
                \Log::error("PDF Batch Error (Emp ID: {$employee->id}): " . $e->getMessage());
            }
        }
    }

    protected function handleSaveToSlot($employee, $content, $template)
    {
        $slotName = $this->slotName;

        // Determine path and model
        if (str_starts_with($slotName, 'employee_doc_')) {
            $filename = 'employee_files/' . $employee->id . '/' . $slotName . '_' . time() . '.pdf';
            Storage::disk('public')->put($filename, $content);

            $employee->update([$slotName => $filename]);

            // Update Description if applicable
            if (preg_match('/employee_doc_(\d+)/', $slotName, $matches)) {
                $index = (int)$matches[1];
                if ($index >= 9 && $index <= 18) {
                    $descIndex = $index - 8;
                    $employee->update(["other_doc_{$descIndex}_desc" => $template->name]);
                }
            }

        } elseif (str_starts_with($slotName, 'employer_doc_other_')) {
            if ($employee->employer) {
                $employer = $employee->employer;
                $filename = 'employer_documents/' . $employer->id . '/' . $slotName . '_' . $employee->id . '_' . time() . '.pdf';
                Storage::disk('public')->put($filename, $content);

                $employer->update([$slotName => $filename]);

                if (preg_match('/employer_doc_other_(\d+)/', $slotName, $matches)) {
                    $index = $matches[1];
                    $employer->update(["employer_doc_other_{$index}_desc" => "Auto: " . $employee->employeeNameEn . " - " . $template->name]);
                }
            }
        }
    }

    protected function handleDownloadStorage($content, $filename)
    {
        // Store in a temp folder dedicated to this batch UUID
        // storage/app/public/temp/batches/{batchId}/{filename}
        $path = 'temp/batches/' . $this->batchId . '/' . $filename;
        Storage::disk('public')->put($path, $content);
    }
}

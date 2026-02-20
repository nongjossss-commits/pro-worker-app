<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCustomField;
use App\Models\ProductionCustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomFieldController extends Controller
{
    /**
     * Download a custom field document as PDF (converting images if necessary).
     */
    public function downloadCustomFieldPdf(Request $request, $id)
    {
        $type = $request->input('type', 'employee'); // Default to employee

        if ($type === 'employee') {
            $field = EmployeeCustomField::findOrFail($id);
            // Authorize: Check if user can view the employee
            $this->authorize('view', $field->employee);
        } else {
            $field = ProductionCustomField::findOrFail($id);
            // Authorize: Check if user can view employers (using basic permission for now)
            $this->authorize('view-employers');
        }

        if ($field->field_type !== 'file' || !$field->file_path) {
            abort(404, 'File not found or not a file field.');
        }

        $filePath = $field->file_path;
        $disk = 'public';
        $disposition = $request->input('disposition', 'attachment');

        return \App\Helpers\PdfHelper::streamFile($disk, $filePath, $disposition);
    }
}

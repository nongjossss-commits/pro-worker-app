<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
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

        $fieldName = $field->field_name ?? 'Custom_Field';
        $ownerName = 'Unknown';
        $ownerId = '';

        if ($type === 'employee') {
            $owner = $field->employee;
            $ownerName = $owner->employeeNameTh ?: ($owner->employeeNameEn ?: 'Unknown_Employee');
            $ownerId = $owner->employer_employee_id ?: $owner->id;
        } else {
            // ProductionCustomField (Polymorphic)
            $owner = $field->model;
            if ($owner instanceof \App\Models\Employer) {
                $ownerName = $owner->employerNameTh ?: ($owner->employerNameEn ?: 'Unknown_Employer');
                $ownerId = $owner->employerId;
            } elseif ($owner instanceof \App\Models\Employee) {
                $ownerName = $owner->employeeNameTh ?: ($owner->employeeNameEn ?: 'Unknown_Employee');
                $ownerId = $owner->employer_employee_id ?: $owner->id;
            } else {
                 $ownerName = $owner ? (class_basename($owner) . '_' . $owner->id) : 'Unknown_Owner';
                 $ownerId = $owner ? $owner->id : '';
            }
        }

        $filename = "{$fieldName}_{$ownerName}_{$ownerId}.pdf";

        $filePath = $field->file_path;
        $disk = 'public';
        $disposition = $request->input('disposition', 'inline');

        return \App\Helpers\PdfHelper::streamFile($disk, $filePath, $disposition, $filename);
    }
}

<?php

// app/Http/Requests/StoreTicketReplyRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
// V2.4-S11: Add Rule
use Illuminate\Validation\Rule;

class StoreTicketReplyRequest extends FormRequest
{
/**
* Determine if the user is authorized to make this request.
*/
public function authorize(): bool
{
return Auth::check();
}

/**
* V2.4-S11: Prepare inputs for validation.
* Decode JSON strings for new employees into arrays.
*/
protected function prepareForValidation()
{
$attachments = $this->input('attachments');

if (isset($attachments['new_employees']) && is_array($attachments['new_employees'])) {
$decodedNewEmployees = [];
foreach ($attachments['new_employees'] as $key => $value) {
// Check if the value is a JSON string from the frontend
if (is_string($value)) {
$decoded = json_decode($value, true);
// Check if decoding was successful
if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
$decodedNewEmployees[$key] = $decoded;
} else {
// If invalid JSON, keep the original value so validation fails later
$decodedNewEmployees[$key] = $value;
}
} elseif (is_array($value)) {
// If already an array (e.g. during testing or if restored from old() input previously)
$decodedNewEmployees[$key] = $value;
}
}
$attachments['new_employees'] = $decodedNewEmployees;
$this->merge(['attachments' => $attachments]);
}
}

/**
* Get the validation rules that apply to the request.
*/
public function rules(): array
{
// V2.4-S11: Define required fields for new employees
$newEmployeeRequiredFields = [
'employeeTitleTh' => ['required', 'string', 'max:50'],
'employeeNameTh' => ['required', 'string', 'max:255'],
'employeeNationality' => ['required', 'string', 'max:100'],
'employeePassport' => ['required', 'string', 'max:50'],
'employeeTitleEn' => ['nullable', 'string', 'max:50'],
'employeeNameEn' => ['nullable', 'string', 'max:255'],
'nature_of_work' => ['nullable', 'string', 'max:255'],
];

// V2.4-S11: Define the temporary file validation rule
$tempFileValidation = [
'nullable',
'string',
function ($attribute, $value, $fail) {
if ($value && (!str_starts_with($value, 'temp_uploads/') || !Storage::disk('public')->exists($value))) {
$fail("The file attachment path ($value) is invalid or the file has expired.");
}
},
];

// V2.4-S11: Define the required_without_all condition
$allAttachments = 'attachments.files,attachments.existing_employees,attachments.new_employees';

return [
// Message is required ONLY IF NO attachments of ANY type are present.
'message' => ['nullable', 'string', 'max:5000', 'required_without_all:' . $allAttachments],

'attachments' => ['nullable', 'array'],

// --- 1. Files (Remove old required_without:message rule) ---
'attachments.files' => ['nullable', 'array'],
'attachments.files.*.name' => ['required', 'string', 'max:255'],
'attachments.files.*.size' => ['required', 'integer', 'min:1'],
'attachments.files.*.path' => [
'required',
'string',
function ($attribute, $value, $fail) {
if (!str_starts_with($value, 'temp_uploads/') || !Storage::disk('public')->exists($value)) {
$fail("The file attachment path is invalid or the file has expired.");
}
},
],

// --- V2.4-S11: 2. Existing Employees (New Logic) ---
'attachments.existing_employees' => ['nullable', 'array'],
// Validate existence in the DB.
'attachments.existing_employees.*' => ['required', 'integer', 'distinct', Rule::exists('employees', 'id')],

// --- V2.4-S11: 3. New Employees (New Logic) ---
'attachments.new_employees' => ['nullable', 'array'],
// Validate as arrays (thanks to prepareForValidation)
'attachments.new_employees.*' => ['required', 'array'],
// Validate nested fields
...$this->buildNestedValidationRules('attachments.new_employees.*.', $newEmployeeRequiredFields),
// Validate file paths
'attachments.new_employees.*.employeePhoto' => $tempFileValidation,
'attachments.new_employees.*.document_1' => $tempFileValidation,
];
}

// V2.4-S11: Helper function for nested validation
protected function buildNestedValidationRules($prefix, $rules): array
{
$nestedRules = [];
foreach ($rules as $field => $rule) {
$nestedRules[$prefix . $field] = $rule;
}
return $nestedRules;
}

public function messages(): array
{
return [
// V2.4-S11 Update: Use required_without_all
'message.required_without_all' => 'กรุณาพิมพ์ข้อความ หรือ แนบไฟล์/ลูกจ้าง อย่างน้อยหนึ่งอย่าง',
'attachments.existing_employees.*.exists' => 'ลูกจ้างที่เลือกไม่ถูกต้องหรือไม่พบในระบบ',
];
}
}

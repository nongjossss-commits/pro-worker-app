<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
// V2.5-PATCH: เพิ่มการอ้างอิง Storage และ Rule
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StoreAdminJobTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('manage-tickets');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // V2.5-PATCH: สร้างกฎสำหรับไฟล์แนบชั่วคราว
        $tempFileValidation = [
            'nullable',
            'string',
            function ($attribute, $value, $fail) {
                if ($value && (!str_starts_with($value, 'temp_uploads/') || !Storage::disk('public')->exists($value))) {
                    $fail("ไฟล์แนบ ($value) ไม่ถูกต้อง หรือหมดอายุ");
                }
            },
        ];

        return [
            'employer_user_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'], // Admin ต้องกรอกข้อความแรกเสมอ
            'attachments' => ['nullable', 'array'],

            // --- V2.5-PATCH: อัปเดตกฎให้เหมือนกับ StoreTicketRequest และ StoreTicketReplyRequest ---

            // 1. Files
            'attachments.files' => ['nullable', 'array'],
            'attachments.files.*.name' => ['required', 'string', 'max:255'],
            'attachments.files.*.size' => ['required', 'integer', 'min:1'],
            'attachments.files.*.path' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!str_starts_with($value, 'temp_uploads/') || !Storage::disk('public')->exists($value)) {
                        $fail("ไฟล์แนบ ($value) ไม่ถูกต้อง หรือหมดอายุ");
                    }
                },
            ],

            // 2. Existing Employees (Strict Affiliation Check)
            'attachments.existing_employees' => ['nullable', 'array'],
            'attachments.existing_employees.*' => [
                'required',
                'integer',
                'distinct',
                // V2.5-PATCH: ตรวจสอบว่า Employee ID นี้ อยู่ใน Employer ที่เลือกมาจริงๆ
                Rule::exists('employees', 'id')->where(function ($query) {
                    // ค้นหา employer_id จาก employer_user_id ที่ส่งมา
                    $user = \App\Models\User::find($this->input('employer_user_id'));
                    if ($user && $user->employer) {
                        $query->where('employer_id', $user->employer->id);
                    } else {
                        // ถ้าไม่เจอนายจ้าง ให้ validation fail ไปเลย
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],

            // 3. External Employees (V2.5-S17: Exception for Admin/Staff - No Affiliation Check)
            'attachments.external_employees' => ['nullable', 'array'],
            'attachments.external_employees.*' => [
                'required',
                'integer',
                'distinct',
                'exists:employees,id', // Just check existence, ignore employer
            ],

            // 4. New Employees (JSON data from modal)
            'attachments.new_employees' => ['nullable', 'array'],
            'attachments.new_employees.*' => ['required', 'array'], // ตรวจสอบว่าเป็น array ที่ถูก decode แล้ว

            // Validation for text fields (Updated to 'required' to match messages and intent)
            'attachments.new_employees.*.employeeTitleTh' => ['required', 'string', 'max:50'],
            'attachments.new_employees.*.employeeNameTh' => ['required', 'string', 'max:255'],
            'attachments.new_employees.*.employeeNationality' => ['required', 'string', 'max:100'],
            'attachments.new_employees.*.employeePassport' => ['required', 'string', 'max:50'],

            // Validation for all file attachments
            'attachments.new_employees.*.employeePhoto' => $tempFileValidation,
            'attachments.new_employees.*.insurance_document_path_social' => $tempFileValidation,
            'attachments.new_employees.*.insurance_document_path_hospital' => $tempFileValidation,
            'attachments.new_employees.*.insurance_document_path_private' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_1' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_2' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_3' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_4' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_5' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_6' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_7' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_8' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_9' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_10' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_11' => $tempFileValidation,
            'attachments.new_employees.*.employee_doc_12' => $tempFileValidation,
        ];
    }

    /**
     * Prepare the data for validation.
     * (คงเดิม: โค้ดนี้ทำงานถูกต้องในการ decode JSON)
     */
    protected function prepareForValidation(): void
    {
        $attachments = $this->input('attachments', []);

        if (!empty($attachments['new_employees']) && is_array($attachments['new_employees'])) {
            $attachments['new_employees'] = array_map(function ($item) {
                if (is_string($item)) {
                    $decoded = json_decode($item, true);
                    return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $item;
                }
                return $item;
            }, $attachments['new_employees']);
        }

        $this->merge([
            'attachments' => $attachments,
        ]);
    }

    /**
     * Custom error messages for validation failures.
     */
    public function messages(): array
    {
        return [
            'employer_user_id.required' => 'กรุณาเลือกนายจ้าง',
            'attachments.existing_employees.*.exists' => 'ลูกจ้างที่เลือกไม่ถูกต้อง หรือไม่ได้อยู่ในสังกัดของนายจ้างที่เลือก',
            'attachments.external_employees.*.exists' => 'ลูกจ้างภายนอกที่เลือกไม่ถูกต้อง (ไม่พบในระบบ)',
            'attachments.new_employees.*.employeeNameTh.required' => 'กรุณากรอกชื่อ-สกุล (ภาษาไทย) สำหรับลูกจ้างใหม่',
            'attachments.new_employees.*.employeePassport.required' => 'กรุณากรอก Passport สำหรับลูกจ้างใหม่',
            'attachments.new_employees.*.employeeNationality.required' => 'กรุณาเลือกสัญชาติสำหรับลูกจ้างใหม่',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StoreAdminJobTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only users with 'manage-tickets' permission (Admins/Staff) can use this form.
        return Auth::check() && Auth::user()->can('manage-tickets');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // V2.4-S12: Admin must select which employer the ticket is for.
            'employer_user_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],

            // --- Attachment Validation (Same as StoreTicketRequest) ---
            'attachments.existing_employees'   => ['nullable', 'array'],
            'attachments.existing_employees.*' => ['integer', 'exists:employees,id'],
            'attachments.new_employees' => ['nullable', 'array'],
            'attachments.new_employees.*' => ['json'],

            'attachments.files'       => ['nullable', 'array'],
            'attachments.files.*.path' => ['required', 'string'],
            'attachments.files.*.name' => ['required', 'string'],
            'attachments.files.*.size' => ['required', 'integer'],
        ];
    }


     /**
     * Custom error messages for validation failures.
     */
    public function messages(): array
    {
        return [
            'employer_user_id.required' => 'กรุณาเลือกนายจ้าง',
            'attachments.new_employees.*.employeeNameTh.required' => 'กรุณากรอกชื่อ-สกุล (ภาษาไทย) สำหรับลูกจ้างใหม่',
            // Add other custom messages as needed
        ];
    }
}

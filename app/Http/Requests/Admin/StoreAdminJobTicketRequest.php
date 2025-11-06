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
            'attachments.new_employees'        => ['nullable', 'array'],
            // Notice: new_employees.* is now an array, not JSON
            'attachments.new_employees.*.employeeTitleTh'   => ['required', 'string', 'max:20'],
            'attachments.new_employees.*.employeeNameTh'    => ['required', 'string', 'max:100'],
            'attachments.new_employees.*.employeeDob'       => ['required', 'date'],
            'attachments.new_employees.*.employeeNationality' => ['required', 'string', 'max:100'],
            'attachments.new_employees.*.employeePassport'  => ['nullable', 'string', 'max:100'],
            // File paths are strings from the temp upload
            'attachments.new_employees.*.employeePhoto' => ['nullable', 'string'],
            'attachments.new_employees.*.document_1'    => ['nullable', 'string'],

            'attachments.files'       => ['nullable', 'array'],
            'attachments.files.*.path' => ['required', 'string'],
            'attachments.files.*.name' => ['required', 'string'],
            'attachments.files.*.size' => ['required', 'integer'],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * This method is crucial for handling the JSON-encoded strings for new employees
     * that come from the Alpine.js component. We decode them into arrays so the
     * validator can check the nested fields.
     */
    protected function prepareForValidation(): void
    {
        $attachments = $this->input('attachments', []);

        // If new employees are present, decode each one from JSON string to array
        if (!empty($attachments['new_employees']) && is_array($attachments['new_employees'])) {
            $attachments['new_employees'] = array_map(function ($item) {
                if (is_string($item)) {
                    return json_decode($item, true);
                }
                return $item; // Already an array
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
            'attachments.new_employees.*.employeeNameTh.required' => 'กรุณากรอกชื่อ-สกุล (ภาษาไทย) สำหรับลูกจ้างใหม่',
            // Add other custom messages as needed
        ];
    }
}

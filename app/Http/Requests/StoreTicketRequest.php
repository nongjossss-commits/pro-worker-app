<?php

// app/Http/Requests/StoreTicketRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
// Import Storage facade
use Illuminate\Support\Facades\Storage;

class StoreTicketRequest extends FormRequest
{
    // ... (authorize() method remains the same)
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization logic: Must be an 'employer'.
        return Auth::check() && Auth::user()->hasRole('employer');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // V2.5-S8: Loosen validation to match Admin side. Controller handles logic.
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],

            // 1. Files
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

            // 2. Existing Employees
            'attachments.existing_employees' => ['nullable', 'array'],
            'attachments.existing_employees.*' => [
                'required',
                'integer',
                'distinct',
                // Ensure the employee belongs to the authenticated employer.
                'exists:employees,id,employer_id,' . Auth::user()->employer->id,
            ],

            // 3. New Employees (JSON data from modal)
            'attachments.new_employees' => ['nullable', 'array'],
            // The controller will now handle the JSON decoding, so we just expect an array here.
            'attachments.new_employees.*' => ['required', 'array'],
            'attachments.new_employees.*.employeeTitleTh' => ['nullable', 'string', 'max:50'],
            'attachments.new_employees.*.employeeNameTh' => ['nullable', 'string', 'max:255'],
            'attachments.new_employees.*.employeeNationality' => ['nullable', 'string', 'max:100'],
            'attachments.new_employees.*.employeePassport' => ['nullable', 'string', 'max:50'],
            'attachments.new_employees.*.employeePhoto' => ['nullable', 'string'],
            'attachments.new_employees.*.document_1' => ['nullable', 'string'],

        ];
    }

    /**
     * V2.5-S8: Prepare the data for validation, decoding new_employee JSON.
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
}

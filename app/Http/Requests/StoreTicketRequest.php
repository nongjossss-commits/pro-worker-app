<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->hasRole('employer');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['required', 'array', 'min:1'], // Must have at least one attachment.
            'attachments.existing_employees' => ['nullable', 'array'],
            'attachments.existing_employees.*' => ['integer', 'exists:employees,id'],
            'attachments.new_employees' => ['nullable', 'array'],
            // New employee data is now validated as a nested array of objects
            'attachments.new_employees.*.employeeTitleTh' => ['required', 'string'],
            'attachments.new_employees.*.employeeNameTh' => ['required', 'string'],
            'attachments.new_employees.*.employeeDob' => ['required', 'date'],
            'attachments.new_employees.*.employeeNationality' => ['required', 'string'],
            'attachments.files' => ['nullable', 'array'],
            'attachments.files.*.name' => ['required', 'string', 'max:255'],
            'attachments.files.*.size' => ['required', 'integer', 'min:1'],
            'attachments.files.*.path' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!str_starts_with($value, 'temp_uploads/') || !Storage::disk('public')->exists($value)) {
                        $fail("The file attachment at {$attribute} is invalid or has expired.");
                    }
                },
            ],
        ];
    }
}

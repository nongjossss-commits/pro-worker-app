<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTicketRequest extends FormRequest
{
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
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            // V2.4-S5: Validate Existing Employees
            'attachments.existing_employees' => ['nullable', 'array'],
            // Ensure IDs are integers and exist in the employees table
            'attachments.existing_employees.*' => ['integer', 'exists:employees,id'],
            // V2.4-S6: Validate New Employees (Array of JSON strings)
            'attachments.new_employees' => ['nullable', 'array'],
            // Ensure each item in the array is a valid JSON string
            'attachments.new_employees.*' => ['json'],
        ];
    }
}

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
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            // Existing/New Employees validation rules remain the same
            'attachments.existing_employees' => ['nullable', 'array'],
            'attachments.existing_employees.*' => ['integer', 'exists:employees,id'],
            'attachments.new_employees' => ['nullable', 'array'],
            'attachments.new_employees.*' => ['json'],
            // V2.4-S7: Validate General File Attachments
            'attachments.files' => ['nullable', 'array'],
            'attachments.files.*.name' => ['required', 'string', 'max:255'],
            'attachments.files.*.size' => ['required', 'integer', 'min:1'],
            'attachments.files.*.path' => [
                'required',
                'string',
                // CRITICAL SECURITY: Custom rule to ensure the path exists in the temporary storage
                function ($attribute, $value, $fail) {
                    // Ensure it looks like a temp path AND the file exists on the 'public' disk
                    if (!str_starts_with($value, 'temp_uploads/') || !Storage::disk('public')->exists($value)) {
                        // This prevents users from submitting arbitrary file paths or expired uploads
                        $fail("The file attachment path is invalid or the file has expired.");
                    }
                },
            ],
        ];
    }
}

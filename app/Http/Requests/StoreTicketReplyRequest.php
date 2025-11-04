<?php
// app/Http/Requests/StoreTicketReplyRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoreTicketReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization logic (e.g. checking ticket status) is handled in the Controller.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Message is required ONLY IF files array is empty or missing.
            'message' => ['nullable', 'string', 'max:5000', 'required_without:attachments.files'],
            // Attachments structure validation
            'attachments' => ['nullable', 'array'],
            // Files array is required ONLY IF message is empty or missing.
            'attachments.files' => ['nullable', 'array', 'required_without:message'],
            'attachments.files.*.name' => ['required', 'string', 'max:255'],
            'attachments.files.*.size' => ['required', 'integer', 'min:1'],
            'attachments.files.*.path' => [
                'required',
                'string',
                // CRITICAL SECURITY: Reuse the secure temporary file validation logic
                function ($attribute, $value, $fail) {
                    if (!str_starts_with($value, 'temp_uploads/') || !Storage::disk('public')->exists($value)) {
                        $fail("The file attachment path is invalid or the file has expired.");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required_without' => 'กรุณาพิมพ์ข้อความ หรือ แนบไฟล์ อย่างน้อยหนึ่งอย่าง',
            'attachments.files.required_without' => 'กรุณาพิมพ์ข้อความ หรือ แนบไฟล์ อย่างน้อยหนึ่งอย่าง',
        ];
    }
}

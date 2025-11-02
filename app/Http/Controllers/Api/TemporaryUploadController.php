<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemporaryUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Security: Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validation: image or pdf, max 5MB (5120 KB)
        // Use validator() helper for clean API response handling
        $validator = validator($request->all(), [
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            // Return a 422 Unprocessable Entity status with the specific error message
            return response()->json(['error' => $validator->errors()->first('file')], 422);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            // Generate a unique name (UUID) and store in 'public/temp_uploads'
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('temp_uploads', $filename, 'public');

            return response()->json([
                'message' => 'File uploaded successfully',
                'path' => $path, // The relative path stored in the database/JSON
                // The full URL for preview purposes in the UI
                'url' => Storage::disk('public')->url($path)
            ]);
        }

        return response()->json(['error' => 'Upload failed'], 400);
    }
}

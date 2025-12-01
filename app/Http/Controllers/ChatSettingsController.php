<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatSettingsController extends Controller
{
    /**
     * Update the user's chat background preference.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // Validate the request
        $request->validate([
            'chat_background' => 'nullable|string', // For presets or clearing
            'chat_background_file' => 'nullable|image|max:2048', // For custom uploads
        ]);

        // Handle File Upload
        if ($request->hasFile('chat_background_file')) {
            // Delete old file if it exists and is not a preset
            if ($user->chat_background && !str_starts_with($user->chat_background, 'preset-') && !str_starts_with($user->chat_background, 'http')) {
                 // The DB stores 'storage/chat_backgrounds/...'. We need to remove 'storage/' to get the disk relative path.
                 $oldPath = str_replace('storage/', '', $user->chat_background);
                 if (Storage::disk('public')->exists($oldPath)) {
                     Storage::disk('public')->delete($oldPath);
                 }
            }

            $path = $request->file('chat_background_file')->store('chat_backgrounds/' . $user->id, 'public');
            $user->chat_background = 'storage/' . $path;
        }
        // Handle Preset Selection or Clear
        elseif ($request->has('chat_background')) {
            $user->chat_background = $request->input('chat_background');
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Chat background updated successfully.',
            'background' => $user->chat_background
        ]);
    }
}

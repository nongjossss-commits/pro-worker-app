<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Fetch list of users for the chat sidebar (Contacts).
     */
    public function fetchContacts()
    {
        $currentUserId = Auth::id();

        // Optimized Query: Fetch users and count unread messages in one go
        $contacts = User::where('id', '!=', $currentUserId)
            ->select('id', 'name', 'avatar_path', 'position_title', 'last_active_at')
            ->withCount(['sentChatMessages as unread_count' => function ($query) use ($currentUserId) {
                $query->where('receiver_id', $currentUserId)
                      ->where('is_read', false);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'position_title' => $user->position_title,
                    'is_online' => $user->last_active_at && $user->last_active_at->diffInMinutes(now()) < 5,
                    'unread_count' => $user->unread_count,
                ];
            });

        return response()->json($contacts);
    }

    /**
     * Fetch messages between current user and specific user.
     */
    public function fetchMessages($userId)
    {
        $currentUserId = Auth::id();

        $messages = ChatMessage::where(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)
                  ->where('receiver_id', $userId);
            })
            ->orWhere(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)
                  ->where('receiver_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->with('sender:id,name,avatar_path')
            ->get();

        // Mark as read
        ChatMessage::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    /**
     * Send a message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string', // Make message nullable if sending file only
            'context_data' => 'nullable|array'
        ]);

        if (empty($request->message) && empty($request->context_data)) {
             return response()->json(['error' => 'Message or attachment required'], 422);
        }

        $message = ChatMessage::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message ?? '',
            'context_data' => $request->context_data,
            'is_read' => false,
        ]);

        // Update sender's last active
        User::where('id', Auth::id())->update(['last_active_at' => now()]);

        return response()->json($message->load('sender:id,name,avatar_path'));
    }

    /**
     * Poll for new messages.
     * Used to check if there are any new messages globally or from a specific context.
     * Returns a simplified status or list of new messages since a timestamp.
     */
    public function checkNewMessages(Request $request)
    {
        $lastCheck = $request->input('last_check'); // Timestamp
        $currentUserId = Auth::id();

        // Update activity
        User::where('id', $currentUserId)->update(['last_active_at' => now()]);

        $query = ChatMessage::where('receiver_id', $currentUserId)
            ->where('is_read', false);

        if ($lastCheck) {
            $query->where('created_at', '>', $lastCheck);
        }

        $newMessages = $query->with('sender:id,name,avatar_path')->get();

        return response()->json([
            'messages' => $newMessages,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Update User Profile (Avatar, Bio, Position).
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'position_title' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:2048', // 2MB
        ]);

        $user = Auth::user();
        // Check actual class type, although type hint says Authenticatable
        if (!($user instanceof User)) {
             $user = User::find(Auth::id());
        }

        $user->name = $request->name;
        $user->position_title = $request->position_title;
        $user->bio = $request->bio;

        if ($request->hasFile('avatar')) {
            // Delete old if exists
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_path = $path;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'position_title' => $user->position_title,
                'bio' => $user->bio
            ]
        ]);
    }

    /**
     * Upload a file for chat.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $path = $request->file('file')->store('chat_attachments', 'public');
        $url = Storage::disk('public')->url($path);
        $name = $request->file('file')->getClientOriginalName();
        $mime = $request->file('file')->getMimeType();

        return response()->json([
            'url' => $url,
            'name' => $name,
            'type' => str_starts_with($mime, 'image/') ? 'image' : 'file',
            'mime' => $mime
        ]);
    }
}

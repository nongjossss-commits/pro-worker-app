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
        $currentUser = Auth::user();

        // 1. STRICT BLOCK: Employers cannot access chat at all
        if ($currentUser->hasRole('employer')) {
            return response()->json([], 403);
        }

        // Permission Check: User must have 'use-chat' permission OR be Admin/Staff explicitly
        if (!$currentUser->can('use-chat') && !$currentUser->hasRole(['admin', 'staff'])) {
            return response()->json([], 403);
        }

        // Base Query: Exclude current user and ensure active status
        $query = User::where('id', '!=', $currentUser->id)
                     ->where('status', 'active');

        // --- Access Control Logic ---
        if ($currentUser->hasRole(['admin', 'staff', 'caretaker'])) {
            // Admin, Staff, and Caretaker can see each other.
            // Employers are now BLOCKED, so they are removed from visibility.

            $query->where(function($q) {
                // Mutual Visibility: Admin, Staff, Caretaker see each other
                $q->whereHas('roles', function($r) {
                    $r->whereIn('name', ['admin', 'staff', 'caretaker']);
                });
            });

        } else {
            // Fallback for any other role: See nobody
            return response()->json([]);
        }

        // Optimized Select
        $contacts = $query->select('id', 'name', 'avatar_path', 'position_title', 'last_active_at')
            ->withCount(['sentChatMessages as unread_count' => function ($query) use ($currentUser) {
                $query->where('receiver_id', $currentUser->id)
                      ->where('is_read', false);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'position_title' => $user->position_title,
                    // Online if active in last 5 minutes
                    'is_online' => $user->last_active_at && $user->last_active_at->diffInMinutes(now()) < 5,
                    'unread_count' => $user->unread_count,
                    'last_message_time' => null // Could populate this if needed, but keeping lightweight
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
        $currentUser = Auth::user();

        // 1. STRICT BLOCK: Employers cannot access chat
        if ($currentUser->hasRole('employer')) {
            return response()->json(['error' => 'Access Denied: Employers are blocked from chat'], 403);
        }

        // Basic Permission Check
        if (!$currentUser->can('use-chat') && !$currentUser->hasRole(['admin', 'staff'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // --- Security Check: Validate relationship logic again ---
        $canChat = false;
        $targetUser = User::with(['roles', 'employer'])->find($userId);

        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Prevent chatting with blocked employers
        if ($targetUser->hasRole('employer')) {
            return response()->json(['error' => 'Cannot chat with employer (Blocked)'], 403);
        }

        // Logic for Admin/Staff/Caretaker
        if ($currentUser->hasRole(['admin', 'staff', 'caretaker'])) {
            // Can chat with any Admin/Staff/Caretaker
            if ($targetUser->hasRole(['admin', 'staff', 'caretaker'])) {
                $canChat = true;
            }
        }

        if (!$canChat) {
             return response()->json(['error' => 'Access Denied'], 403);
        }

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
            'message' => 'nullable|string',
            'context_data' => 'nullable|array'
        ]);

        $currentUser = Auth::user();

        // 1. STRICT BLOCK: Employers cannot access chat
        if ($currentUser->hasRole('employer')) {
            return response()->json(['error' => 'Access Denied'], 403);
        }

        if (!$currentUser->can('use-chat') && !$currentUser->hasRole(['admin', 'staff'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Re-verify permission
        $userId = $request->receiver_id;
        $canChat = false;
        $targetUser = User::with(['roles', 'employer'])->find($userId);

        if ($targetUser) {
            // Prevent chatting with blocked employers
            if ($targetUser->hasRole('employer')) {
                 return response()->json(['error' => 'Access Denied: Target is blocked'], 403);
            }

            if ($currentUser->hasRole(['admin', 'staff', 'caretaker'])) {
                if ($targetUser->hasRole(['admin', 'staff', 'caretaker'])) {
                    $canChat = true;
                }
            }
        }

        if (!$canChat) {
            return response()->json(['error' => 'Access Denied'], 403);
        }

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
     * Optimized to reduce load.
     */
    public function checkNewMessages(Request $request)
    {
        // Don't validate strict timestamp, just use what's sent or default to recent
        $lastCheck = $request->input('last_check');
        $currentUserId = Auth::id();

        // Update activity - But maybe not on EVERY poll if it's too frequent?
        // Let's do it only if > 1 minute has passed since last update in session?
        // For now, keep it but the frontend polling will be slower (10-15s).
        // To save DB writes, we can check if last_active_at is old enough.
        $user = Auth::user();
        if ($user && (!$user->last_active_at || $user->last_active_at->diffInMinutes(now()) >= 5)) {
            $user->update(['last_active_at' => now()]);
        }

        $query = ChatMessage::where('receiver_id', $currentUserId)
            ->where('is_read', false);

        if ($lastCheck) {
            $query->where('created_at', '>', $lastCheck);
        }

        // Limit the check to prevent massive dumps if something goes wrong
        $newMessages = $query->with('sender:id,name,avatar_path')
            ->limit(20)
            ->get();

        return response()->json([
            'messages' => $newMessages,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Update User Profile.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'position_title' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();
        if (!($user instanceof User)) {
             $user = User::find(Auth::id());
        }

        $user->name = $request->name;
        $user->position_title = $request->position_title;
        $user->bio = $request->bio;

        if ($request->hasFile('avatar')) {
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
            'file' => 'required|file|max:10240',
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

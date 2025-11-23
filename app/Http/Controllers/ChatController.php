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

        // Permission Check: User must have 'use-chat' permission (Admin, Staff, or allowed Employer)
        // If Admin, bypass specific permission check if they are superadmin, but standard is 'use-chat'
        if (!$currentUser->can('use-chat') && !$currentUser->hasRole('admin')) {
            return response()->json([], 403);
        }

        // Base Query: Exclude current user
        $query = User::where('id', '!=', $currentUser->id);

        // --- Access Control Logic ---
        if ($currentUser->hasRole('admin')) {
            // Admin sees EVERYONE.
            // No filters applied.
        } elseif ($currentUser->hasRole('employer')) {
            // Employer sees:
            // 1. Admins
            // 2. Staff
            // 3. THEIR Assigned Staff (Caretaker)
            // They do NOT see other Employers.

            // Get the assigned staff ID if it exists
            $assignedStaffId = null;
            if ($currentUser->employer) {
                $assignedStaffId = $currentUser->employer->assigned_staff_id;
            }

            $query->where(function($q) use ($assignedStaffId) {
                // 1. See System Users (Admin, Staff)
                $q->whereHas('roles', function($r) {
                    $r->whereIn('name', ['admin', 'staff']);
                });

                // 2. See their specific assigned staff
                if ($assignedStaffId) {
                    $q->orWhere('id', $assignedStaffId);
                }
            });

        } elseif ($currentUser->hasRole(['staff', 'caretaker'])) {
            // Staff/Caretaker sees:
            // 1. Colleagues (Admin, Staff, Caretaker)
            // 2. Employers assigned SPECIFICALLY to them.
            // 3. IF they are 'staff' (not just caretaker), maybe they see ALL employers?
            //    The requirement says: "Staff and Caretaker... visible to each other".
            //    "Employers won't be able to use this chat channel if Admin doesn't allow it... if allowed... Employer sees Admin and Staff and Caretaker that matches rights."

            // Let's assume Staff can see ALL Colleagues.
            $query->where(function($q) use ($currentUser) {
                // 1. See Colleagues (Admin, Staff, Caretaker)
                $q->whereHas('roles', function($r) {
                    $r->whereIn('name', ['admin', 'staff', 'caretaker']);
                });

                // 2. See Employers assigned to this user
                // We check users who have an 'employer' record where assigned_staff_id matches current user.
                $q->orWhereHas('employer', function($e) use ($currentUser) {
                    $e->where('assigned_staff_id', $currentUser->id);
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

        // Basic Permission Check
        if (!$currentUser->can('use-chat') && !$currentUser->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // --- Security Check: Validate relationship logic again ---
        // We reuse the logic: Can I see this user in my contacts?
        // This prevents users from manually hitting the API for users they shouldn't see.

        $canChat = false;
        $targetUser = User::with(['roles', 'employer'])->find($userId);

        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($currentUser->hasRole('admin')) {
            $canChat = true;
        } elseif ($currentUser->hasRole('employer')) {
             // Target must be Admin, Staff, or Assigned Staff
             $isSystem = $targetUser->hasRole(['admin', 'staff']);
             $isAssigned = $currentUser->employer && $currentUser->employer->assigned_staff_id == $userId;

             if ($isSystem || $isAssigned) {
                 $canChat = true;
             }
        } elseif ($currentUser->hasRole(['staff', 'caretaker'])) {
             // Target must be Colleague OR Assigned Employer
             $isColleague = $targetUser->hasRole(['admin', 'staff', 'caretaker']);
             // Check if target is an employer assigned to me
             $isAssignedEmployer = $targetUser->employer && $targetUser->employer->assigned_staff_id == $currentUserId;

             if ($isColleague || $isAssignedEmployer) {
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
        if (!$currentUser->can('use-chat') && !$currentUser->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Re-verify permission (same logic as fetchMessages)
        $userId = $request->receiver_id;
        $canChat = false;
        $targetUser = User::with(['roles', 'employer'])->find($userId);

        if ($targetUser) {
            if ($currentUser->hasRole('admin')) {
                $canChat = true;
            } elseif ($currentUser->hasRole('employer')) {
                $isSystem = $targetUser->hasRole(['admin', 'staff']);
                $isAssigned = $currentUser->employer && $currentUser->employer->assigned_staff_id == $userId;
                if ($isSystem || $isAssigned) $canChat = true;
            } elseif ($currentUser->hasRole(['staff', 'caretaker'])) {
                $isColleague = $targetUser->hasRole(['admin', 'staff', 'caretaker']);
                $isAssignedEmployer = $targetUser->employer && $targetUser->employer->assigned_staff_id == $currentUser->id;
                if ($isColleague || $isAssignedEmployer) $canChat = true;
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

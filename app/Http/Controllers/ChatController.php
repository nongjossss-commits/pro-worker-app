<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChatMessage;
use App\Models\ChatGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Fetch list of users and groups for the chat sidebar.
     */
    public function fetchContacts()
    {
        $currentUser = Auth::user();

        // 1. STRICT BLOCK: Employers cannot access chat at all
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json([], 403);
        }

        // Permission Check
        if (!$currentUser->can('use-chat') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json([], 403);
        }

        // Base User Query
        $userQuery = User::where('id', '!=', $currentUser->id)
                     ->where('status', 'active');

        // --- User Access Control Logic ---
        if ($currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            $userQuery->where(function($q) {
                // Admin, Staff, Caretaker, Delegate see each other
                $q->whereHas('roles', function($r) {
                    $r->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
                });
            });
        } else {
            return response()->json([]);
        }

        $contacts = $userQuery->select('id', 'name', 'avatar_path', 'position_title', 'last_active_at')
            ->withCount(['sentChatMessages as unread_count' => function ($query) use ($currentUser) {
                $query->where('receiver_id', $currentUser->id)
                      ->where('is_read', false);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'type' => 'user',
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'position_title' => $user->position_title,
                    'is_online' => $user->last_active_at && $user->last_active_at->diffInMinutes(now()) < 5,
                    'unread_count' => $user->unread_count,
                    'last_message_time' => null
                ];
            });

        // --- Groups Logic ---
        // 1. Community Group (Always one, auto-generated if missing)
        // Check if community group exists, create if not (and if admin/staff)
        $communityGroup = ChatGroup::firstOrCreate(
            ['type' => 'community'],
            ['name' => 'Community Chat', 'created_by' => 1] // Assuming ID 1 is superadmin
        );

        // 2. Fetch Groups user is part of OR is Community
        // Since we don't strictly enforce membership for Community (everyone has access), we just fetch it.
        // For custom groups, we check membership.

        $groups = ChatGroup::where('type', 'community')
            ->orWhereHas('members', function($q) use ($currentUser) {
                $q->where('user_id', $currentUser->id);
            })
            ->withCount(['messages as unread_count' => function($q) use ($currentUser) {
                 // Logic for unread group messages is complex without a read_receipts table.
                 // For now, we'll return 0 or implement a simple check later.
                 // A simple way is to check messages created_at > user's last view time (not tracked yet).
                 // We will skip unread count for groups for now to avoid complexity or set to 0.
                 // Or we could use the 'is_read' flag on message if we assume it's read by *everyone*? No.
                 // For this iteration, unread count for groups is omitted.
                 $q->whereRaw('1=0');
            }])
            ->get()
            ->map(function($group) {
                return [
                    'id' => $group->id,
                    'type' => 'group',
                    'group_type' => $group->type, // 'community' or 'private_group'
                    'name' => $group->name,
                    'avatar_url' => $group->type === 'community' ? '/images/community-icon.png' : '/images/group-icon.png', // Placeholder
                    'position_title' => $group->type === 'community' ? 'Public Channel' : 'Group Chat',
                    'is_online' => true, // Groups always "online"
                    'unread_count' => 0, // Placeholder
                    'last_message_time' => null
                ];
            });

        // Merge Groups and Users
        // We want Groups at the top, then Users.
        $allContacts = $groups->merge($contacts);

        return response()->json($allContacts);
    }

    /**
     * Fetch messages.
     * Supports ?type=user|group
     */
    public function fetchMessages($id, Request $request)
    {
        $currentUserId = Auth::id();
        $currentUser = Auth::user();
        $type = $request->query('type', 'user'); // Default to user for backward compatibility

        // Access Checks
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Access Denied'], 403);
        }

        if ($type === 'group') {
            $group = ChatGroup::find($id);
            if (!$group) return response()->json(['error' => 'Group not found'], 404);

            // Authorization:
            // Community: Open to all allowed roles.
            // Private: Must be member.
            if ($group->type !== 'community') {
                if (!$group->members()->where('user_id', $currentUserId)->exists() && !$currentUser->hasRole(['admin'])) {
                     return response()->json(['error' => 'Not a member of this group'], 403);
                }
            }

            $messages = ChatMessage::where('chat_group_id', $id)
                ->orderBy('created_at', 'asc')
                ->with('sender:id,name,avatar_path')
                ->get();

            return response()->json($messages);

        } else {
            // Direct Message Logic (Existing)
            $targetUser = User::find($id);
            if (!$targetUser) return response()->json(['error' => 'User not found'], 404);

            $messages = ChatMessage::where(function ($q) use ($currentUserId, $id) {
                    $q->where('sender_id', $currentUserId)->where('receiver_id', $id);
                })
                ->orWhere(function ($q) use ($currentUserId, $id) {
                    $q->where('sender_id', $id)->where('receiver_id', $currentUserId);
                })
                ->orderBy('created_at', 'asc')
                ->with('sender:id,name,avatar_path')
                ->get();

            // Mark as read (Simple 1-on-1 logic)
            ChatMessage::where('sender_id', $id)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json($messages);
        }
    }

    /**
     * Send a message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string',
            'context_data' => 'nullable|array',
            // receiver_id is optional if group_id is present
            'receiver_id' => 'required_without:chat_group_id',
            'chat_group_id' => 'required_without:receiver_id',
            'mentions' => 'nullable|array' // Array of user IDs
        ]);

        $currentUser = Auth::user();

        // Strict Access Check
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
             return response()->json(['error' => 'Access Denied'], 403);
        }

        $data = [
            'sender_id' => Auth::id(),
            'message' => $request->message ?? '',
            'context_data' => $request->context_data,
            'is_read' => false, // Default
            'mentions' => $request->mentions,
        ];

        if ($request->chat_group_id) {
            $group = ChatGroup::find($request->chat_group_id);
            if (!$group) return response()->json(['error' => 'Group not found'], 404);

            // Check membership for private groups
            if ($group->type !== 'community' && !$group->members()->where('user_id', Auth::id())->exists() && !$currentUser->hasRole('admin')) {
                return response()->json(['error' => 'Not a member'], 403);
            }

            $data['chat_group_id'] = $group->id;
            $data['receiver_id'] = null;

        } else {
            // DM Logic
            $data['receiver_id'] = $request->receiver_id;
            $data['chat_group_id'] = null;
        }

        $message = ChatMessage::create($data);

        // Update sender's last active
        User::where('id', Auth::id())->update(['last_active_at' => now()]);

        return response()->json($message->load('sender:id,name,avatar_path'));
    }

    /**
     * Poll for new messages.
     */
    public function checkNewMessages(Request $request)
    {
        $lastCheck = $request->input('last_check');
        $currentUserId = Auth::id();

        // 1. Direct Messages
        $dmQuery = ChatMessage::where('receiver_id', $currentUserId)
            ->where('is_read', false);

        if ($lastCheck) {
            $dmQuery->where('created_at', '>', $lastCheck);
        }

        $newDMs = $dmQuery->with('sender:id,name,avatar_path')->get();

        // 2. Group Messages (Community or Joined Groups)
        // We fetch messages from groups the user has access to, created after last_check
        // For polling simplicity, we just fetch recent messages from groups the user is in.
        // Optimization: track "last_read_message_id" per group would be better, but "created_at > last_check" works for now.

        $communityGroupId = ChatGroup::where('type', 'community')->value('id');
        $myGroupIds = DB::table('chat_group_members')->where('user_id', $currentUserId)->pluck('chat_group_id')->toArray();
        if ($communityGroupId) {
            $myGroupIds[] = $communityGroupId;
        }

        $groupQuery = ChatMessage::whereIn('chat_group_id', $myGroupIds)
            ->where('sender_id', '!=', $currentUserId); // Don't fetch own messages

        if ($lastCheck) {
            $groupQuery->where('created_at', '>', $lastCheck);
        } else {
             // If no last check, maybe don't fetch anything or just very recent?
             // Logic in frontend usually provides no last_check on first load, but this method is for polling.
             // If it's a poll, we usually have last_check. If not, default to last 10 seconds?
             $groupQuery->where('created_at', '>', now()->subSeconds(10));
        }

        $newGroupMsgs = $groupQuery->with('sender:id,name,avatar_path')->get();

        $allMessages = $newDMs->merge($newGroupMsgs);

        return response()->json([
            'messages' => $allMessages,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    // ... existing updateProfile and uploadFile methods ...
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

    /**
     * Create a new Group.
     */
    public function createGroup(Request $request)
    {
        $currentUser = Auth::user();

        // Access: Only Admin and Staff
        if (!$currentUser->hasRole(['admin', 'staff'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'members' => 'nullable|array', // Array of user IDs to add
            'members.*' => 'exists:users,id'
        ]);

        $group = ChatGroup::create([
            'name' => $request->name,
            'type' => 'private_group',
            'created_by' => $currentUser->id
        ]);

        // Add creator as admin
        $group->members()->attach($currentUser->id, ['role' => 'admin']);

        // Add other members
        if ($request->members) {
            $group->members()->attach($request->members, ['role' => 'member']);
        }

        return response()->json([
            'success' => true,
            'group' => $group
        ]);
    }

    /**
     * Search users for mentions.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->input('q');
        if (!$query) return response()->json([]);

        // Search Admin, Staff, Caretaker, Delegate
        $users = User::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->whereHas('roles', function($r) {
                $r->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
            })
            ->limit(10)
            ->get(['id', 'name', 'avatar_path']);

        // Transform for frontend
        $results = $users->map(function($u) {
            return [
                'id' => $u->id,
                'value' => $u->name, // For autocomplete
                'avatar_url' => $u->avatar_url
            ];
        });

        return response()->json($results);
    }
}

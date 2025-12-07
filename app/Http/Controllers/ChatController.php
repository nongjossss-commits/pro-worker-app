<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChatMessage;
use App\Models\ChatMessageRead; // Added
use App\Models\ChatGroup;
use App\Models\PushSubscription; // Added
use App\Services\WebPushService; // Added
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
            // Allow fetching groups even if no user contacts are visible
            $userQuery->whereRaw('1 = 0');
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
        $communityGroup = ChatGroup::firstOrCreate(
            ['type' => 'community'],
            ['name' => 'Community Chat', 'created_by' => 1] // Assuming ID 1 is superadmin
        );

        // 2. Fetch Groups user is part of OR is Community
        $groups = ChatGroup::query()
            ->where(function($q) use ($currentUser) {
                $q->where('type', 'community'); // Everyone (who has access to chat) sees community
                $q->orWhereHas('members', function($m) use ($currentUser) {
                    $m->where('user_id', $currentUser->id);
                });
            })
            ->withCount(['messages as unread_count' => function($q) use ($currentUser) {
                 // UPDATED Logic for unread group messages:
                 // Count messages NOT read by this user
                 $q->whereDoesntHave('reads', function($r) use ($currentUser) {
                     $r->where('user_id', $currentUser->id);
                 })->where('sender_id', '!=', $currentUser->id);
            }])
            ->get()
            ->map(function($group) use ($currentUser) {
                return [
                    'id' => $group->id,
                    'type' => 'group',
                    'group_type' => $group->type,
                    'name' => $group->name,
                    'avatar_url' => $group->avatar_url,
                    'position_title' => $group->type === 'community' ? 'Public Channel' : 'Group Chat',
                    'is_online' => true,
                    'unread_count' => $group->unread_count, // Now accurate
                    'last_message_time' => null,
                    'is_admin' => $currentUser->hasRole('admin') || $group->members()->where('user_id', $currentUser->id)->where('role', 'admin')->exists()
                ];
            });

        // Merge Groups and Users
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

            // Authorization
            if ($group->type !== 'community') {
                if (!$group->members()->where('user_id', $currentUserId)->exists() && !$currentUser->hasRole(['admin'])) {
                     return response()->json(['error' => 'Not a member of this group'], 403);
                }
            }

            // Mark unread messages as read (excluding own messages)
            $unreadMessages = ChatMessage::where('chat_group_id', $id)
                ->where('sender_id', '!=', $currentUserId)
                ->whereDoesntHave('reads', function($q) use ($currentUserId) {
                    $q->where('user_id', $currentUserId);
                })
                ->pluck('id');

            foreach ($unreadMessages as $msgId) {
                ChatMessageRead::firstOrCreate([
                    'chat_message_id' => $msgId,
                    'user_id' => $currentUserId
                ]);
            }

            $messages = ChatMessage::where('chat_group_id', $id)
                ->orderBy('created_at', 'asc')
                ->with('sender:id,name,avatar_path')
                ->withCount('reads') // Get count of reads
                ->get();

            // Transform to include read info for UI
            $messages = $messages->map(function($msg) {
                $msg->read_count = $msg->reads_count; // "Read X"
                return $msg;
            });

            return response()->json($messages);

        } else {
            // Direct Message Logic (Existing)
            $targetUser = User::find($id);
            if (!$targetUser) return response()->json(['error' => 'User not found'], 404);

            // Mark as read (Simple 1-on-1 logic)
            ChatMessage::where('sender_id', $id)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messages = ChatMessage::where(function ($q) use ($currentUserId, $id) {
                    $q->where('sender_id', $currentUserId)->where('receiver_id', $id);
                })
                ->orWhere(function ($q) use ($currentUserId, $id) {
                    $q->where('sender_id', $id)->where('receiver_id', $currentUserId);
                })
                ->orderBy('created_at', 'asc')
                ->with('sender:id,name,avatar_path')
                ->get();

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

        // If it's a group message, we can automatically mark it as read by sender?
        // Not really needed, as they don't count towards their own unread count.

        // Update sender's last active
        User::where('id', Auth::id())->update(['last_active_at' => now()]);

        $message->load('sender:id,name,avatar_path');
        $message->read_count = 0; // Initial read count

        // --- WEB PUSH NOTIFICATION ---
        // Fire and forget (or queue it in a real app)
        // We find recipients who are NOT the sender.
        $recipientIds = [];

        if ($data['chat_group_id']) {
            // Get all members of the group except sender
            // Community group: All 'active' users who have chat access? Or explicit members?
            $group = ChatGroup::find($data['chat_group_id']);
            if ($group->type === 'community') {
                // Community is implicitly everyone with roles admin/staff/caretaker/delegate
                 $recipientIds = User::whereHas('roles', function($q) {
                        $q->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
                 })->where('id', '!=', Auth::id())->pluck('id')->toArray();
            } else {
                 $recipientIds = $group->members()->where('user_id', '!=', Auth::id())->pluck('user_id')->toArray();
            }
        } else {
            $recipientIds = [$data['receiver_id']];
        }

        // Send Push
        if (!empty($recipientIds)) {
            $subscriptions = PushSubscription::whereIn('user_id', $recipientIds)->get();
            $pushService = new WebPushService();
            $payload = json_encode([
                'title' => 'New Message from ' . $currentUser->name,
                'body' => substr($message->message, 0, 100),
                'icon' => $currentUser->avatar_url,
                'url' => '/dashboard#chat'
            ]);

            foreach ($subscriptions as $sub) {
                // Simple check: we don't want to push if user is currently online (last_active < 1 min)?
                // The requirement says "like modern chat apps", which usually push anyway or silence if focused.
                // We'll push.
                $pushService->sendNotification(
                    $sub->endpoint,
                    ['p256dh' => $sub->public_key, 'auth' => $sub->auth_token],
                    $payload
                );
            }
        }

        return response()->json($message);
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
        // Groups user is in
        $myGroupIds = DB::table('chat_group_members')->where('user_id', $currentUserId)->pluck('chat_group_id')->toArray();
        // Plus Community Groups
        $communityGroupIds = ChatGroup::where('type', 'community')->pluck('id')->toArray();
        $allGroupIds = array_unique(array_merge($myGroupIds, $communityGroupIds));

        $groupQuery = ChatMessage::whereIn('chat_group_id', $allGroupIds)
            ->where('sender_id', '!=', $currentUserId); // Don't fetch own messages

        if ($lastCheck) {
            $groupQuery->where('created_at', '>', $lastCheck);
        } else {
             $groupQuery->where('created_at', '>', now()->subSeconds(10));
        }

        $newGroupMsgs = $groupQuery->with('sender:id,name,avatar_path')
            ->withCount('reads')
            ->get();

        // Add read_count to all new group messages
        $newGroupMsgs->transform(function ($msg) {
            $msg->read_count = $msg->reads_count;
            return $msg;
        });

        $allMessages = $newDMs->merge($newGroupMsgs);

        // --- NEW: Fetch Read Updates for recent messages ---
        // We need to know if "Read X" count updated for messages we see.
        // For simplicity, let's fetch read counts for messages sent by ME or in my groups in the last 1 hour.
        // Or better: Let the frontend handle calling 'fetchMessages' periodically? No, that's heavy.

        // Let's just return a list of {id, read_count, is_read} for messages updated recently.
        // This is getting complex for simple polling.
        // Alternative: The user just asked for "Read X" label.
        // If we only send new messages, the "Read X" on OLD messages won't update until refresh.
        // Let's include a separate list of "read_updates".

        $readUpdates = [];
        if ($lastCheck) {
             // Find messages where a read occurred since last_check
             // For Groups:
             $updatedGroupMessages = ChatMessage::whereIn('chat_group_id', $allGroupIds)
                ->whereHas('reads', function($q) use ($lastCheck) {
                    $q->where('read_at', '>', $lastCheck);
                })
                ->withCount('reads')
                ->limit(50) // Limit to avoid massive payloads
                ->get();

             foreach($updatedGroupMessages as $msg) {
                 $readUpdates[] = [
                     'id' => $msg->id,
                     'read_count' => $msg->reads_count,
                     'is_read' => true // irrelevant for group usually, but consistent structure
                 ];
             }

             // For DMs sent by ME:
             $updatedDMs = ChatMessage::where('sender_id', $currentUserId)
                ->where('is_read', true)
                ->where('updated_at', '>', $lastCheck) // approximate
                ->get();

             foreach($updatedDMs as $msg) {
                 $readUpdates[] = [
                     'id' => $msg->id,
                     'read_count' => 0,
                     'is_read' => true
                 ];
             }
        }

        return response()->json([
            'messages' => $allMessages,
            'read_updates' => $readUpdates,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Mark specific messages as read (e.g., when a new message arrives in an open window)
     */
    public function markAsRead(Request $request)
    {
        $currentUserId = Auth::id();
        $messageIds = $request->input('message_ids', []);

        if (empty($messageIds)) return response()->json(['success' => true]);

        // Filter messages that are group messages vs DMs
        $messages = ChatMessage::whereIn('id', $messageIds)->get();

        foreach($messages as $msg) {
            if ($msg->receiver_id == $currentUserId) {
                // DM
                $msg->update(['is_read' => true]);
            } elseif ($msg->chat_group_id) {
                // Group Message
                ChatMessageRead::firstOrCreate([
                    'chat_message_id' => $msg->id,
                    'user_id' => $currentUserId
                ]);
            }
        }

        return response()->json(['success' => true]);
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
            'members.*' => 'exists:users,id',
            'avatar' => 'nullable|image|max:2048'
        ]);

        $groupData = [
            'name' => $request->name,
            'type' => 'private_group',
            'created_by' => $currentUser->id
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('group_avatars', 'public');
            $groupData['avatar_path'] = $path;
        }

        $group = ChatGroup::create($groupData);

        // Add creator as admin
        $group->members()->attach($currentUser->id, ['role' => 'admin']);

        // Add other members
        if ($request->members) {
            // Filter unique
            $members = array_unique($request->members);
            $group->members()->attach($members, ['role' => 'member']);
        }

        return response()->json([
            'success' => true,
            'group' => $group
        ]);
    }

    public function updateGroup(Request $request, $id)
    {
        $currentUser = Auth::user();
        $group = ChatGroup::findOrFail($id);

        // Check permission (Admin role or Group Admin)
        $isGroupAdmin = $group->members()->where('user_id', $currentUser->id)->wherePivot('role', 'admin')->exists();
        if (!$currentUser->hasRole('admin') && !$isGroupAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $group->name = $request->name;

        if ($request->hasFile('avatar')) {
            if ($group->avatar_path && Storage::disk('public')->exists($group->avatar_path)) {
                Storage::disk('public')->delete($group->avatar_path);
            }
            $path = $request->file('avatar')->store('group_avatars', 'public');
            $group->avatar_path = $path;
        }

        $group->save();

        return response()->json([
            'success' => true,
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'avatar_url' => $group->avatar_url,
            ]
        ]);
    }

    /**
     * Search users for mentions.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->input('q');
        $groupId = $request->input('group_id'); // Optional: restrict to group members

        if (!$query && !$groupId) return response()->json([]);

        // Base Query
        $usersQuery = User::where('status', 'active');

        // Filter by Query
        if ($query && $query !== 'all') {
            $usersQuery->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            });
        }

        // Restrict to Group Members if provided and NOT Community
        if ($groupId) {
            $group = ChatGroup::find($groupId);

            // Security Check: Requester must be a member or admin to see the member list
            $currentUser = Auth::user();
            if ($group && $group->type !== 'community') {
                $isMember = $group->members()->where('user_id', $currentUser->id)->exists();
                if (!$isMember && !$currentUser->hasRole('admin')) {
                    return response()->json([], 403);
                }

                $usersQuery->whereHas('chatGroups', function($q) use ($groupId) {
                    $q->where('chat_groups.id', $groupId);
                });
            } else {
                 // For Community or if group not specified, we search all staff/admin/etc.
                 // This matches the general contact list logic.
                 $usersQuery->whereHas('roles', function($r) {
                    $r->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
                });
            }
        } else {
             // Fallback default search (Global Admin/Staff)
             $usersQuery->whereHas('roles', function($r) {
                $r->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
            });
        }

        $users = $usersQuery->limit(20)->get(['id', 'name', 'avatar_path']);

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

    /**
     * Delete a group.
     */
    public function destroyGroup($id)
    {
        $currentUser = Auth::user();
        $group = ChatGroup::findOrFail($id);

        if ($group->type === 'community') {
            return response()->json(['error' => 'Cannot delete Community group'], 403);
        }

        // Check permission (Admin role or Group Owner)
        // Group Owner check: Assuming 'created_by' or admin role in pivot
        $isGroupOwner = $group->created_by == $currentUser->id;

        if (!$currentUser->hasRole(['admin', 'staff']) && !$isGroupOwner) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Optional: Detach all members
        $group->members()->detach();
        $group->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get Group Details including members.
     */
    public function getGroupDetails($id)
    {
        $currentUser = Auth::user();
        $group = ChatGroup::with(['members' => function($q) {
            $q->select('users.id', 'users.name', 'users.avatar_path', 'chat_group_members.role as group_role');
        }])->findOrFail($id);

        if ($group->type !== 'community' && !$group->members()->where('user_id', $currentUser->id)->exists() && !$currentUser->hasRole('admin')) {
             return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'avatar_url' => $group->avatar_url,
                'members' => $group->members->map(function($m) {
                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                        'avatar_url' => $m->avatar_url,
                        'role' => $m->group_role // 'admin' or 'member'
                    ];
                })
            ]
        ]);
    }

    /**
     * Add member to group.
     */
    public function addMember(Request $request, $id)
    {
        $currentUser = Auth::user();
        $group = ChatGroup::findOrFail($id);

        // Check permissions
        $isGroupAdmin = $group->members()->where('user_id', $currentUser->id)->wherePivot('role', 'admin')->exists();
        if (!$currentUser->hasRole(['admin', 'staff']) && !$isGroupAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $userId = $request->input('user_id');
        if (!$group->members()->where('user_id', $userId)->exists()) {
            $group->members()->attach($userId, ['role' => 'member']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove member from group.
     */
    public function removeMember($id, $userId)
    {
        $currentUser = Auth::user();
        $group = ChatGroup::findOrFail($id);

        // Check permissions
        $isGroupAdmin = $group->members()->where('user_id', $currentUser->id)->wherePivot('role', 'admin')->exists();
        // Allow user to leave (remove themselves)
        $isSelf = $currentUser->id == $userId;

        if (!$currentUser->hasRole(['admin', 'staff']) && !$isGroupAdmin && !$isSelf) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $group->members()->detach($userId);

        return response()->json(['success' => true]);
    }
}

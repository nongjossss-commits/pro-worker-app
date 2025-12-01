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
        $communityGroup = ChatGroup::firstOrCreate(
            ['type' => 'community'],
            ['name' => 'Community Chat', 'created_by' => 1] // Assuming ID 1 is superadmin
        );

        // 2. Fetch Groups
        $groups = ChatGroup::query()
            ->where(function($q) use ($currentUser) {
                $q->where('type', 'community');
                // Admin sees all private groups too
                if ($currentUser->hasRole('admin')) {
                     $q->orWhere('type', 'private_group');
                } else {
                     $q->orWhereHas('members', function($m) use ($currentUser) {
                        $m->where('user_id', $currentUser->id);
                    });
                }
            })
            ->withCount(['messages as unread_count' => function($q) use ($currentUser) {
                 $q->whereRaw('1=0'); // Unread count placeholder
            }])
            ->get()
            ->map(function($group) use ($currentUser) {
                // Determine avatar
                $avatarUrl = $group->avatar_path ? Storage::disk('public')->url($group->avatar_path) : ($group->type === 'community' ? '/images/community-icon.png' : '/images/group-icon.png');

                // Determine if admin (creator or in members pivot with role admin)
                // Note: Pivot role check requires eager loading members or separate query.
                // For simplicity, we assume 'created_by' is admin, or we can check pivot if needed for detailed permissions.
                $isAdmin = $group->created_by == $currentUser->id || $currentUser->hasRole('admin');

                return [
                    'id' => $group->id,
                    'type' => 'group',
                    'group_type' => $group->type,
                    'name' => $group->name,
                    'avatar_url' => $avatarUrl,
                    'position_title' => $group->type === 'community' ? 'Public Channel' : 'Group Chat',
                    'is_online' => true,
                    'unread_count' => 0,
                    'last_message_time' => null,
                    'can_edit' => $isAdmin
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
        $type = $request->query('type', 'user');

        // Access Checks
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Access Denied'], 403);
        }

        if ($type === 'group') {
            $group = ChatGroup::find($id);
            if (!$group) return response()->json(['error' => 'Group not found'], 404);

            // Authorization:
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
            // Direct Message Logic
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

            // Mark as read
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
            'receiver_id' => 'required_without:chat_group_id',
            'chat_group_id' => 'required_without:receiver_id',
            'mentions' => 'nullable|array'
        ]);

        $currentUser = Auth::user();

        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
             return response()->json(['error' => 'Access Denied'], 403);
        }

        $data = [
            'sender_id' => Auth::id(),
            'message' => $request->message ?? '',
            'context_data' => $request->context_data,
            'is_read' => false,
            'mentions' => $request->mentions,
        ];

        if ($request->chat_group_id) {
            $group = ChatGroup::find($request->chat_group_id);
            if (!$group) return response()->json(['error' => 'Group not found'], 404);

            if ($group->type !== 'community' && !$group->members()->where('user_id', Auth::id())->exists() && !$currentUser->hasRole('admin')) {
                return response()->json(['error' => 'Not a member'], 403);
            }

            $data['chat_group_id'] = $group->id;
            $data['receiver_id'] = null;

        } else {
            $data['receiver_id'] = $request->receiver_id;
            $data['chat_group_id'] = null;
        }

        $message = ChatMessage::create($data);

        User::where('id', Auth::id())->update(['last_active_at' => now()]);

        return response()->json($message->load('sender:id,name,avatar_path'));
    }

    /**
     * Poll for new messages.
     */
    public function checkNewMessages(Request $request)
    {
        $lastCheck = $request->input('last_check');
        $currentUser = Auth::user();
        if (!$currentUser) return response()->json([]);

        // 1. Direct Messages
        $dmQuery = ChatMessage::where('receiver_id', $currentUser->id)
            ->where('is_read', false);

        if ($lastCheck) {
            $dmQuery->where('created_at', '>', $lastCheck);
        }

        $newDMs = $dmQuery->with('sender:id,name,avatar_path')->get();

        // 2. Group Messages
        $groupQuery = ChatMessage::query()
            ->where('sender_id', '!=', $currentUser->id);

        if ($currentUser->hasRole('admin')) {
            // Admin sees messages from ALL groups
            $groupQuery->whereNotNull('chat_group_id');
        } else {
            // Non-admin sees messages only from their groups (and community)
            $communityGroupId = ChatGroup::where('type', 'community')->value('id');
            $myGroupIds = DB::table('chat_group_members')->where('user_id', $currentUser->id)->pluck('chat_group_id')->toArray();
            if ($communityGroupId) {
                $myGroupIds[] = $communityGroupId;
            }
            $groupQuery->whereIn('chat_group_id', $myGroupIds);
        }

        if ($lastCheck) {
            $groupQuery->where('created_at', '>', $lastCheck);
        } else {
             $groupQuery->where('created_at', '>', now()->subSeconds(10));
        }

        $newGroupMsgs = $groupQuery->with('sender:id,name,avatar_path')->get();

        $allMessages = $newDMs->merge($newGroupMsgs);

        return response()->json([
            'messages' => $allMessages,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

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
     * Update Group Profile (Name/Avatar).
     */
    public function updateGroupProfile(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:chat_groups,id',
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $group = ChatGroup::find($request->group_id);
        $currentUser = Auth::user();

        // Permission: Admin or Creator
        if ($group->created_by != $currentUser->id && !$currentUser->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $group->name = $request->name;

        if ($request->hasFile('avatar')) {
             if ($group->avatar_path && Storage::disk('public')->exists($group->avatar_path)) {
                Storage::disk('public')->delete($group->avatar_path);
            }
            $path = $request->file('avatar')->store('chat_group_avatars', 'public');
            $group->avatar_path = $path;
        }

        $group->save();

        return response()->json([
            'success' => true,
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'avatar_url' => $group->avatar_path ? Storage::disk('public')->url($group->avatar_path) : ($group->type === 'community' ? '/images/community-icon.png' : '/images/group-icon.png')
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
            'members' => 'nullable|array',
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
        $chatGroupId = $request->input('chat_group_id');
        $currentUser = Auth::user();

        if (!$query) return response()->json([]);

        // If chat_group_id is provided, search within group members
        if ($chatGroupId) {
            $group = ChatGroup::find($chatGroupId);
            if (!$group) return response()->json([]);

            // Security Check: Is user allowed to see this group?
            if ($group->type !== 'community' && !$group->members()->where('user_id', $currentUser->id)->exists() && !$currentUser->hasRole('admin')) {
                return response()->json([]); // Or 403, but empty list is safer for search
            }

            if ($group->type === 'community') {
                // Community searches all eligible staff users (admin, staff, etc)
                $users = User::where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->whereHas('roles', function($r) {
                        $r->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
                    })
                    ->limit(20)
                    ->get(['id', 'name', 'avatar_path']);
            } else {
                // Private group: search members
                $users = $group->members()
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->limit(20)
                    ->get(['users.id', 'users.name', 'users.avatar_path']);
            }
        } else {
             // Fallback to global search (existing behavior)
            $users = User::where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->whereHas('roles', function($r) {
                    $r->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
                })
                ->limit(10)
                ->get(['id', 'name', 'avatar_path']);
        }

        // Transform for frontend
        $results = $users->map(function($u) {
            return [
                'id' => $u->id,
                'value' => $u->name,
                'avatar_url' => $u->avatar_url
            ];
        });

        return response()->json($results);
    }
}

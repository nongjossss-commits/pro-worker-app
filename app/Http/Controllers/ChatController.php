<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
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
        // UNLESS they also hold a privileged role (Admin, Staff, Caretaker, Delegate)
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json([], 403);
        }

        // Permission Check: User must have 'use-chat' permission OR be in a privileged role explicitly
        if (!$currentUser->can('use-chat') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json([], 403);
        }

        // Base Query: Exclude current user and ensure active status
        $query = User::where('id', '!=', $currentUser->id)
                     ->where('status', 'active');

        // --- Access Control Logic ---
        // Allow Admin, Staff, Caretaker, and Delegate to chat with each other
        if ($currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            // Admin, Staff, Caretaker, Delegate can see each other.
            // Pure Employers are BLOCKED, so they are removed from visibility.

            $query->where(function($q) {
                // Mutual Visibility: Admin, Staff, Caretaker, Delegate see each other
                $q->whereHas('roles', function($r) {
                    $r->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
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

        // --- V3: Add Community Room ---
        $communityRoom = ChatRoom::where('type', 'community')->first();
        if ($communityRoom && $currentUser->hasAnyRole(['admin', 'staff'])) {
             $communityContact = [
                'id' => 'room_' . $communityRoom->id,
                'name' => $communityRoom->name,
                'avatar_url' => asset('images/community-icon.png'),
                'position_title' => $communityRoom->description,
                'is_online' => true,
                'unread_count' => 0, // Implement unread logic for rooms later
                'last_message_time' => null,
                'is_room' => true,
            ];
            // Add community room to the top of the list
            $contacts->prepend($communityContact);
        }

        return response()->json($contacts);
    }

    /**
     * Fetch messages between current user and specific user.
     */
    public function fetchMessages($id)
    {
        $currentUserId = Auth::id();
        $currentUser = Auth::user();

        // --- V3: Handle Room vs User ---
        if (str_starts_with($id, 'room_')) {
            $roomId = substr($id, 5);
            $room = ChatRoom::find($roomId);

            if (!$room || !$room->users->contains($currentUserId)) {
                return response()->json(['error' => 'Room not found or access denied'], 404);
            }

            $messages = $room->messages()->with('sender:id,name,avatar_path')->orderBy('created_at', 'asc')->get();

            // Mark messages as read for this user in this room (logic to be implemented)
            // This would require a read receipts table like `chat_message_read_status`

            return response()->json($messages);
        }

        // --- Original User-to-User Logic ---
        $userId = $id;

        // 1. STRICT BLOCK: Employers cannot access chat
        // UNLESS they also hold a privileged role
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Access Denied: Employers are blocked from chat'], 403);
        }

        // Basic Permission Check
        if (!$currentUser->can('use-chat') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // --- Security Check: Validate relationship logic again ---
        $canChat = false;
        $targetUser = User::with(['roles', 'employer'])->find($userId);

        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Prevent chatting with blocked employers (pure employers)
        if ($targetUser->hasRole('employer') && !$targetUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Cannot chat with employer (Blocked)'], 403);
        }

        // Logic for Admin/Staff/Caretaker/Delegate
        if ($currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            // Can chat with any Admin/Staff/Caretaker/Delegate
            if ($targetUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
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
            'receiver_id' => 'required|string', // Changed to string to handle room IDs
            'message' => 'nullable|string',
            'context_data' => 'nullable|array'
        ]);

        $currentUser = Auth::user();
        $receiverId = $request->receiver_id;

        // --- V3: Handle Room vs User ---
        if (str_starts_with($receiverId, 'room_')) {
            $roomId = substr($receiverId, 5);
            $room = ChatRoom::find($roomId);

            if (!$room || !$room->users->contains($currentUser->id)) {
                return response()->json(['error' => 'Room not found or access denied'], 404);
            }

            $message = ChatMessage::create([
                'sender_id' => $currentUser->id,
                'chat_room_id' => $roomId,
                'message' => $request->message ?? '',
                'context_data' => $request->context_data,
            ]);

            User::where('id', Auth::id())->update(['last_active_at' => now()]);

            return response()->json($message->load('sender:id,name,avatar_path'));
        }

        // --- Original User-to-User Logic ---
        $request->merge(['receiver_id' => (int)$receiverId]); // Ensure it's an integer for validation
         $request->validate(['receiver_id' => 'exists:users,id']);


        // 1. STRICT BLOCK: Employers cannot access chat
        // UNLESS they also hold a privileged role
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Access Denied'], 403);
        }

        if (!$currentUser->can('use-chat') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Re-verify permission
        $userId = $request->receiver_id;
        $canChat = false;
        $targetUser = User::with(['roles', 'employer'])->find($userId);

        if ($targetUser) {
            // Prevent chatting with blocked employers (pure employers)
            if ($targetUser->hasRole('employer') && !$targetUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
                 return response()->json(['error' => 'Access Denied: Target is blocked'], 403);
            }

            if ($currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
                if ($targetUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
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
     * Poll for new messages (both 1-on-1 and rooms).
     */
    public function checkNewMessages(Request $request)
    {
        $user = Auth::user();
        $currentUserId = $user->id;

        // --- User Activity Update ---
        if (!$user->last_active_at || $user->last_active_at->diffInMinutes(now()) >= 5) {
            $user->update(['last_active_at' => now()]);
        }

        // --- Fetch 1-on-1 Messages ---
        $lastCheck = $request->input('last_check');
        $dmQuery = ChatMessage::where('receiver_id', $currentUserId)->where('is_read', false);
        if ($lastCheck) {
            $dmQuery->where('created_at', '>', $lastCheck);
        }
        $newDirectMessages = $dmQuery->with('sender:id,name,avatar_path')->limit(20)->get();

        // --- Fetch Room Messages ---
        $roomIds = $request->input('rooms', []);
        $lastRoomMessageId = (int) $request->input('last_room_message_id', 0);
        $newRoomMessages = collect();

        if (!empty($roomIds)) {
            $accessibleRoomIds = $user->chatRooms()->whereIn('chat_room_id', $roomIds)->pluck('chat_room_id');
            if ($accessibleRoomIds->isNotEmpty()) {
                $newRoomMessages = ChatMessage::whereIn('chat_room_id', $accessibleRoomIds)
                    ->where('id', '>', $lastRoomMessageId)
                    ->where('sender_id', '!=', $currentUserId)
                    ->with('sender:id,name,avatar_path')
                    ->orderBy('id', 'asc')
                    ->limit(50)
                    ->get();
            }
        }

        return response()->json([
            'direct_messages' => $newDirectMessages,
            'room_messages' => $newRoomMessages,
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

    /**
     * Create a new chat room.
     */
    public function createRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        $user = Auth::user();

        $room = ChatRoom::create([
            'name' => $request->name,
            'created_by' => $user->id,
            'type' => 'group',
        ]);

        // Attach the creator and the selected users
        $userIds = collect($request->users)->push($user->id)->unique();
        $room->users()->sync($userIds);

        return response()->json([
            'success' => true,
            'room' => [
                'id' => 'room_' . $room->id,
                'name' => $room->name,
                'avatar_url' => asset('images/community-icon.png'), // Default icon
                'position_title' => $room->description,
                'is_online' => true,
                'unread_count' => 0,
                'last_message_time' => null,
                'is_room' => true,
            ]
        ], 201);
    }

    /**
     * Get list of default chat backgrounds.
     */
    public function getBackgrounds()
    {
        $files = [
            'bg1.png', 'bg2.png', 'bg3.png', 'bg4.png', 'bg5.png',
            'bg6.png', 'bg7.png', 'bg8.png', 'bg9.png', 'bg10.png',
        ];

        $urls = array_map(function ($file) {
            return asset('images/chat-bgs/' . $file);
        }, $files);

        return response()->json($urls);
    }


    /**
     * Proxy for Giphy API to protect the API key.
     */
    public function giphyProxy(Request $request)
    {
        $request->validate(['query' => 'nullable|string|max:100']);
        $query = $request->input('query');
        $apiKey = config('services.giphy.key'); // Assumes key is in config/services.php

        if (!$apiKey) {
            return response()->json(['error' => 'Giphy API key not configured'], 500);
        }

        $endpoint = $query
            ? 'https://api.giphy.com/v1/gifs/search'
            : 'https://api.giphy.com/v1/gifs/trending';

        $response = Http::get($endpoint, [
            'api_key' => $apiKey,
            'q' => $query,
            'limit' => 12,
        ]);

        return $response->json();
    }
}

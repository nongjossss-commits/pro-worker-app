<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
        if (!$currentUser->can('use-chat')) {
            return response()->json(['error' => 'Chat access denied.'], 403);
        }

        $query = User::where('id', '!=', $currentUser->id)->where('status', 'active');

        if ($currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            $query->whereHas('roles', function($r) {
                $r->whereIn('name', ['admin', 'staff', 'caretaker', 'delegate']);
            });
        } else {
            // Employers and other roles should not see anyone.
            return response()->json([]);
        }

        $contacts = $query->select('id', 'name', 'avatar_path', 'position_title', 'last_active_at')
            ->withCount(['sentChatMessages as unread_count' => function ($query) use ($currentUser) {
                $query->where('receiver_id', $currentUser->id)->where('is_read', false);
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
                    'last_message_time' => null
                ];
            });

        $rooms = $currentUser->chatRooms()->get();
        foreach ($rooms as $room) {
            $contacts->prepend([
                'id' => 'room_' . $room->id,
                'name' => $room->name,
                'avatar_url' => asset('images/community-icon.png'),
                'position_title' => $room->description ?? 'Group Chat',
                'is_online' => true,
                'unread_count' => 0,
                'last_message_time' => null,
                'is_room' => true,
            ]);
        }

        return response()->json($contacts);
    }

    /**
     * Fetch messages between current user and specific user or room.
     */
    public function fetchMessages($id)
    {
        $currentUserId = Auth::id();
        $currentUser = Auth::user();

        if (str_starts_with($id, 'room_')) {
            $roomId = substr($id, 5);
            $room = ChatRoom::find($roomId);
            if (!$room || !$room->users->contains($currentUserId)) {
                return response()->json(['error' => 'Room not found or access denied'], 404);
            }
            $messages = $room->messages()->with('sender:id,name,avatar_path')->orderBy('created_at', 'asc')->get();
            return response()->json($messages);
        }

        $userId = $id;
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Access Denied: Employers are blocked from chat'], 403);
        }

        if (!$currentUser->can('use-chat')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $targetUser = User::find($userId);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($targetUser->hasRole('employer') && !$targetUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Cannot chat with employer (Blocked)'], 403);
        }

        $canChat = false;
        if ($currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            if ($targetUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
                $canChat = true;
            }
        }

        if (!$canChat) {
             return response()->json(['error' => 'Access Denied'], 403);
        }

        $messages = ChatMessage::where(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)->where('receiver_id', $userId);
            })->orWhere(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->with('sender:id,name,avatar_path')
            ->get();

        ChatMessage::where('sender_id', $userId)->where('receiver_id', $currentUserId)->where('is_read', false)->update(['is_read' => true]);

        return response()->json($messages);
    }

    /**
     * Send a message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|string',
            'message' => 'nullable|string',
            'context_data' => 'nullable|array'
        ]);

        $currentUser = Auth::user();
        if ($currentUser->hasRole('employer') && !$currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
            return response()->json(['error' => 'Access Denied'], 403);
        }

        if (!$currentUser->can('use-chat')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $receiverId = $request->receiver_id;

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

        } else {
            $userId = (int)$receiverId;
            $targetUser = User::find($userId);
            if ($targetUser->hasRole('employer') && !$targetUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
                 return response()->json(['error' => 'Access Denied: Target is blocked'], 403);
            }

            $canChat = false;
            if ($targetUser) {
                if ($currentUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
                    if ($targetUser->hasRole(['admin', 'staff', 'caretaker', 'delegate'])) {
                        $canChat = true;
                    }
                }
            }
            if (!$canChat) {
                return response()->json(['error' => 'Access Denied'], 403);
            }

            $message = ChatMessage::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $userId,
                'message' => $request->message ?? '',
                'context_data' => $request->context_data,
                'is_read' => false,
            ]);
        }

        User::where('id', Auth::id())->update(['last_active_at' => now()]);
        return response()->json($message->load('sender:id,name,avatar_path'));
    }

    public function checkNewMessages(Request $request)
    {
        $user = Auth::user();
        $currentUserId = $user->id;
        $user->update(['last_active_at' => now()]);

        $lastCheck = $request->input('last_check');
        $dmQuery = ChatMessage::where('receiver_id', $currentUserId)->where('is_read', false);
        if ($lastCheck) {
            $dmQuery->where('created_at', '>', $lastCheck);
        }
        $newDirectMessages = $dmQuery->with('sender:id,name,avatar_path')->get();

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
                    ->get();
            }
        }

        return response()->json([
            'direct_messages' => $newDirectMessages,
            'room_messages' => $newRoomMessages,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    public function uploadFile(Request $request)
    {
        $request->validate(['file' => 'required|file|max:10240']);
        $path = $request->file('file')->store('chat_attachments', 'public');
        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'name' => $request->file('file')->getClientOriginalName(),
            'type' => str_starts_with($request->file('file')->getMimeType(), 'image/') ? 'image' : 'file',
            'mime' => $request->file('file')->getMimeType()
        ]);
    }

    public function giphyProxy(Request $request)
    {
        $request->validate(['query' => 'nullable|string|max:100']);
        $query = $request->input('query');
        $apiKey = config('services.giphy.key');

        if (!$apiKey || $apiKey === 'your_giphy_api_key_here') {
            return response()->json(['error' => 'Giphy API key not configured'], 500);
        }

        $endpoint = $query ? 'https://api.giphy.com/v1/gifs/search' : 'https://api.giphy.com/v1/gifs/trending';
        $response = Http::get($endpoint, ['api_key' => $apiKey, 'q' => $query, 'limit' => 24, 'rating' => 'g']);

        return $response->json();
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
        $user->fill($request->only(['name', 'position_title', 'bio']));

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();
        return response()->json(['success' => true, 'user' => $user->only(['name', 'avatar_url', 'position_title', 'bio'])]);
    }

    public function createRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        $user = Auth::user();
        $room = ChatRoom::create(['name' => $request->name, 'created_by' => $user->id, 'type' => 'group']);
        $userIds = collect($request->users)->push($user->id)->unique();
        $room->users()->sync($userIds);

        return response()->json(['success' => true, 'room' => $room], 201);
    }

    public function getBackgrounds()
    {
        $files = glob(public_path('images/chat-bgs/*.png'));
        $urls = collect($files)->map(fn($file) => asset('images/chat-bgs/' . basename($file)));
        return response()->json($urls);
    }
}

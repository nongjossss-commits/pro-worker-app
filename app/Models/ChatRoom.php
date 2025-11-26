<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'type',
    ];

    /**
     * The user who created the chat room.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The users that belong to the chat room.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_user');
    }

    /**
     * The messages sent to the chat room.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}

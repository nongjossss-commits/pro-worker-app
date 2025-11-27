<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // 'community', 'private_group'
        'created_by',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_members', 'chat_group_id', 'user_id')
                    ->withPivot('role', 'joined_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

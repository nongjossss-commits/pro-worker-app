<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChatGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // 'community', 'private_group'
        'created_by',
        'avatar_path',
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return $this->type === 'community' ? '/images/community-icon.png' : '/images/group-icon.png';
    }

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

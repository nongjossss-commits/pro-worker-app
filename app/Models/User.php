<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'avatar_path',
        'position_title',
        'bio',
        'last_active_at',
        'is_ticket_hidden', // V2.5.1: For hiding employer job box
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar_path) {
            return Storage::disk('public')->url($this->avatar_path);
        }
        // Return a default placeholder or a generated avatar service URL
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    // Ensure return type hinting is applied
    public function employer(): HasOne
    {
        return $this->hasOne(Employer::class);
    }

    // --- START: V2.4 Corrected Relationships ---
    /**
     * Get the tickets submitted by this user (if they are an Employer).
     */
    public function submittedTickets(): HasMany
    {
        // MUST use 'employer_user_id'
        return $this->hasMany(JobTicket::class, 'employer_user_id');
    }

    /**
     * Get the tickets assigned to this user (if they are Staff/Admin).
     */
    public function assignedTickets(): HasMany
    {
        // MUST use 'assigned_staff_id'
        return $this->hasMany(JobTicket::class, 'assigned_staff_id');
    }

    /**
     * Get the messages sent by this user in the ticket system.
     */
    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }
    // --- END: V2.4 Corrected Relationships ---

    // --- Chat Relationships ---
    public function sentChatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function receivedChatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id');
    }
}

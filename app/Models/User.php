<?php

namespace App\Models;

// ... (Existing imports)
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\LogActivity;
// Ensure these relationship imports exist or add them
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogActivity;

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
        ];
    }


    // Ensure return type hinting is applied
    public function employer(): HasOne
    {
        return $this->hasOne(Employer::class);
    }

    // --- START: V2.4 Corrected Relationships ---
    // CRITICAL: Remove the incorrect `jobTickets()` method if it exists in the file.
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
}

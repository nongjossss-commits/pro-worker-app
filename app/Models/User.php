<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogActivity;
    // Alias Spatie's hasPermissionTo (inherited from HasPermissions via HasRoles)
    // so our override below can still delegate to the original logic. We override
    // hasPermissionTo rather than checkPermissionTo because Spatie's own
    // checkPermissionTo internally calls hasPermissionTo, and PdfTemplatePolicy
    // (and any future callers) invoke hasPermissionTo directly — hooking the
    // deeper method covers both paths in one place.
    use HasRoles {
        hasPermissionTo as protected spatieHasPermissionTo;
    }

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
        'labor_team_id',
        'labor_access_granted',
        'revoked_permissions',
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
            'labor_access_granted' => 'boolean',
            'revoked_permissions' => 'array',
        ];
    }

    /**
     * Whether this user has had the given permission explicitly revoked,
     * overriding whatever their role would otherwise grant.
     * Consulted from checkPermissionTo() below so the revoke wins over
     * Spatie's role/direct-grant check.
     */
    public function hasRevoked(string $permission): bool
    {
        return in_array($permission, $this->revoked_permissions ?? [], true);
    }

    /**
     * Enforce per-user revocations before Spatie's role/direct-grant check.
     *
     * Spatie's PermissionRegistrar registers its own Gate::before that calls
     * $user->checkPermissionTo() → hasPermissionTo(). Because Spatie's
     * provider boots BEFORE AppServiceProvider, that Gate::before returns
     * true first for anything the user has via role and Gate never reaches
     * the revoke logic in AppServiceProvider::boot(). The revoke UI persisted
     * users.revoked_permissions but had no runtime effect — caretakers whose
     * "edit-employees" box was unticked still saw and clicked edit buttons.
     *
     * Overriding hasPermissionTo intercepts at the same layer Spatie's
     * gate consults AND at the layer PdfTemplatePolicy hits directly, so
     * both @can/Gate paths AND direct hasPermissionTo() callers respect
     * the revoke.
     *
     * Admin/super-admin skip the revoke gate entirely — they're supposed
     * to bypass everything (mirrors the Gate::before in AppServiceProvider).
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if ($this->hasRole('admin') || $this->hasRole('super-admin')) {
            return $this->spatieHasPermissionTo($permission, $guardName);
        }

        if (is_string($permission) && $this->hasRevoked($permission)) {
            return false;
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    /**
     * The Pro Walker Labor team this user belongs to (role `labor-team` only).
     */
    public function laborTeam(): BelongsTo
    {
        return $this->belongsTo(LaborTeam::class, 'labor_team_id');
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

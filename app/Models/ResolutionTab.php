<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ResolutionTab extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'slug',
        'sort_order',
        'is_default',
        'badge_enabled',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'badge_enabled' => 'boolean',
    ];

    // --- Scopes ---

    public function scopeRegistration($query)
    {
        return $query->where('type', 'registration');
    }

    public function scopeRenewal($query)
    {
        return $query->where('type', 'renewal');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    // --- Relationships ---

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function employers()
    {
        return $this->belongsToMany(Employer::class, 'employer_resolution_tab')
                    ->withPivot('resolution_status', 'resolution_note')
                    ->withTimestamps();
    }

    public function steps()
    {
        return $this->hasMany(RegistrationStep::class);
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function systemSettings()
    {
        return $this->hasMany(SystemSetting::class);
    }

    public function notificationSettings()
    {
        return $this->hasMany(NotificationSetting::class);
    }

    // --- Helpers ---

    /**
     * Get the status prefix for employees in this tab type.
     * e.g. 'registration' => ['registration_pending', 'registration_completed', 'registration_cancelled']
     */
    public function getEmployeeStatuses(): array
    {
        $prefix = $this->type;
        return [
            "{$prefix}_pending",
            "{$prefix}_completed",
            "{$prefix}_cancelled",
        ];
    }

    /**
     * Check if this tab is within the 7-day cooldown period (soft deleted but not yet purgeable).
     */
    public function isInCooldown(): bool
    {
        return $this->trashed() && $this->deleted_at->diffInDays(now()) < 7;
    }

    /**
     * Check if this tab can be permanently deleted (past 7-day cooldown).
     */
    public function isPurgeable(): bool
    {
        return $this->trashed() && $this->deleted_at->diffInDays(now()) >= 7;
    }

    /**
     * Get the number of days remaining in cooldown.
     */
    public function getCooldownDaysRemainingAttribute(): int
    {
        if (!$this->trashed()) return 0;
        return (int) max(0, 7 - floor($this->deleted_at->diffInDays(now())));
    }

    // --- Boot ---

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tab) {
            if (empty($tab->slug)) {
                $tab->slug = Str::slug($tab->name) . '-' . Str::random(6);
            }

            // Auto-assign sort_order
            if ($tab->sort_order === 0 || $tab->sort_order === null) {
                $maxOrder = static::where('type', $tab->type)->max('sort_order') ?? 0;
                $tab->sort_order = $maxOrder + 1;
            }
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Super Admin-manageable "ประเภทใบอนุญาตทำงาน" (Work Permit / MOU Group
 * type) list — see the create_work_permit_types_table migration docblock
 * for the full design rationale (why this doesn't touch how the value is
 * stored on Employee, and why `slug` is separate from the editable `name`).
 */
class WorkPermitType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * How many employees currently carry this type's name in
     * workPermitMOUGroup — used to block deletion so a removed type never
     * leaves employees pointing at an option that's vanished from the
     * dropdown.
     */
    public function usageCount(): int
    {
        return Employee::where('workPermitMOUGroup', $this->name)->count()
            + SalesLeadEmployee::where('workPermitMOUGroup', $this->name)->count();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($type) {
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name) . '-' . Str::random(6);
            }
            if ($type->sort_order === 0 || $type->sort_order === null) {
                $maxOrder = static::max('sort_order') ?? 0;
                $type->sort_order = $maxOrder + 1;
            }
        });
    }
}

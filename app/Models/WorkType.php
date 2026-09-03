<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `allow_multiple_orders` and `show_mou_fields` replace what used to be
 * hardcoded slug checks (`in_array($workType->slug, ['mou', 'mou_import'])`)
 * scattered across WorkflowController/ImportEmployeeController and the
 * workflow/production Blade views — deliberately 2 separate columns, not
 * one, so a Super Admin creating a new custom tab can opt a tab into
 * "one employer can have multiple cards" (`allow_multiple_orders`) without
 * that tab automatically inheriting the MOU-specific nationality/gender-
 * count/import-type fields too. `show_mou_fields` is NOT exposed in the
 * tab create/edit UI at all — it stays true only for the pre-existing MOU
 * Import system tab (set once in the migration that added these columns).
 *
 * Uses SoftDeletes so deleting a custom tab (WorkTypeController::destroy())
 * just hides it — every ProductionOrder/ProductionItem ever processed
 * under it stays completely untouched in the database.
 */
class WorkType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_system',
        'allow_multiple_orders',
        'show_mou_fields',
        'order',
        'notify_days_advance',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'allow_multiple_orders' => 'boolean',
        'show_mou_fields' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(WorkTypeStep::class)->orderBy('order');
    }

    public function workflowSteps()
    {
        return $this->hasMany(WorkTypeStep::class)
            ->where(function ($query) {
                $query->whereNull('stage')
                      ->orWhere('stage', '!=', 'preparation');
            })
            ->orderBy('order');
    }

    public function preparationSteps()
    {
        return $this->hasMany(WorkTypeStep::class)
            ->where('stage', 'preparation')
            ->orderBy('order');
    }

    public function orders()
    {
        return $this->hasMany(ProductionOrder::class);
    }
}

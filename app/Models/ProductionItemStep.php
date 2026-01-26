<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductionItemStep extends Pivot
{
    protected $table = 'production_item_step';
    protected $fillable = ['production_item_id', 'work_type_step_id', 'completed_at', 'completed_by'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group', 'resolution_tab_id'];

    public function resolutionTab()
    {
        return $this->belongsTo(\App\Models\ResolutionTab::class);
    }
}

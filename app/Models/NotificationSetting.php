<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_type',
        'days_before_expiry',
        'is_enabled',
        'resolution_tab_id',
    ];

    public function resolutionTab()
    {
        return $this->belongsTo(\App\Models\ResolutionTab::class);
    }
}

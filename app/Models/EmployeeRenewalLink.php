<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A Registration-Resolution employee's independent "also usable in this
 * Renewal tab" state — see the migration's docblock and
 * EmployeeObserver::syncRenewalStatus() for the full rationale. This never
 * overwrites Employee.status/resolution_tab_id; it's a parallel status
 * track for the same physical person.
 */
class EmployeeRenewalLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'resolution_tab_id',
        'status',
        'resolution_completed_at',
        'resolution_settings_applied',
    ];

    protected $casts = [
        'resolution_completed_at' => 'datetime',
        'resolution_settings_applied' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function resolutionTab()
    {
        return $this->belongsTo(ResolutionTab::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One employee's appointment, scoped independently per ResolutionTab — see
 * the create_employee_appointments_table migration's docblock. Mirrors
 * EmployeeRenewalLink's exact shape/rationale: a shared table, one row per
 * (employee, resolution_tab_id) pair, so the same physical person can have
 * a completely independent appointment in Registration Resolution vs. any
 * Renewal Resolution tab (or between separate, user-created Renewal tabs),
 * instead of one un-scoped set of columns shared by every context that
 * touches the Employee row.
 */
class EmployeeAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'resolution_tab_id',
        'appointment_date',
        'appointment_location',
        'appointment_completed_at',
        'appointment_updated_by',
        'appointment_updated_at',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'appointment_completed_at' => 'datetime',
        'appointment_updated_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function resolutionTab()
    {
        return $this->belongsTo(ResolutionTab::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'appointment_updated_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One employee's "เลขรับคำขอ" (request-received number), scoped
 * independently per ResolutionTab — see the
 * create_employee_request_numbers_table migration's docblock. Mirrors
 * EmployeeAppointment's exact shape/rationale: a shared table, one row per
 * (employee, resolution_tab_id) pair, so the same physical person can have a
 * completely independent request number in Registration Resolution vs. any
 * Renewal Resolution tab (or between separate, user-created Renewal tabs),
 * instead of one un-scoped column shared by every context that touches the
 * Employee row.
 */
class EmployeeRequestNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'resolution_tab_id',
        'request_number',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One employee's team assignment within a single ResolutionTab — see the
 * create_employee_team_assignments_table migration's docblock. A row's
 * existence means that employee is on that team for that specific tab;
 * removing them from a team deletes the row.
 */
class EmployeeTeamAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'resolution_tab_id',
        'employer_id',
        'team_name',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function resolutionTab()
    {
        return $this->belongsTo(ResolutionTab::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }
}

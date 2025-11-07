<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date',
        'passport_issue_date' => 'date',
        'passport_expiry_date' => 'date',
        'visa_issue_date' => 'date',
        'visa_expiry_date' => 'date',
        'work_permit_issue_date' => 'date',
        'work_permit_expiry_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'terminated_date' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope('userScope', function (Builder $builder) {
            $user = Auth::user();
            if ($user) {
                if ($user->hasRole('admin')) {
                    // Admin can see all employees, do not apply any scope.
                    return;
                } elseif ($user->hasRole('employer')) {
                    // Employer specific logic
                    $builder->where('employer_id', $user->employer->id);
                } elseif ($user->hasRole('staff')) {
                    // Staff specific logic
                    $jobOwnerId = $user->job_owner_id; // Assuming staff user has job_owner_id
                    $builder->whereHas('employer', function ($query) use ($jobOwnerId) {
                        $query->where('job_owner_id', $jobOwnerId);
                    });
                }
            }
        });
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function jobTickets()
    {
        return $this->belongsToMany(JobTicket::class, 'job_ticket_employee');
    }
}

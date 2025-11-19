<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Employee $employee)
    {
        // Users with 'manage-tickets' permission (Admins, Staff) can view any employee.
        if ($user->can('manage-tickets')) {
            return true;
        }

        // Employers can only view their own employees.
        // Assumes the User model has a relationship to an Employer model.
        return $user->id === $employee->employer->user_id;
    }
}

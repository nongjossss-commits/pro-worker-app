<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Employee $employee)
    {
        // Admins/Staff can view any employee.
        if ($user->can('manage-tickets')) {
            return true;
        }

        // Employers can only view their own employees.
        return $user->id === $employee->employer->user_id;
    }

    /**
     * Determine whether the user can terminate or reinstate an employee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function terminate(User $user, Employee $employee)
    {
        // Admins/Staff can always terminate.
        if ($user->can('terminate-employees')) {
            return true;
        }

        // Employers can terminate their own employees.
        return $user->id === $employee->employer->user_id;
    }

    /**
     * Determine whether the user can transfer an employee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function transfer(User $user, Employee $employee)
    {
        // First, check the base permission for terminating/transferring.
        if ($user->can('terminate-employees')) {
            return true;
        }

        // If not an admin/staff, check if they are the employer of this specific employee.
        // The `Employee` model is passed to this method, so we can check ownership.
        return $user->id === $employee->employer->user_id;
    }
}

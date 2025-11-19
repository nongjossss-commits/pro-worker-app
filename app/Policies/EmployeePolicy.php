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
    public function transfer(User $user, Employee $employee = null)
    {
        // For admins/staff, this permission check is enough for both single and bulk actions.
        if ($user->can('terminate-employees')) {
            return true;
        }

        // If an employee instance is provided (single transfer), check ownership.
        if ($employee) {
            return $user->id === $employee->employer->user_id;
        }

        // For a bulk action ($employee is null) by a non-admin (e.g., an employer),
        // we cannot check ownership of individual employees here.
        // We allow the request to proceed to the controller, which is responsible
        // for verifying that the user owns all employees in the request.
        return true;
    }
}

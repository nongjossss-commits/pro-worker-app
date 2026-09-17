<?php

namespace App\Traits;

use App\Models\ResolutionTab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for controllers that work with Resolution Tabs (Registration / Renewal).
 *
 * Provides helpers to resolve the current tab from route parameters,
 * scope queries to the current tab, and pass tab context to views.
 */
trait HasResolutionTab
{
    protected ?ResolutionTab $currentTab = null;

    /**
     * Resolve the current tab from the route parameter or find the first tab of the given type.
     */
    protected function resolveTab($tabIdOrSlug, string $type): ResolutionTab
    {
        if ($tabIdOrSlug instanceof ResolutionTab) {
            $this->currentTab = $tabIdOrSlug;
            return $this->currentTab;
        }

        $this->currentTab = ResolutionTab::where('type', $type)
            ->where(function ($q) use ($tabIdOrSlug) {
                $q->where('id', $tabIdOrSlug)
                  ->orWhere('slug', $tabIdOrSlug);
            })
            ->firstOrFail();

        return $this->currentTab;
    }

    /**
     * Get the first (default) tab of a given type for redirects.
     */
    protected function getDefaultTab(string $type): ResolutionTab
    {
        return ResolutionTab::where('type', $type)
            ->ordered()
            ->firstOrFail();
    }

    /**
     * Get all tabs of a given type (for the tab bar).
     */
    protected function getAllTabs(string $type)
    {
        return ResolutionTab::where('type', $type)
            ->ordered()
            ->get();
    }

    /**
     * Scope an Employee query to the current tab.
     */
    protected function scopeEmployeesByTab(Builder $query): Builder
    {
        if ($this->currentTab) {
            $query->where('resolution_tab_id', $this->currentTab->id);
        }
        return $query;
    }

    /**
     * Scope an Employer query to only include employers with employees in the current tab.
     */
    protected function scopeEmployersByTab(Builder $query): Builder
    {
        if ($this->currentTab) {
            $query->whereHas('employees', function ($q) {
                $q->where('resolution_tab_id', $this->currentTab->id);
            });
        }
        return $query;
    }

    /**
     * Get registration steps scoped to the current tab.
     */
    protected function getTabSteps()
    {
        if (!$this->currentTab) {
            return collect();
        }

        return \App\Models\RegistrationStep::where('resolution_tab_id', $this->currentTab->id)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get the employee status strings for the current tab type.
     * e.g., ['registration_pending', 'registration_completed', 'registration_cancelled']
     */
    protected function getTabStatuses(): array
    {
        if (!$this->currentTab) {
            return [];
        }

        return $this->currentTab->getEmployeeStatuses();
    }

    /**
     * Return shared view data for the tab bar and current tab context.
     */
    protected function getTabViewData(string $type): array
    {
        return [
            'currentTab' => $this->currentTab,
            'allTabs' => $this->getAllTabs($type),
        ];
    }

    /**
     * Overwrite each Employee model's own (legacy, un-scoped)
     * appointment_date/appointment_location/appointment_completed_at/
     * appointment_updated_by/appointment_updated_at attributes IN-MEMORY
     * with the appointment scoped to $resolutionTabId — never persisted,
     * this Employee instance is never save()'d after this call. Lets every
     * existing read of those attributes (Blade card partials, exports,
     * PHP-side sorting/grouping) stay correct for whichever
     * Registration/Renewal tab is actually being viewed, without having to
     * individually convert every read site to the new
     * EmployeeAppointment/employee_appointments table. See that model's
     * docblock for why appointments had to move off the Employee row.
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|iterable  $employees
     */
    protected function applyTabAppointments($employees, int $resolutionTabId): void
    {
        // Plain foreach (not collect($employees)->pluck(...)) deliberately
        // — $employees is sometimes a LengthAwarePaginator, and
        // collect()'ing one converts it via its Arrayable::toArray(),
        // which returns the pagination META array, not the item list.
        $ids = [];
        foreach ($employees as $employee) {
            if ($employee->id) {
                $ids[] = $employee->id;
            }
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            return;
        }

        $appointments = \App\Models\EmployeeAppointment::where('resolution_tab_id', $resolutionTabId)
            ->whereIn('employee_id', $ids)
            ->get()
            ->keyBy('employee_id');

        foreach ($employees as $employee) {
            $appointment = $appointments->get($employee->id);
            $employee->appointment_date = $appointment->appointment_date ?? null;
            $employee->appointment_location = $appointment->appointment_location ?? null;
            $employee->appointment_completed_at = $appointment->appointment_completed_at ?? null;
            $employee->appointment_updated_by = $appointment->appointment_updated_by ?? null;
            $employee->appointment_updated_at = $appointment->appointment_updated_at ?? null;
        }
    }

    /**
     * Same idea as applyTabAppointments() but for "เลขรับคำขอ" (request
     * number) — overwrite each Employee model's own (legacy, un-scoped)
     * registration_request_number/renewal_request_number attributes
     * IN-MEMORY with the value scoped to $resolutionTabId. Never persisted.
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|iterable  $employees
     */
    protected function applyTabRequestNumbers($employees, int $resolutionTabId): void
    {
        $ids = [];
        foreach ($employees as $employee) {
            if ($employee->id) {
                $ids[] = $employee->id;
            }
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            return;
        }

        $requestNumbers = \App\Models\EmployeeRequestNumber::where('resolution_tab_id', $resolutionTabId)
            ->whereIn('employee_id', $ids)
            ->get()
            ->keyBy('employee_id');

        foreach ($employees as $employee) {
            $value = $requestNumbers->get($employee->id)->request_number ?? null;
            $employee->registration_request_number = $value;
            $employee->renewal_request_number = $value;
        }
    }

    /**
     * Overwrite each Employee model's team_name attribute IN-MEMORY (a
     * transient attribute — there is no such column on `employees`) with
     * the team assignment scoped to $resolutionTabId. Never persisted.
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|iterable  $employees
     */
    protected function applyTabTeams($employees, int $resolutionTabId): void
    {
        $ids = [];
        foreach ($employees as $employee) {
            if ($employee->id) {
                $ids[] = $employee->id;
            }
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            return;
        }

        $assignments = \App\Models\EmployeeTeamAssignment::where('resolution_tab_id', $resolutionTabId)
            ->whereIn('employee_id', $ids)
            ->get()
            ->keyBy('employee_id');

        foreach ($employees as $employee) {
            $employee->team_name = $assignments->get($employee->id)->team_name ?? null;
        }
    }

    /**
     * Distinct team names already in use within this tab AND this employer,
     * for the "existing teams" chip list in the "จัดทีม" modal — queried
     * straight from the database instead of scraped from rendered HTML (as
     * Workflow's version does), since Registration/Renewal's employee list
     * is AJAX-paginated per employer and would never have every team
     * visible in the DOM at once.
     *
     * Scoped per employer on purpose (not tab-wide): team names are a
     * small, free-form vocabulary each employer defines for their own
     * batch of employees — Employer B opening this modal must never see
     * Employer A's "Team 1"/"Team 2"/"Team 3" as suggestions, even though
     * both sit in the same tab.
     */
    protected function getTabTeamNames(int $resolutionTabId, int $employerId): array
    {
        return \App\Models\EmployeeTeamAssignment::where('resolution_tab_id', $resolutionTabId)
            ->where('employer_id', $employerId)
            ->distinct()
            ->orderBy('team_name')
            ->pluck('team_name')
            ->all();
    }
}

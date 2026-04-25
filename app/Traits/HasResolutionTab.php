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
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Employee;
use App\Models\Employer;
use App\Observers\EmployeeObserver;
use App\Observers\EmployerObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Employee::observe(EmployeeObserver::class);
        Employer::observe(EmployerObserver::class);

        // Share incomplete employee count with specific views (layout)
        view()->composer('layouts.app', function ($view) {
            if (auth()->check()) {
                // 1. Incomplete Data Count (Admin/Staff only)
                if (auth()->user()->can('manage-tickets')) {
                    $incompleteCount = \App\Helpers\CompletenessHelper::getIncompleteCount();
                    $view->with('incompleteCount', $incompleteCount);
                }

                // 2. Notification Count (All users who can view notifications)
                if (auth()->user()->can('view-notifications')) {
                    $totalNotificationCount = \App\Helpers\NotificationHelper::getTotalNotificationCount();
                    $view->with('totalNotificationCount', $totalNotificationCount);
                }
            }
        });
    }
}

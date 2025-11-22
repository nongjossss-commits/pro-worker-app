<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\JobTicket;
use App\Observers\EmployeeObserver;
use App\Observers\EmployerObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;

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

        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);

        // Share incomplete employee count with specific views (layout)
        view()->composer('layouts.app', function ($view) {
            if (auth()->check()) {
                // 1. Incomplete Data Count & Admin Ticket Unread (Admin/Staff only)
                if (auth()->user()->can('manage-tickets')) {
                    $incompleteCount = \App\Helpers\CompletenessHelper::getIncompleteCount();
                    $view->with('incompleteCount', $incompleteCount);

                    $adminTicketUnreadCount = JobTicket::sum('admin_unread_count');
                    $view->with('adminTicketUnreadCount', $adminTicketUnreadCount);
                }
                // 2. Employer Ticket Unread (Employer only, if not Admin/Staff)
                elseif (auth()->user()->hasRole('employer')) {
                    $employerTicketUnreadCount = JobTicket::where('employer_user_id', auth()->id())
                        ->sum('employer_unread_count');
                    $view->with('employerTicketUnreadCount', $employerTicketUnreadCount);
                }

                // 3. Notification Count (All users who can view notifications)
                if (auth()->user()->can('view-notifications')) {
                    $totalNotificationCount = \App\Helpers\NotificationHelper::getTotalNotificationCount();
                    $view->with('totalNotificationCount', $totalNotificationCount);
                }
            }
        });
    }
}

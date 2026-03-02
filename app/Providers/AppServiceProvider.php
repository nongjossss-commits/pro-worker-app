<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\JobTicket;
use App\Observers\EmployerObserver;
use App\Observers\EmployeeObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
        // Implicitly grant "super-admin" role all permissions
        // "admin" is also granted here for consistency, ensuring both roles bypass standard checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') || $user->hasRole('admin') ? true : null;
        });

        Paginator::useBootstrapFive();
        Employer::observe(EmployerObserver::class);
        Employee::observe(EmployeeObserver::class);

        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);

        // Share incomplete employee count with specific views (layout)
        view()->composer('layouts.app', function ($view) {
            if (auth()->check()) {
                // 1. Incomplete Data Count & Admin Ticket Unread (Admin/Staff only)
                if (auth()->user()->can('manage-tickets')) {
                    $incompleteCount = \App\Helpers\CompletenessHelper::getIncompleteCount();
                    $view->with('incompleteCount', $incompleteCount);

                    // Logic update: "adminTicketUnreadCount" should respect visibility scope.
                    $currentUser = auth()->user();
                    $canViewAllTickets = $currentUser->hasRole(['super-admin', 'admin', 'staff']);

                    $unreadQuery = JobTicket::query();

                    // Apply Caretaker Scope if not Admin/Staff
                    if (!$canViewAllTickets) {
                        $unreadQuery->whereHas('employerUser.employer', function ($q) use ($currentUser) {
                            $q->where('assigned_staff_id', $currentUser->id);
                        });
                    }

                    $adminTicketUnreadCount = $unreadQuery->sum('admin_unread_count');
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

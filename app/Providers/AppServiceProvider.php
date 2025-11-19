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
    }
}

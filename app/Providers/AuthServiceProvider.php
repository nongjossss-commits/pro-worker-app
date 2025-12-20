<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\Employer' => 'App\Policies\EmployerPolicy',
        'App\Models\Employee' => 'App\Policies\EmployeePolicy',
        'App\Models\PdfTemplate' => 'App\Policies\PdfTemplatePolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Implicitly grant "admin" role all permissions
        // This works in the app by using Gate::before() interception
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });
    }
}

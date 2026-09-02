<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            // Contract lifecycle: block writes system-wide when the license
            // has expired without grace, except for super-admin.
            \App\Http\Middleware\EnforceContractStatus::class,
            // Pro Walker Labor: keep dedicated labor-* roles confined to that module.
            \App\Http\Middleware\ConfineToLaborModule::class,
            // โหมดเช็คงาน: confine a user to Pre-Prod/Workflow/Registration/Renewal
            // Resolution while they have an active Job Check session.
            \App\Http\Middleware\EnforceJobCheckMode::class,
        ]);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'menu' => \App\Http\Middleware\CheckMenuAccess::class,
            'labor.access' => \App\Http\Middleware\EnsureLaborAccess::class,
            'labor.member.restrict' => \App\Http\Middleware\RestrictLaborMemberAccess::class,
        ]);
    })->create();

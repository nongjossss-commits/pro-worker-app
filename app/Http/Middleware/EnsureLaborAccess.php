<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for entering the Pro Walker Labor module.
 *
 * Allowed in:
 *  - super-admin (always)
 *  - admin, but ONLY if Super Admin has ticked users.labor_access_granted for them
 *  - labor-accounting / labor-shareholder / labor-team (dedicated roles, always)
 *
 * Deliberately NOT permission-based: `admin` bypasses every Spatie permission
 * check via Gate::before, so a `@can()`-style gate would let every admin in
 * regardless of the opt-in checkbox. This middleware checks role + flag directly.
 */
class EnsureLaborAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $allowed = $user->hasRole('super-admin')
            || $user->hasAnyRole(['labor-accounting', 'labor-shareholder', 'labor-team'])
            || ($user->hasRole('admin') && $user->labor_access_granted);

        if (!$allowed) {
            abort(403, 'You do not have access to Pro Walker Labor.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Users with a dedicated Pro Walker Labor role (labor-accounting,
 * labor-shareholder, labor-team) have zero access to the main operations
 * app — this runs on every web request and bounces them back into the
 * Labor module if they ever land on a route outside it.
 *
 * super-admin and admin are untouched here — they move between both
 * apps freely (admin only if granted, checked separately by EnsureLaborAccess
 * on the labor.* routes themselves).
 */
class ConfineToLaborModule
{
    /**
     * Public (not just protected) so other code can use this exact list as
     * the single source of truth for "confined external team" — e.g.
     * LaborContractController uses it to decide whether a user gets the
     * main-app Employer picker or a plain free-text name field when
     * issuing a Pro Worker contract, since these are precisely the roles
     * with no access to the main app's Employer records.
     */
    public const LABOR_ROLES = ['labor-accounting', 'labor-shareholder', 'labor-team', 'labor-member'];

    // Routes a confined user must still be able to reach even outside the labor.* group.
    protected const ALLOWED_ROUTE_NAMES = ['logout', 'password.confirm', 'lang.switch', 'addresses.thai_data'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasAnyRole(self::LABOR_ROLES)) {
            $routeName = $request->route()?->getName();

            $onLaborRoute = $routeName && str_starts_with($routeName, 'labor.');
            $onAllowedRoute = $routeName && in_array($routeName, self::ALLOWED_ROUTE_NAMES, true);

            if (!$onLaborRoute && !$onAllowedRoute) {
                return redirect()->route('labor.dashboard');
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The "ลูกทีม" (labor-member) role sees only their own personal totals and
 * their own basic self-service features — never a team's data, another
 * member's data, or any accounting/team-management/settings screen.
 * Rather than relying on every current AND future controller in the
 * module to individually check for this role (one missed check would leak
 * someone else's financial data), this is a single allowlist choke point:
 * a labor-member login may reach ONLY what's allowed below, anywhere else
 * in the module bounces them back to their own dashboard — same pattern
 * as App\Http\Middleware\ConfineToLaborModule, one level narrower.
 *
 * Allowed:
 *  - the dashboard and "edit my name" (exact routes)
 *  - Contracts (labor.contracts.*) — issuing/viewing/downloading their own
 *    Pro Worker contracts is basic, personal functionality every login
 *    needs; LaborContractController::assertCanAccessContract() already
 *    scopes show/edit/update/download/view to contracts THEY issued
 *    (issued_by = their own user id), so this middleware only needs to
 *    let the routes through, not re-implement that scoping here.
 *  - Company Documents (labor.company-documents.*) — browsing/downloading
 *    is open to every Labor-access role already (LaborCompanyDocumentController
 *    has no role check on index/download); its store/destroy actions stay
 *    safe regardless, since those routes carry their own independent
 *    'role:super-admin' middleware that a labor-member can never pass.
 */
class RestrictLaborMemberAccess
{
    protected const ALLOWED_ROUTE_NAMES = [
        'labor.dashboard',
        'labor.my-name.edit',
        'labor.my-name.update',
    ];

    protected const ALLOWED_ROUTE_PREFIXES = [
        'labor.contracts.',
        'labor.company-documents.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('labor-member')) {
            $routeName = $request->route()?->getName();

            $allowed = $routeName && (
                in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)
                || collect(self::ALLOWED_ROUTE_PREFIXES)->contains(fn ($prefix) => str_starts_with($routeName, $prefix))
            );

            if (!$allowed) {
                return redirect()->route('labor.dashboard');
            }
        }

        return $next($request);
    }
}

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
 *  - admin, but ONLY if Super Admin has set users.labor_access_level to
 *    'view' or 'edit' for them (not 'none') — this middleware only decides
 *    whether the module is reachable at all; which of those two tiers they
 *    got is enforced separately by the Gate::before carve-out in
 *    AppServiceProvider for the manage-labor-ledger/view-labor-ledger
 *    abilities, since permission-based gating here would let every admin
 *    in (they bypass Spatie permission checks otherwise).
 *  - labor-accounting / labor-shareholder / labor-team / labor-member (dedicated roles, always —
 *    labor-member's access within the module is then locked down to just
 *    their own dashboard by App\Http\Middleware\RestrictLaborMemberAccess)
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
            || $user->hasAnyRole(['labor-accounting', 'labor-shareholder', 'labor-team', 'labor-member'])
            || ($user->hasRole('admin') && $user->labor_access_level !== 'none');

        if (!$allowed) {
            abort(403, 'You do not have access to Pro Walker Labor.');
        }

        return $next($request);
    }
}

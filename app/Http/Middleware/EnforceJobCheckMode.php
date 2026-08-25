<?php

namespace App\Http\Middleware;

use App\Models\JobCheckSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While a user has an active "โหมดเช็คงาน" (Job Check Mode) session, they may
 * only work inside Pre-Production, Workflow, มติลงทะเบียน (Registration
 * Resolution), and มติต่ออายุ (Renewal Resolution) — all reachable under the
 * 'workflow.*' and 'production.*' route names. This is checked against the
 * DB (not the PHP session) so the restriction survives logout/browser close
 * and resumes automatically on the next login, per the user's requirement.
 */
class EnforceJobCheckMode
{
    // Routes reachable even while a check session is active, so the user
    // is never trapped unable to finish/cancel the mode or log out.
    protected const ALLOWED_ROUTE_NAMES = [
        'logout',
        'lang.switch',
        'password.confirm',
        'menu.unlock.form',
        // Universal preview widget (magnifying-glass buttons on employee/
        // employer names) — used from inside the 4 confined menus, fetched
        // via plain `fetch()` with no custom headers, so a redirect here
        // would silently render the wrong page inside the preview modal
        // instead of failing loudly.
        'global.preview',
    ];

    protected const ALLOWED_ROUTE_PREFIXES = [
        'workflow.',
        'production.',
        'job-check.',
        // Editing an employee's own record (photo, documents, personal
        // info) via the shared "Edit Employee" modal is still work on the
        // employee being checked, not navigating away — the modal is
        // triggered from inside the 4 confined menus.
        'employees.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $hasActiveSession = JobCheckSession::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if ($hasActiveSession) {
                $routeName = $request->route()?->getName();

                $onAllowedPrefix = $routeName && collect(self::ALLOWED_ROUTE_PREFIXES)
                    ->contains(fn ($prefix) => str_starts_with($routeName, $prefix));
                $onAllowedRoute = $routeName && in_array($routeName, self::ALLOWED_ROUTE_NAMES, true);

                if (!$onAllowedPrefix && !$onAllowedRoute) {
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'message' => __('You are in Job Check Mode — finish or cancel it before leaving these menus.'),
                        ], 403);
                    }

                    return redirect()->route('workflow.index')
                        ->with('warning', __('You are in Job Check Mode — finish or cancel it before leaving these menus.'));
                }
            }
        }

        return $next($request);
    }
}

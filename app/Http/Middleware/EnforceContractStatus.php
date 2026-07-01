<?php

namespace App\Http\Middleware;

use App\Services\ContractStatusService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the installation is in Read-Only mode (contract expired without grace),
 * block every write request from non-super-admin users. Super admin keeps
 * full access so they can renew the contract or enable a grace period.
 *
 * What we block:
 *   - HTTP methods POST / PUT / PATCH / DELETE
 *
 * What we still allow:
 *   - GET requests everywhere (viewing the app)
 *   - Everything under super-admin routes (so the SA can save renewals)
 *   - Auth endpoints (login/logout) so users can sign in and out
 *   - Explicit whitelisted routes below
 *
 * On block:
 *   - AJAX / expectsJson → 423 Locked JSON payload
 *   - Regular form POST → redirect back with a session flash and no data change
 */
class EnforceContractStatus
{
    /**
     * Route name prefixes that are always allowed even in Read-Only mode.
     * Super admin routes let the SA renew; the "password." prefix keeps
     * password-reset endpoints usable. NB: the POST /login and POST /logout
     * routes are UNNAMED under Laravel Breeze, so we can't whitelist them
     * by name alone — those are guarded by $writeAllowedPaths below.
     */
    protected array $writeAllowedPrefixes = [
        'super-admin.',
        'password.',
    ];

    /**
     * Raw request-path prefixes that are always allowed. Covers unnamed
     * auth routes (login/logout/register) and CSRF/livewire endpoints so
     * blocking write access can't lock the user out of authenticating.
     */
    protected array $writeAllowedPaths = [
        'login', 'logout', 'register',
        'password/', 'forgot-password', 'reset-password',
        'email/verification-notification',
        'livewire/', 'broadcasting/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Only guard write methods.
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        // Fast exit if the system isn't in read-only mode.
        if (!ContractStatusService::isReadOnly()) {
            return $next($request);
        }

        // Super admin can still write — they must be able to renew the contract.
        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return $next($request);
        }

        // Whitelist by route name (super-admin, password reset routes).
        $routeName = optional($request->route())->getName() ?? '';
        foreach ($this->writeAllowedPrefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        // Whitelist by raw path — needed for unnamed Breeze auth routes.
        $path = ltrim($request->path(), '/');
        foreach ($this->writeAllowedPaths as $allowed) {
            if ($path === rtrim($allowed, '/') || str_starts_with($path, rtrim($allowed, '/') . '/') || (str_ends_with($allowed, '/') && str_starts_with($path, $allowed))) {
                return $next($request);
            }
            // Simple prefix / exact match for entries like "login" or "logout"
            if (!str_contains($allowed, '/') && $path === $allowed) {
                return $next($request);
            }
        }

        // Block.
        $snap = ContractStatusService::snapshot();
        $message = 'ระบบอยู่ในโหมดดูอย่างเดียว (Read-Only) — สัญญาการใช้งานได้สิ้นสุดลงแล้ว โปรดติดต่อผู้ให้บริการเพื่อต่ออายุ';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'read_only' => true,
                'message' => $message,
                'contract_end' => $snap['effective_end'] ?? null,
            ], 423);
        }

        return back()->with('error', $message)->withInput();
    }
}

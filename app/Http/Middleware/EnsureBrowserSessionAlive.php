<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nobody stays signed in once they close the browser / app.
 *
 * SESSION_EXPIRE_ON_CLOSE alone isn't enough: Chrome's "continue where you
 * left off" and mobile browsers (and the installed PWA) keep session cookies
 * alive after the window is closed. So every open page of the app sends a
 * heartbeat (partials/_session_heartbeat.blade.php, once a minute and on
 * returning to the tab). Any authenticated request refreshes
 * `session_alive_at`; if the gap since the last one is longer than
 * config('session.alive_grace_seconds') — i.e. no page of the app was open
 * (browser/app closed, phone app left in the background) — the session is
 * ended and the user must log in again.
 *
 * Also ends logins restored from an old "remember me" cookie: that checkbox
 * now only remembers the email on the login page, never the login itself.
 */
class EnsureBrowserSessionAlive
{
    public const SESSION_KEY = 'session_alive_at';

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if (Auth::viaRemember()) {
            return $this->expire($request);
        }

        $grace = (int) config('session.alive_grace_seconds', 300);
        $lastAlive = $request->session()->get(self::SESSION_KEY);

        if ($grace > 0 && $lastAlive && (time() - (int) $lastAlive) > $grace) {
            return $this->expire($request);
        }

        $request->session()->put(self::SESSION_KEY, time());

        return $next($request);
    }

    protected function expire(Request $request): Response
    {
        // Tells LogSuccessfulLogout this was not the user pressing Logout.
        $request->attributes->set('logout_reason', 'inactive');
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = __('You were signed out because the program was closed or left unused. Please log in again.');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message, 'session_expired' => true], 401);
        }

        return redirect()->route('login')->with('status', $message);
    }
}

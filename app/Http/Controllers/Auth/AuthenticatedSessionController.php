<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// เราได้ลบบรรทัด 'use App\Providers\RouteServiceProvider;' ออกไปแล้ว

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Never a persistent login: closing the browser / app always means
        // logging in again (SESSION_EXPIRE_ON_CLOSE + EnsureBrowserSessionAlive).
        // The "Remember me" checkbox now only remembers the email on the
        // login page (client-side, see auth/login.blade.php); the password is
        // left to the browser's own password manager.
        if (Auth::attempt([...$credentials, 'status' => 'active'], false)) {
            $request->session()->regenerate();

            // Restore whatever language this user last picked (see
            // LanguageController::switch()) — default to Thai for anyone
            // who's never picked one, matching the previous behavior.
            $locale = Auth::user()->locale ?? 'th';
            session(['locale' => $locale]);
            \Illuminate\Support\Facades\App::setLocale($locale);

            // Pro Walker Labor: dedicated roles never see the main operations app —
            // send them straight into their module instead of '/index'.
            if (Auth::user()->hasAnyRole(['labor-accounting', 'labor-shareholder', 'labor-team', 'labor-member'])) {
                return redirect()->route('labor.dashboard');
            }

            // เปลี่ยนจาก RouteServiceProvider::HOME เป็น '/dashboard' โดยตรง
            return redirect()->intended('/index');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
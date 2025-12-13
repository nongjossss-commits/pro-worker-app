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

        // Force "Remember Me" to true for persistent login (PWA/Mobile friendly)
        if (Auth::attempt([...$credentials, 'status' => 'active'], true)) {
            $request->session()->regenerate();

            // Force Thai language on login as per requirements
            session(['locale' => 'th']);
            \Illuminate\Support\Facades\App::setLocale('th');

            // เปลี่ยนจาก RouteServiceProvider::HOME เป็น '/dashboard' โดยตรง
            return redirect()->intended('/dashboard');
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
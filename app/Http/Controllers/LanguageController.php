<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch($locale)
    {
        if (! in_array($locale, ['en', 'th', 'zh', 'my'])) {
            abort(400);
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        // Persist per-user so the choice survives logging back in — see
        // AuthenticatedSessionController, which used to hardcode 'th' on
        // every login regardless of what was picked here last time.
        auth()->user()?->update(['locale' => $locale]);

        return redirect()->back();
    }
}

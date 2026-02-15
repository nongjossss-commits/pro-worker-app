<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the application landing page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('index');
    }
}

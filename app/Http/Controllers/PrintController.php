<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Employer;

class PrintController extends Controller
{
    public function index()
    {
        $employers = Employer::all();
        return view('print-center.index', compact('employers'));
    }
}

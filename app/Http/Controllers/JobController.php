<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function createFromEmployee(Employee $employee)
    {
        return view('jobs.create', compact('employee'));
    }
}

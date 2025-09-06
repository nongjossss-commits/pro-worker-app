<?php

namespace App\Http\Controllers;

use App\Models\JobOwner;
use Illuminate\Http\Request;

class JobOwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $jobOwners = JobOwner::all();
        return response()->json($jobOwners);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:job_owners',
        ]);

        $jobOwner = JobOwner::create($request->all());

        return response()->json($jobOwner, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobOwner $jobOwner)
    {
        $jobOwner->delete();

        return response()->json(null, 204);
    }
}

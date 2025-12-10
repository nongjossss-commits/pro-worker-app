<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use Illuminate\Http\Request;

class BusinessTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(BusinessType::latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_th' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        $businessType = BusinessType::create($request->only('name_th', 'name_en'));

        return response()->json([
            'success' => true,
            'data' => $businessType,
            'message' => 'Business Type created successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessType $businessType)
    {
        $businessType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Business Type deleted successfully.'
        ]);
    }
}

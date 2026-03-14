<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employer;

class EmployerController extends Controller
{
    /**
     * Display a listing of employers.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $employers = Employer::query()
            ->when($search, function ($query, $search) {
                return $query->where('company_name_en', 'like', "%{$search}%")
                    ->orWhere('company_name_th', 'like', "%{$search}%")
                    ->orWhere('employerId', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $employers->items(),
            'meta' => [
                'current_page' => $employers->currentPage(),
                'last_page' => $employers->lastPage(),
                'per_page' => $employers->perPage(),
                'total' => $employers->total(),
            ]
        ], 200);
    }

    /**
     * Display the specified employer.
     */
    public function show($id)
    {
        $employer = Employer::with([
            'employees' => function($q) {
                // limit to 5 recent for quick summary view
                $q->latest()->take(5);
            }
        ])->find($id);

        if (!$employer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $employer
        ], 200);
    }
}

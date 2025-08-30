<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use Illuminate\Http\Request;

class EmployerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employers = Employer::all();
        return view('employers.index', compact('employers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employerNameTh' => 'required',
            'employerId' => 'required|unique:employers',
            'signerNameTh' => 'nullable',
            'signerNameEn' => 'nullable',
            'businessTypeEn' => 'nullable',
            'regCapital' => 'nullable',
            'regDate' => 'nullable|date',
        ]);

        Employer::create($request->all());

        return redirect()->route('employers.index')
            ->with('success', 'เพิ่มข้อมูลนายจ้างเรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employer $employer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employer $employer)
    {
        $employees = $employer->employees;
        return view('employers.edit', compact('employer', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employer $employer)
    {
        $request->validate([
            'employerNameTh' => 'required',
            'employerId' => 'required|unique:employers,employerId,' . $employer->id,
            'signerNameTh' => 'nullable',
            'signerNameEn' => 'nullable',
            'businessTypeEn' => 'nullable',
            'regCapital' => 'nullable',
            'regDate' => 'nullable|date',
        ]);

        $employer->update($request->all());

        return redirect()->route('employers.index')
            ->with('success', 'อัปเดตข้อมูลนายจ้างเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employer $employer)
    {
        $employer->delete();

        return redirect()->route('employers.index')
            ->with('success', 'ลบข้อมูลนายจ้างเรียบร้อยแล้ว');
    }
}

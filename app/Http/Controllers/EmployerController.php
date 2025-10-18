<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use Illuminate\Http\Request;
use App\Helpers\CountryHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-employers', ['only' => ['index', 'show', 'filterEmployees', 'filterHistory']]);
        $this->middleware('permission:create-employers', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-employers', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-employers', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $query = Employer::query();

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employerNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('employerNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('employerId', 'like', "%{$searchTerm}%");
            });
        }

        $employers = $query->latest()->paginate(10);
        return view('employers.index', compact('employers'));
    }

    public function create()
    {
        $lastEmployer = Employer::orderBy('id', 'desc')->first();
        $nextId = $lastEmployer ? (int)substr($lastEmployer->employerId, 4) + 1 : 1;
        $newEmployerId = 'EMP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('employers.create', compact('newEmployerId'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employerId' => 'required|unique:employers,employerId',
            'employerNameTh' => 'required|string|max:255',
            'employerNameEn' => 'nullable|string|max:255',
            'document_company_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_vat_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_map' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('document_company_registration')) {
            $validatedData['document_company_registration'] = $request->file('document_company_registration')->store('employer_documents', 'public');
        }
        if ($request->hasFile('document_vat_registration')) {
            $validatedData['document_vat_registration'] = $request->file('document_vat_registration')->store('employer_documents', 'public');
        }
        if ($request->hasFile('document_map')) {
            $validatedData['document_map'] = $request->file('document_map')->store('employer_documents', 'public');
        }

        $employer = Employer::create($validatedData);

        return redirect()->route('employers.edit', $employer->id)
                         ->with('success', 'Employer created successfully. You can now add addresses and employees.');
    }

    public function show(Employer $employer)
    {
        return view('employers.show', compact('employer'));
    }

    public function edit(Request $request, Employer $employer)
    {
        $nationalities = \App\Models\Employee::select('employeeNationality')->distinct()->pluck('employeeNationality');
        $perPage = $request->input('per_page', 10);

        // Employee Filtering
        $employeesQuery = $employer->employees()->where(function ($query) {
            $query->where('workPermitMOUGroup', 'MOU')
                  ->orWhere('workPermitMOUGroup', 'นำเข้า');
        });

        if ($request->has('nationality') && $request->nationality != '') {
            $employeesQuery->where('employeeNationality', $request->nationality);
        }
        if ($request->has('pink_card_status') && $request->pink_card_status != '') {
            $status = $request->pink_card_status == 'มี' ? 1 : 0;
            $employeesQuery->where('pinkCard', $status);
        }
        if ($request->has('search_employee') && $request->search_employee != '') {
            $searchTerm = $request->search_employee;
            $employeesQuery->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('passportNumber', 'like', "%{$searchTerm}%");
            });
        }

        $employees = $employeesQuery->paginate($perPage, ['*'], 'employees_page')->appends($request->except('employees_page'));
        $male_count = $employer->employees()->whereIn('employeeTitleTh', ['นาย'])->count();
        $female_count = $employer->employees()->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();

        // History Filtering (reusing some logic)
        $historyQuery = $employer->employees()->onlyTrashed();
        if ($request->has('search_history') && $request->search_history != '') {
            $searchTerm = $request->search_history;
            $historyQuery->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('passportNumber', 'like', "%{$searchTerm}%");
            });
        }
        $terminatedEmployees = $historyQuery->paginate($perPage, ['*'], 'history_page')->appends($request->except('history_page'));

        return view('employers.edit', compact(
            'employer',
            'employees',
            'terminatedEmployees',
            'nationalities',
            'male_count',
            'female_count'
        ));
    }


    public function update(Request $request, Employer $employer)
    {
        $validatedData = $request->validate([
            'employerNameTh' => 'required|string|max:255',
            'employerNameEn' => 'nullable|string|max:255',
            'document_company_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_vat_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_map' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('document_company_registration')) {
            if ($employer->document_company_registration) {
                Storage::disk('public')->delete($employer->document_company_registration);
            }
            $validatedData['document_company_registration'] = $request->file('document_company_registration')->store('employer_documents', 'public');
        }
        if ($request->hasFile('document_vat_registration')) {
            if ($employer->document_vat_registration) {
                Storage::disk('public')->delete($employer->document_vat_registration);
            }
            $validatedData['document_vat_registration'] = $request->file('document_vat_registration')->store('employer_documents', 'public');
        }
        if ($request->hasFile('document_map')) {
            if ($employer->document_map) {
                Storage::disk('public')->delete($employer->document_map);
            }
            $validatedData['document_map'] = $request->file('document_map')->store('employer_documents', 'public');
        }

        $employer->update($validatedData);

        return redirect()->route('employers.edit', $employer->id)->with('success', 'Employer updated successfully.');
    }

    public function destroy(Employer $employer)
    {
        // Soft delete the employer and related employees
        $employer->delete(); // This will trigger the deleting event in the model
        return redirect()->route('employers.index')->with('success', 'Employer and associated employees moved to trash.');
    }

    public function filterEmployees(Request $request, Employer $employer)
    {
        $query = $employer->employees();
        $currentPerPage = $request->input('per_page', 10);

        if ($request->filled('nationality')) {
            $query->where('employeeNationality', $request->nationality);
        }
        if ($request->filled('pink_card_status')) {
            $status = $request->pink_card_status == 'มี' ? 1 : 0;
            $query->where('pinkCard', $status);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('passportNumber', 'like', "%{$searchTerm}%");
            });
        }

        $employees = $query->paginate($currentPerPage);
        $male_count = (clone $query)->whereIn('employeeTitleTh', ['นาย'])->count();
        $female_count = (clone $query)->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();

        $viewMode = $request->input('view', 'card');
        $viewPath = 'employers.partials._employee_' . ($viewMode === 'table' ? 'table' : 'card') . '_view';

        return response()->json([
            'html' => view($viewPath, compact('employees', 'employer'))->render(),
            'pagination' => (string) $employees->appends($request->except('page'))->links(),
            'total' => $employees->total(),
            'male_count' => $male_count,
            'female_count' => $female_count,
            'can_restore' => auth()->user()->can('restore-employees'),
            'can_force_delete' => auth()->user()->can('force-delete-employees')
        ]);
    }

    public function filterHistory(Request $request, Employer $employer)
    {
        $query = $employer->employees()->onlyTrashed();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('passportNumber', 'like', "%{$searchTerm}%");
            });
        }
        $terminatedEmployees = $query->paginate(10);
        return response()->json([
            'html' => view('employers.partials._history_table', compact('terminatedEmployees'))->render(),
            'pagination' => (string) $terminatedEmployees->links(),
            'total' => $terminatedEmployees->total(),
            'can_restore' => auth()->user()->can('restore-employees'),
            'can_force_delete' => auth()->user()->can('force-delete-employees')
        ]);
    }

    public function export(Request $request)
    {
        // Your export logic here, potentially using a library like Laravel Excel
        return redirect()->back()->with('info', 'Export functionality is not yet implemented.');
    }

    public function exportEmployees(Request $request, Employer $employer)
    {
        // Your export logic here
        return redirect()->back()->with('info', 'Employee export functionality is not yet implemented.');
    }

    public function exportHistory(Request $request, Employer $employer)
    {
        // Your export logic here
        return redirect()->back()->with('info', 'History export functionality is not yet implemented.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $cardPerPageOptions = [10, 15, 20];
        $tablePerPageOptions = [25, 50, 100];
        $currentView = $request->input('view', 'card');
        $defaultPerPage = ($currentView === 'card') ? $cardPerPageOptions[0] : $tablePerPageOptions[0];
        $currentPerPage = $request->input('per_page', $defaultPerPage);
        $perPageOptions = ($currentView === 'card') ? $cardPerPageOptions : $tablePerPageOptions[0];

        $query = Employee::query();

        $totalEmployees = $query->count();
        $maleCount = (clone $query)->whereIn('employeeTitleTh', ['นาย'])->count();
        $femaleCount = (clone $query)->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();

        $query->with('employer')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($q_employer) use ($search) {
                      $q_employer->where('employerNameTh', 'like', "%{$search}%");
                  });
            });
        }

        $employees = $query->paginate($currentPerPage)->withQueryString();

        return view('employees.index', compact(
            'employees', 'currentView', 'currentPerPage', 'perPageOptions',
            'totalEmployees', 'maleCount', 'femaleCount'
        ));
    }

    public function create(Request $request)
    {
        $employer_id = $request->query('employer_id');
        $employer = Employer::findOrFail($employer_id);
        return view('employees.create', compact('employer'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'employeeNameTh' => 'required|string|max:255',
            'employeePassport' => 'required|string|max:255|unique:employees,employeePassport',
            'employeePhoto' => 'nullable|image|max:2048',
            // Add other fields as needed
        ]);

        $employer = Employer::findOrFail($validated['employer_id']);
        $data = $request->all();

        if ($request->hasFile('employeePhoto')) {
            $path = $request->file('employeePhoto')->store('employee_photos', 'public');
            $data['employeePhoto'] = $path;
        }

        $employer->employees()->create($data);

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'เพิ่มข้อมูลพนักงานเรียบร้อยแล้ว');
    }

    public function edit(Employer $employer, Employee $employee)
    {
        return view('employees.edit', compact('employer', 'employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'employeeNameTh' => 'required|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'employeePassport' => ['required', 'string', 'max:255', Rule::unique('employees')->ignore($employee->id)],
            'employeeTitleTh' => 'nullable|string',
            'employeeTitleEn' => 'nullable|string',
            'employeeDob' => 'nullable|date',
            'employeeNationality' => 'nullable|string',
            'passportType' => 'nullable|string',
            'passportExpiryDate' => 'nullable|date',
            'namelistNo' => 'nullable|string',
            'requestNo' => 'nullable|string',
            'workerRefNo' => 'nullable|string',
            'personalId' => 'nullable|string',
            'companyWorkerId' => 'nullable|string',
            'pinkCardNo' => 'nullable|string',
            'socialSecurityNo' => 'nullable|string',
            'taxIdNo' => 'nullable|string',
            'designatedHospital' => 'nullable|string',
            'employeeWorkPermit' => 'nullable|string',
            'workPermitExpiryDate' => 'nullable|date',
            'workPermitMOUGroup' => 'nullable|string',
            'workPermitMOUGroupOther' => 'nullable|string',
            'visaExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'startDate' => 'nullable|date',
            'employeePhone' => 'nullable|string',
            'employeePosition' => 'nullable|string',
            'employeePhoto' => 'nullable|image|max:2048',
            'document_1' => 'nullable|file|max:5120',
            'document_2' => 'nullable|file|max:5120',
            'document_3' => 'nullable|file|max:5120',
            'document_4' => 'nullable|file|max:5120',
            'document_5' => 'nullable|file|max:5120',
            'document_6' => 'nullable|file|max:5120',
            'document_7' => 'nullable|file|max:5120',
            'document_8' => 'nullable|file|max:5120',
            'document_description_3' => 'nullable|string|max:255',
            'document_description_4' => 'nullable|string|max:255',
            'document_description_5' => 'nullable|string|max:255',
        ]);

        $employee->fill($request->except(['_token', '_method']));

        $fileFields = ['employeePhoto', 'document_1', 'document_2', 'document_3', 'document_4', 'document_5', 'document_6', 'document_7', 'document_8'];
        foreach ($fileFields as $field) {
            $remove_field = 'remove_' . $field;
            // --- FIX: Logic to handle file DELETION ---
            if ($request->has($remove_field)) {
                if ($employee->{$field}) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                $employee->{$field} = null;
            }

            // --- Logic to handle file UPLOAD ---
            if ($request->hasFile($field)) {
                if ($employee->{$field}) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                $path = $request->file($field)->store($field . '_files', 'public');
                $employee->{$field} = $path;
            }
        }

        $employee->save();

        $employer = $employee->employer;

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'อัปเดตข้อมูลพนักงานเรียบร้อยแล้ว');
    }

    public function destroy(Employer $employer, Employee $employee)
    {
        if ($employee->employeePhoto) {
            Storage::disk('public')->delete($employee->employeePhoto);
        }

        for ($i = 1; $i <= 6; $i++) {
            $field = 'document_' . $i;
            if ($employee->{$field}) {
                Storage::disk('public')->delete($employee->{$field});
            }
        }

        $employee->delete();

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'ลบข้อมูลพนักงานเรียบร้อยแล้ว');
    }

    public function locate(Employee $employee)
    {
        $employer = $employee->employer;
        if (!$employer) {
            return redirect()->route('employees.index')->with('error', 'ไม่พบข้อมูลนายจ้างของลูกจ้างคนนี้');
        }
        $url = route('employers.edit', $employer) . '#employee-card-' . $employee->id;
        return redirect($url);
    }
}

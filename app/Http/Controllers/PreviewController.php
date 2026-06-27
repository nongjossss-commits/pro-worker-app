<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Delegate;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Importer;
use App\Models\JobOwner;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Exception;

class PreviewController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');

        try {
            switch ($type) {
                case 'employee':
                    $user = $request->user();
                    // Use withoutGlobalScope to allow finding employees that don't belong to the current employer
                    // (e.g. external employees attached to a ticket by admin)
                    $employee = Employee::withoutGlobalScope('employerTenancy')->with(['employer'])->withTrashed()->findOrFail($id);

                    // Security Check: Verify if user has permission to view this employee
                    $canView = false;

                    // 1. Admin/Staff (manage-tickets) can view everything
                    if ($user->can('manage-tickets')) {
                        $canView = true;
                    }
                    // 2. Employer owns the employee
                    elseif ($employee->employer && $user->id === $employee->employer->user_id) {
                        $canView = true;
                    }
                    // 3. Caretaker assigned to the employee's employer
                    // Caretakers have view-employees permission and are assigned to specific
                    // employers via the employer_caretaker pivot — they should be able to
                    // preview employees of any employer they look after.
                    elseif ($user->hasRole('caretaker') && $employee->employer
                            && $employee->employer->caretakers()->where('users.id', $user->id)->exists()) {
                        $canView = true;
                    }
                    // 4. "Security Rule": Context-aware access via JobTicket
                    // Even if the employee is not yours (and we bypassed the global scope to find it),
                    // we explicitly check if it is attached to a ticket the user has access to.
                    else {
                         // Check if this employee is attached to any ticket where the user is a participant
                         $hasAccessViaTicket = \App\Models\JobTicket::where(function($q) use ($user) {
                                 // User must be the ticket owner (employer) or the assigned staff
                                 $q->where('employer_user_id', $user->id)
                                   ->orWhere('assigned_staff_id', $user->id);
                              })
                              ->whereHas('messages', function($q) use ($id) {
                                 // The employee ID must exist in a 'attachment_employee' message within that ticket
                                 $q->where('message_type', 'attachment_employee')
                                   ->where('body', (string)$id);
                              })
                              ->exists();

                         if ($hasAccessViaTicket) {
                             $canView = true;
                         }
                    }

                    if (!$canView) {
                        abort(403, 'Unauthorized access to this employee record.');
                    }

                    return view('previews._employee_data', ['employee' => $employee]);

                case 'employer':
                    $employer = Employer::withTrashed()->findOrFail($id);

                    // --- START: โหลดข้อมูลเพิ่มเติมเหมือนหน้า Edit ---
                    $jobOwners = JobOwner::orderBy('name')->get();

                    // โหลดลูกจ้างที่ยังไม่ถูกแจ้งออก (สำหรับแสดงรายการ)
                    // ใช้ paginate(10) เพื่อไม่ให้ modal ช้า
                    $employees = $employer->employees()
                                            ->whereNull('terminated_at')
                                            ->paginate(10);

                    // โหลดลูกจ้างที่ถูกแจ้งออก (สำหรับแสดงรายการ)
                    $terminatedEmployees = $employer->employees()
                                                    ->whereNotNull('terminated_at')
                                                    ->get();

                    // โหลดข้อมูลสถิติ (ยังคงมีประโยชน์)
                    $stats = $this->getEmployeeStats($id);
                    // --- END: โหลดข้อมูลเพิ่มเติม ---

                    // ส่งตัวแปรทั้งหมดไปที่ View
                    return view('previews._employer_data', compact(
                        'employer',
                        'stats',
                        'jobOwners',
                        'employees',
                        'terminatedEmployees'
                    ));

                case 'importer':
                    $importer = Importer::with('addresses')->findOrFail($id);
                    return view('previews._importer_data', ['importer' => $importer]);

                case 'agent':
                    $agent = Agent::findOrFail($id);
                    return view('previews._agent_data', ['agent' => $agent]);

                case 'delegate':
                    $delegate = Delegate::with('addresses')->findOrFail($id);
                    return view('previews._delegate_data', ['delegate' => $delegate]);

                default:
                    return response()->json(['error' => 'Invalid preview type specified.'], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'The requested resource was not found.'], 404);
        } catch (HttpExceptionInterface $e) {
            // Don't swallow abort(403) / abort(401) etc. — let Laravel handle them
            // with the correct status code instead of turning every abort into a 500.
            throw $e;
        } catch (Exception $e) {
            \Log::error('Preview show error: ' . $e->getMessage(), ['type' => $type, 'id' => $id, 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Get employee statistics for a given employer.
     *
     * @param  int  $employer_id
     * @return object
     */
    private function getEmployeeStats($employer_id)
    {
        $employees = Employee::where('employer_id', $employer_id)->whereNull('terminated_at')->get();

        $total = $employees->count();
        $male = $employees->where('employeeTitleTh', 'นาย')->count();
        $female = $employees->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();

        // NEW: Count terminated employees
        $terminated_total = Employee::where('employer_id', $employer_id)->whereNotNull('terminated_at')->count();

        $breakdown = $employees->groupBy('employeeNationality')->map(function ($group) {
            return (object) [
                'male_count' => $group->where('employeeTitleTh', 'นาย')->count(),
                'female_count' => $group->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count(),
                'total_count' => $group->count(),
            ];
        });

        return (object) [
            'total' => $total,
            'male' => $male,
            'female' => $female,
            'terminated_total' => $terminated_total, // <-- ADD THIS LINE
            'breakdown' => $breakdown,
        ];
    }
}

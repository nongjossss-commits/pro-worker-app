<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\EmployeeTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupTeamController extends Controller
{
    public function index()
    {
        return view('groups.index');
    }

    public function indexAffiliated(Request $request)
    {
        $search = $request->input('search');
        $employers = collect();

        if ($search) {
            $employers = Employer::query()
                ->where('employerNameTh', 'like', "%{$search}%")
                ->orWhere('employerNameEn', 'like', "%{$search}%")
                ->limit(20)
                ->get();
        }

        return view('groups.affiliated.index', compact('employers', 'search'));
    }

    public function manageAffiliated(Request $request, Employer $employer)
    {
        $search = $request->input('search');
        $nationality = $request->input('nationality');
        $passportTypeMyanmar = $request->input('passport_type_myanmar');
        $passportTypeCambodia = $request->input('passport_type_cambodia');
        $pinkCard = $request->input('pink_card');

        // Fetch distinct nationalities for dropdown
        $nationalities = Employee::distinct('employeeNationality')->whereNotNull('employeeNationality')->pluck('employeeNationality');

        $groups = EmployeeGroup::where('employer_id', $employer->id)
            ->where('type', 'affiliated')
            ->with(['teams.employees' => function($query) use ($search, $nationality, $passportTypeMyanmar, $passportTypeCambodia, $pinkCard) {
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('employeeNameTh', 'like', "%{$search}%")
                          ->orWhere('employeeNameEn', 'like', "%{$search}%")
                          ->orWhere('employeePassport', 'like', "%{$search}%")
                          ->orWhere('pinkCardNo', 'like', "%{$search}%");
                    });
                }
                if ($nationality) {
                    $query->where('employeeNationality', $nationality);
                }
                if ($passportTypeMyanmar) {
                    $query->where('passportType', $passportTypeMyanmar);
                }
                if ($passportTypeCambodia) {
                    $query->where('passport_type_cambodia', $passportTypeCambodia);
                }
                if ($pinkCard) {
                    if ($pinkCard === 'has_card') {
                        $query->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
                    } elseif ($pinkCard === 'no_card') {
                        $query->where(function($q) {
                            $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '');
                        });
                    }
                }
                $query->with('employer');
            }])
            ->get();

        return view('groups.manage', [
            'type' => 'affiliated',
            'employer' => $employer,
            'groups' => $groups,
            'nationalities' => $nationalities,
            'filters' => $request->all()
        ]);
    }

    public function manageIndependent(Request $request)
    {
        $search = $request->input('search');
        $nationality = $request->input('nationality');
        $passportTypeMyanmar = $request->input('passport_type_myanmar');
        $passportTypeCambodia = $request->input('passport_type_cambodia');
        $pinkCard = $request->input('pink_card');

        // Fetch distinct nationalities for dropdown
        $nationalities = Employee::distinct('employeeNationality')->whereNotNull('employeeNationality')->pluck('employeeNationality');

        $groups = EmployeeGroup::where('type', 'independent')
            ->with(['teams.employees' => function($query) use ($search, $nationality, $passportTypeMyanmar, $passportTypeCambodia, $pinkCard) {
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('employeeNameTh', 'like', "%{$search}%")
                          ->orWhere('employeeNameEn', 'like', "%{$search}%")
                          ->orWhere('employeePassport', 'like', "%{$search}%")
                          ->orWhere('pinkCardNo', 'like', "%{$search}%");
                    });
                }
                if ($nationality) {
                    $query->where('employeeNationality', $nationality);
                }
                if ($passportTypeMyanmar) {
                    $query->where('passportType', $passportTypeMyanmar);
                }
                if ($passportTypeCambodia) {
                    $query->where('passport_type_cambodia', $passportTypeCambodia);
                }
                if ($pinkCard) {
                    if ($pinkCard === 'has_card') {
                        $query->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
                    } elseif ($pinkCard === 'no_card') {
                        $query->where(function($q) {
                            $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '');
                        });
                    }
                }

                // Load employer and sort by it for the grouping requirement
                $query->with('employer')
                      ->orderBy('employer_id');
            }])
            ->get();

        return view('groups.manage', [
            'type' => 'independent',
            'employer' => null,
            'groups' => $groups,
            'nationalities' => $nationalities,
            'filters' => $request->all()
        ]);
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:affiliated,independent',
            'employer_id' => 'required_if:type,affiliated|nullable|exists:employers,id',
        ]);

        EmployeeGroup::create($request->all());

        return back()->with('success', 'Group created successfully.');
    }

    public function storeTeam(Request $request, EmployeeGroup $group)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $group->teams()->create([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Team created successfully.');
    }

    public function searchEmployees(Request $request)
    {
        $term = $request->input('term');
        $employerId = $request->input('employer_id');
        $groupId = $request->input('group_id');

        $query = Employee::query()
            ->where(function($q) use ($term) {
                $q->where('employeeNameTh', 'like', "%{$term}%")
                  ->orWhere('employeeNameEn', 'like', "%{$term}%")
                  ->orWhere('employeePassport', 'like', "%{$term}%");
            });

        // If affiliated, limit to employer
        if ($employerId) {
            $query->where('employer_id', $employerId);
        }

        // Exclude employees who are already in a team WITHIN THIS GROUP
        if ($groupId) {
            $query->whereDoesntHave('teams.group', function ($q) use ($groupId) {
                $q->where('id', $groupId);
            });
        }

        $employees = $query->limit(50)->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'employeePhoto']);

        // Transform for frontend
        $results = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->employeeNameTh . ' (' . $emp->employeeNameEn . ')',
                'passport' => $emp->employeePassport,
                'photo' => $emp->photo_url,
            ];
        });

        return response()->json($results);
    }

    public function addMember(Request $request, EmployeeTeam $team)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $group = $team->group;

        // Verify constraint: Employee cannot be in another team in this group
        $alreadyInGroup = $employee->teams()->where('employee_group_id', $group->id)->exists();

        if ($alreadyInGroup) {
            return response()->json(['message' => 'Employee is already in a team within this group.'], 422);
        }

        $team->employees()->attach($employee->id);

        return response()->json(['success' => true]);
    }

    public function removeMember(EmployeeTeam $team, Employee $employee)
    {
        $team->employees()->detach($employee->id);
        return back()->with('success', 'Member removed.');
    }

    // For navigation via Tags - Fixed argument order to match route {group}/{employee}
    public function locateMember(EmployeeGroup $group, Employee $employee)
    {
        // Find which team in this group the employee belongs to
        $team = $employee->teams()->where('employee_group_id', $group->id)->first();

        if (!$team) {
            return back()->with('error', 'Employee not found in this group.');
        }

        if ($group->type === 'affiliated') {
            return redirect()->route('groups.affiliated.manage', [
                'employer' => $group->employer_id,
                'active_group' => $group->id,
                'active_team' => $team->id
            ]);
        } else {
             return redirect()->route('groups.independent.manage', [
                'active_group' => $group->id,
                'active_team' => $team->id
            ]);
        }
    }
}

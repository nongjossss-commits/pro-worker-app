<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\EmployeeTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\AddressFilterTrait;

class GroupTeamController extends Controller
{
    use AddressFilterTrait;

    public function index()
    {
        return view('groups.index');
    }

    public function indexAffiliated(Request $request)
    {
        $search = $request->input('search');
        $query = Employer::with('addresses');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('employerNameTh', 'like', "%{$search}%")
                  ->orWhere('employerNameEn', 'like', "%{$search}%")
                  ->orWhere('name_suffix', 'like', "%{$search}%");
            });
        }

        // Address options (before address filtering)
        $addressOptions = $this->getAddressOptions($query);

        // Apply address filters
        $query = $this->applyAddressFilters($query, $request);

        $employers = collect();
        if ($search || $request->filled('addrProvince')) {
            $employers = $query->limit(20)->get();
        }

        return view('groups.affiliated.index', compact('employers', 'search', 'addressOptions'));
    }

    public function manageAffiliated(Request $request, Employer $employer)
    {
        $search = $request->input('search');
        $nationality = $request->input('nationality');
        $passportTypeMyanmar = $request->input('passport_type_myanmar');
        $passportTypeCambodia = $request->input('passport_type_cambodia');
        $pinkCard = $request->input('pink_card');

        // NEW: Address Filter logic
        $addrProvince = $request->input('addrProvince');
        $addrDistrict = $request->input('addrDistrict');
        $addrSubDistrict = $request->input('addrSubDistrict');

        // Fetch distinct nationalities for dropdown
        $nationalities = Employee::distinct('employeeNationality')->whereNotNull('employeeNationality')->pluck('employeeNationality');

        // 1. Get all groups for tabs (lightweight)
        $allGroups = EmployeeGroup::where('employer_id', $employer->id)
            ->where('type', 'affiliated')
            ->get(['id', 'name']);

        // 2. Determine active group
        $activeGroupId = $request->input('active_group') ?? ($allGroups->first()->id ?? null);
        $activeGroup = null;

        if ($activeGroupId) {
             $activeGroup = EmployeeGroup::where('id', $activeGroupId)
                ->where('employer_id', $employer->id) // Ensure belongs to this employer
                ->where('type', 'affiliated')
                ->with(['teams.employees' => function($query) use ($search, $nationality, $passportTypeMyanmar, $passportTypeCambodia, $pinkCard, $addrProvince, $addrDistrict, $addrSubDistrict) {
                    if ($search) {
                        $query->where(function($q) use ($search) {
                            $q->where('employeeNameTh', 'like', "%{$search}%")
                              ->orWhere('employeeNameEn', 'like', "%{$search}%")
                              ->orWhere('name_suffix', 'like', "%{$search}%")
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

                    if ($addrProvince || $addrDistrict || $addrSubDistrict) {
                        $query->whereHas('employer.addresses', function($q) use ($addrProvince, $addrDistrict, $addrSubDistrict) {
                            if ($addrProvince) $q->where('addrProvince', $addrProvince);
                            if ($addrDistrict) $q->where('addrDistrict', $addrDistrict);
                            if ($addrSubDistrict) $q->where('addrSubDistrict', $addrSubDistrict);
                        });
                    }

                    $query->with(['employer.addresses', 'employer.jobOwner']);
                }])
                ->first();
        }

        // Options for single employer are just its own addresses
        $addressOptions = $this->getAddressOptions(Employer::where('id', $employer->id));

        return view('groups.manage', [
            'type' => 'affiliated',
            'employer' => $employer,
            'allGroups' => $allGroups,
            'activeGroup' => $activeGroup,
            'nationalities' => $nationalities,
            'filters' => $request->all(),
            'addressOptions' => $addressOptions
        ]);
    }

    public function manageIndependent(Request $request)
    {
        $search = $request->input('search');
        $nationality = $request->input('nationality');
        $passportTypeMyanmar = $request->input('passport_type_myanmar');
        $passportTypeCambodia = $request->input('passport_type_cambodia');
        $pinkCard = $request->input('pink_card');

        // NEW: Address Filter logic
        $addrProvince = $request->input('addrProvince');
        $addrDistrict = $request->input('addrDistrict');
        $addrSubDistrict = $request->input('addrSubDistrict');

        // Fetch distinct nationalities for dropdown
        $nationalities = Employee::distinct('employeeNationality')->whereNotNull('employeeNationality')->pluck('employeeNationality');

        // 1. Get all groups for tabs (lightweight)
        $allGroups = EmployeeGroup::where('type', 'independent')->get(['id', 'name']);

        // 2. Determine active group
        $activeGroupId = $request->input('active_group') ?? ($allGroups->first()->id ?? null);
        $activeGroup = null;

        $addressOptions = ['provinces' => [], 'districts' => [], 'subDistricts' => []];

        if ($activeGroupId) {
             $activeGroup = EmployeeGroup::where('id', $activeGroupId)
                ->where('type', 'independent')
                ->with(['teams.employees' => function($query) use ($search, $nationality, $passportTypeMyanmar, $passportTypeCambodia, $pinkCard, $addrProvince, $addrDistrict, $addrSubDistrict) {
                    if ($search) {
                        $query->where(function($q) use ($search) {
                            $q->where('employeeNameTh', 'like', "%{$search}%")
                              ->orWhere('employeeNameEn', 'like', "%{$search}%")
                              ->orWhere('name_suffix', 'like', "%{$search}%")
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

                    if ($addrProvince || $addrDistrict || $addrSubDistrict) {
                        $query->whereHas('employer.addresses', function($q) use ($addrProvince, $addrDistrict, $addrSubDistrict) {
                            if ($addrProvince) $q->where('addrProvince', $addrProvince);
                            if ($addrDistrict) $q->where('addrDistrict', $addrDistrict);
                            if ($addrSubDistrict) $q->where('addrSubDistrict', $addrSubDistrict);
                        });
                    }

                    // Load employer and sort by it for the grouping requirement
                    $query->with(['employer.addresses', 'employer.jobOwner'])
                          ->orderBy('employer_id');
                }])
                ->first();

             // Address options based on employers IN the group
             $employerIdsQuery = DB::table('employee_team_members')
                ->join('employee_teams', 'employee_team_members.employee_team_id', '=', 'employee_teams.id')
                ->join('employees', 'employee_team_members.employee_id', '=', 'employees.id')
                ->where('employee_teams.employee_group_id', $activeGroupId)
                ->select('employees.employer_id');

             $addressOptions = $this->getAddressOptions(Employer::whereIn('id', $employerIdsQuery));
        }

        return view('groups.manage', [
            'type' => 'independent',
            'employer' => null,
            'allGroups' => $allGroups,
            'activeGroup' => $activeGroup,
            'nationalities' => $nationalities,
            'filters' => $request->all(),
            'addressOptions' => $addressOptions
        ]);
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:affiliated,independent',
            'employer_id' => 'required_if:type,affiliated|nullable|exists:employers,id',
        ]);

        $group = EmployeeGroup::create($request->all());

        if ($group->type === 'affiliated') {
            return redirect()->route('groups.affiliated.manage', [
                'employer' => $group->employer_id,
                'active_group' => $group->id
            ])->with('success', 'Group created successfully.');
        } else {
            return redirect()->route('groups.independent.manage', [
                'active_group' => $group->id
            ])->with('success', 'Group created successfully.');
        }
    }

    public function updateGroup(Request $request, EmployeeGroup $group)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Explicitly only update the name, ensuring employer_id is not changed
        $group->update([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Group updated successfully.');
    }

    public function storeTeam(Request $request, EmployeeGroup $group)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team = $group->teams()->create([
            'name' => $request->name,
        ]);

        if ($group->type === 'affiliated') {
            return redirect()->route('groups.affiliated.manage', [
                'employer' => $group->employer_id,
                'active_group' => $group->id,
                'active_team' => $team->id
            ])->with('success', 'Team created successfully.');
        } else {
            return redirect()->route('groups.independent.manage', [
                'active_group' => $group->id,
                'active_team' => $team->id
            ])->with('success', 'Team created successfully.');
        }
    }

    public function updateTeam(Request $request, EmployeeTeam $team)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team->update([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Team updated successfully.');
    }

    public function searchEmployees(Request $request)
    {
        $term = $request->input('term');
        $employerId = $request->input('employer_id');
        $groupId = $request->input('group_id');

        $query = Employee::query()
            ->with('employer')
            ->where(function($q) use ($term) {
                $q->where('employeeNameTh', 'like', "%{$term}%")
                  ->orWhere('employeeNameEn', 'like', "%{$term}%")
                  ->orWhere('name_suffix', 'like', "%{$term}%")
                  ->orWhere('employeePassport', 'like', "%{$term}%")
                  ->orWhere('employeeWorkPermit', 'like', "%{$term}%")
                  ->orWhere('employee_reference_id', 'like', "%{$term}%")
                  ->orWhere('outsource_code', 'like', "%{$term}%")
                  ->orWhere('employer_employee_id', 'like', "%{$term}%")
                  ->orWhere('pinkCardNo', 'like', "%{$term}%")
                  ->orWhere('request_number', 'like', "%{$term}%")
                  ->orWhere('registration_request_number', 'like', "%{$term}%")
                  ->orWhere('renewal_request_number', 'like', "%{$term}%")
                  ->orWhereHas('employer', function($q2) use ($term) {
                      $q2->where('employerNameTh', 'like', "%{$term}%")
                         ->orWhere('employerNameEn', 'like', "%{$term}%")
                         ->orWhere('name_suffix', 'like', "%{$term}%");
                  });
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

        $employees = $query->limit(50)->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'employeePhoto', 'employer_id']);

        // Transform for frontend
        $results = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->employeeNameTh . ' (' . $emp->employeeNameEn . ')',
                'passport' => $emp->employeePassport,
                'photo' => $emp->photo_url,
                'employer_name' => $emp->employer ? ($emp->employer->employerNameTh . ' (' . $emp->employer->employerNameEn . ')') : 'N/A',
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

        $group = $team->group;

        if ($group->type === 'affiliated') {
            return redirect()->route('groups.affiliated.manage', [
                'employer' => $group->employer_id,
                'active_group' => $group->id,
                'active_team' => $team->id
            ])->with('success', 'Member removed.');
        } else {
             return redirect()->route('groups.independent.manage', [
                'active_group' => $group->id,
                'active_team' => $team->id
            ])->with('success', 'Member removed.');
        }
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
                'active_team' => $team->id,
                'highlight_employee' => $employee->id
            ]);
        } else {
             return redirect()->route('groups.independent.manage', [
                'active_group' => $group->id,
                'active_team' => $team->id,
                'highlight_employee' => $employee->id
            ]);
        }
    }

    public function destroyGroup(EmployeeGroup $group)
    {
        $user = auth()->user();
        if ($group->type === 'affiliated') {
            if (!$user->can('manage-tickets') && ($user->employer?->id !== $group->employer_id)) {
                abort(403, 'Unauthorized action.');
            }
        } else { // independent
            if (!$user->can('manage-tickets')) {
                abort(403, 'Unauthorized action.');
            }
        }

        DB::transaction(function () use ($group) {
            // Detach all employees from all teams within this group
            foreach ($group->teams as $team) {
                $team->employees()->detach();
            }
            // Delete all teams in the group
            $group->teams()->delete();
            // Delete the group itself
            $group->delete();
        });

        if ($group->type === 'affiliated') {
            return redirect()->route('groups.affiliated.manage', [
                'employer' => $group->employer_id,
            ])->with('success', 'Group and all its teams have been deleted.');
        } else {
            return redirect()->route('groups.independent.manage')
                           ->with('success', 'Group and all its teams have been deleted.');
        }
    }

    public function destroyTeam(EmployeeTeam $team)
    {
        $user = auth()->user();
        $group = $team->group;

        if ($group->type === 'affiliated') {
            if (!$user->can('manage-tickets') && ($user->employer?->id !== $group->employer_id)) {
                abort(403, 'Unauthorized action.');
            }
        } else { // independent
            if (!$user->can('manage-tickets')) {
                abort(403, 'Unauthorized action.');
            }
        }

        DB::transaction(function () use ($team) {
            // Detach all employees from this team
            $team->employees()->detach();
            // Delete the team
            $team->delete();
        });

        if ($group->type === 'affiliated') {
            return redirect()->route('groups.affiliated.manage', [
                'employer' => $group->employer_id,
                'active_group' => $group->id
            ])->with('success', 'Team has been deleted.');
        } else {
            return redirect()->route('groups.independent.manage', [
                'active_group' => $group->id
            ])->with('success', 'Team has been deleted.');
        }
    }
}

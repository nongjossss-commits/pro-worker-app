<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\LaborTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /** Pro Walker Labour roles — creation/editing restricted to Super Admin only. */
    protected const LABOR_ROLES = ['labor-accounting', 'labor-shareholder', 'labor-team', 'labor-member'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = User::with('roles')->latest();

        // Security: เฉพาะ super-admin เท่านั้นที่เห็น super-admin users คนอื่น
        // role อื่น (admin/staff/caretaker/employer) จะไม่เห็น super-admin users เลย
        if (!Auth::user()->hasRole('super-admin')) {
            $query->whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        // Determine active tab based on search results
        $activeTab = 'admin';
        if ($search && $users->isNotEmpty()) {
            $counts = [
                'super-admin' => $users->filter(fn($u) => $u->roles->contains('name', 'super-admin'))->count(),
                'admin' => $users->filter(fn($u) => $u->roles->contains('name', 'admin'))->count(),
                'caretaker' => $users->filter(fn($u) => $u->roles->contains('name', 'caretaker'))->count(),
                'staff' => $users->filter(fn($u) => $u->roles->contains('name', 'staff'))->count(),
                'employer' => $users->filter(fn($u) => $u->roles->contains('name', 'employer'))->count(),
            ];
            // Get the role with the most results
            $activeTab = array_keys($counts, max($counts))[0];
        }

        // Roles & Permissions read-only view (merged from the standalone
        // /admin/roles-permissions page — same data, shown at the bottom).
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'search', 'activeTab', 'roles', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Filter Roles: Only Super Admin can see/select 'super-admin'
        if (Auth::user()->hasRole('super-admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::where('name', '!=', 'super-admin')->get();
        }

        $employers = Employer::whereNull('user_id')->get();
        $laborTeams = LaborTeam::orderBy('name')->get();
        return view('admin.users.create', compact('roles', 'employers', 'laborTeams'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // <-- Password required on create
            'role_name' => ['required', 'string', Rule::exists('roles', 'name')],
            'employer_id' => ['nullable', 'required_if:role_name,employer', Rule::exists('employers', 'id')],
            'labor_team_id' => ['nullable', 'required_if:role_name,labor-team', Rule::exists('labor_teams', 'id')],
            'labor_access_level' => ['nullable', 'in:none,view,edit'],
            'staff_code' => ['nullable', 'string', 'max:50'],
        ]);

        // Security Check: Prevent non-SuperAdmin from creating SuperAdmin
        if ($request->role_name === 'super-admin' && !Auth::user()->hasRole('super-admin')) {
            abort(403, 'Unauthorized action. Only Super Admin can create another Super Admin.');
        }

        // Security Check: Pro Walker Labor roles are Super Admin's responsibility only
        // (they're the sole overseer of Accounting Staff via the module's audit log).
        if (in_array($request->role_name, self::LABOR_ROLES, true) && !Auth::user()->hasRole('super-admin')) {
            abort(403, 'Unauthorized action. Only Super Admin can create Pro Walker Labor accounts.');
        }

        // Only Super Admin may grant Labor access, and only to the admin role.
        $laborAccessLevel = ($request->role_name === 'admin' && Auth::user()->hasRole('super-admin'))
            ? ($request->input('labor_access_level') ?: 'none')
            : 'none';

        // labor_team_id is required for role=labor-team (unchanged) and now also
        // optionally assignable to an admin granted Labor access, so their Pro
        // Worker contract issuances/company document downloads can be
        // attributed to a team (see routes/labor.php — Company Documents +
        // Contracts live inside the Labor module).
        $laborTeamId = match (true) {
            $request->role_name === 'labor-team' => $request->labor_team_id,
            $request->role_name === 'admin' && $laborAccessLevel !== 'none' => $request->labor_team_id,
            default => null,
        };

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
            'labor_team_id' => $laborTeamId,
            'labor_access_level' => $laborAccessLevel,
            'staff_code' => Auth::user()->hasRole('super-admin') ? $request->staff_code : null,
        ]);

        // Assign Role
        $user->assignRole($request->role_name);

        // Link Employer
        if ($request->role_name === 'employer' && $request->employer_id) {
            $employer = Employer::find($request->employer_id);
            if ($employer) {
                $employer->update(['user_id' => $user->id]);
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Security Check: Prevent non-SuperAdmin from editing SuperAdmin
        if ($user->hasRole('super-admin') && !Auth::user()->hasRole('super-admin')) {
             abort(403, 'Unauthorized action. You cannot edit a Super Admin user.');
        }

        // Filter Roles: Only Super Admin can see/select 'super-admin'
        if (Auth::user()->hasRole('super-admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::where('name', '!=', 'super-admin')->get();
        }

        $allPermissions = Permission::all();
        $userPermissions = $user->permissions->pluck('name')->toArray();
        $revokedPermissions = $user->revoked_permissions ?? [];

        // Base permission set granted by each role, keyed by role name — used by the
        // Edit form to show/recompute "current permissions" per role (see hasRevoked()).
        $rolePermissionsMap = Role::with('permissions')->get()
            ->mapWithKeys(fn ($role) => [$role->name => $role->permissions->pluck('name')->values()]);

        // Server-rendered fallback for the checkboxes' initial state (role ∪ direct,
        // minus revoked) for the user's CURRENT role — so the form still shows accurate
        // ticks even if JS fails to load; Alpine takes over from there for live updates.
        $currentRoleBase = $rolePermissionsMap[$user->roles->first()->name ?? ''] ?? collect();
        $initialCheckedPermissions = $currentRoleBase->merge($userPermissions)
            ->diff($revokedPermissions)
            ->values()
            ->toArray();

        // V1.1 PATCH: Get available employers (unlinked OR this user's current linked one)
        $currentEmployerId = $user->employer->id ?? null;
        $employers = Employer::whereNull('user_id')
            ->orWhere('id', $currentEmployerId)
            ->get();

        $laborTeams = LaborTeam::orderBy('name')->get();

        return view('admin.users.edit', compact(
            'user', 'roles', 'allPermissions', 'userPermissions', 'revokedPermissions',
            'rolePermissionsMap', 'initialCheckedPermissions', 'employers', 'laborTeams'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Check if this is a request from the full "Edit Form" (Feature D)
        if ($request->has('name')) {

            // Security Check 1: Prevent non-SuperAdmin from updating SuperAdmin
            if ($user->hasRole('super-admin') && !Auth::user()->hasRole('super-admin')) {
                abort(403, 'Unauthorized action. You cannot update a Super Admin user.');
            }

            // Security Check 2: Prevent non-SuperAdmin from assigning SuperAdmin role
            if ($request->role_name === 'super-admin' && !Auth::user()->hasRole('super-admin')) {
                abort(403, 'Unauthorized action. Only Super Admin can assign the Super Admin role.');
            }

            // Security Check 3: Pro Walker Labor roles are Super Admin's responsibility only
            if (in_array($request->role_name, self::LABOR_ROLES, true) && !Auth::user()->hasRole('super-admin')) {
                abort(403, 'Unauthorized action. Only Super Admin can assign Pro Walker Labor roles.');
            }

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'], // <-- MUST BE NULLABLE on edit
                'role_name' => ['required', 'string', Rule::exists('roles', 'name')],
                'employer_id' => ['nullable', 'required_if:role_name,employer', Rule::exists('employers', 'id')], // <-- ADDED (Bug Fix)
                'labor_team_id' => ['nullable', 'required_if:role_name,labor-team', Rule::exists('labor_teams', 'id')],
                'labor_access_level' => ['nullable', 'in:none,view,edit'],
                'staff_code' => ['nullable', 'string', 'max:50'],
                'permissions' => ['nullable', 'array']
            ]);

            // Update User Details
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            // Only Super Admin may change the Labor access grant, and only for the admin role.
            if (Auth::user()->hasRole('super-admin')) {
                $laborAccessLevel = $request->role_name === 'admin'
                    ? ($request->input('labor_access_level') ?: 'none')
                    : 'none';
                $updateData['labor_access_level'] = $laborAccessLevel;

                // labor_team_id: required for role=labor-team (unchanged), also
                // optionally assignable to an admin granted Labor access (see store()).
                $updateData['labor_team_id'] = match (true) {
                    $request->role_name === 'labor-team' => $request->labor_team_id,
                    $request->role_name === 'admin' && $laborAccessLevel !== 'none' => $request->labor_team_id,
                    default => null,
                };

                $updateData['staff_code'] = $request->staff_code;
            } else {
                // Non-Super-Admin editors still need labor_team_id kept in sync for
                // the labor-team role (their own existing behavior, unchanged).
                $updateData['labor_team_id'] = $request->role_name === 'labor-team' ? $request->labor_team_id : $user->labor_team_id;
            }

            // V1.1 PATCH: Conditionally update password
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }
            $user->update($updateData);

            // Sync Role
            $user->syncRoles([$request->role_name]);

            // V1.1 PATCH: Handle Employer Linkage (Bug Fix)
            $currentEmployer = Employer::where('user_id', $user->id)->first();

            if ($request->role_name === 'employer' && $request->employer_id) {
                // Link new employer
                if ($currentEmployer && $currentEmployer->id != $request->employer_id) {
                    $currentEmployer->update(['user_id' => null]); // Unlink old
                }
                $newEmployer = Employer::find($request->employer_id);
                if ($newEmployer) {
                    $newEmployer->update(['user_id' => $user->id]); // Link new
                }
            } elseif ($currentEmployer) {
                // Role changed *away* from employer, unlink them
                $currentEmployer->update(['user_id' => null]);
            }


            // Sync Permissions — admin/super-admin bypass every permission check via
            // Gate::before (see AppServiceProvider), so per-user overrides would have
            // no effect for them; skip and clear any stale override data instead.
            // Revocations are enforced in User::hasPermissionTo().
            if (in_array($request->role_name, ['admin', 'super-admin'], true)) {
                $user->update(['revoked_permissions' => null]);
            } else {
                // Compare submitted checkboxes against the new role's base permission
                // set: extra checks beyond the role's base become direct grants (as
                // before); unchecked boxes that the role WOULD grant become explicit
                // revocations, overriding the role via Gate::before.
                $roleBasePermissions = Role::where('name', $request->role_name)->first()
                    ?->permissions->pluck('name')->toArray() ?? [];
                $checkedPermissions = $request->input('permissions', []);

                $directGrants = [];
                $revoked = [];
                foreach (Permission::pluck('name') as $permissionName) {
                    $isChecked = in_array($permissionName, $checkedPermissions, true);
                    $roleGrantsIt = in_array($permissionName, $roleBasePermissions, true);

                    if ($isChecked && !$roleGrantsIt) {
                        $directGrants[] = $permissionName;
                    } elseif (!$isChecked && $roleGrantsIt) {
                        $revoked[] = $permissionName;
                    }
                }

                $user->syncPermissions($directGrants);
                $user->update(['revoked_permissions' => $revoked]);
            }

            return redirect()->route('admin.users.index')->with('success', 'User permissions and details updated.');
        } else {
            // This is a request from the "Status Toggle" (Feature C)

            // Security Check: Prevent non-SuperAdmin from toggling status of SuperAdmin
            if ($user->hasRole('super-admin') && !Auth::user()->hasRole('super-admin')) {
                 abort(403, 'Unauthorized action. You cannot change status of a Super Admin user.');
            }

            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            $user->update(['status' => $newStatus]);
            return redirect()->route('admin.users.index')->with('success', 'User status updated successfully.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    /**
     * Get a list of active operators (Super Admin, Admin, Staff).
     */
    public function listOperators()
    {
        $operators = User::role(['super-admin', 'admin', 'staff'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $operators
        ]);
    }
}

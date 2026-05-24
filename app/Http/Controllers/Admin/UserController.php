<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
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

        return view('admin.users.index', compact('users', 'search', 'activeTab'));
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
        return view('admin.users.create', compact('roles', 'employers'));
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
        ]);

        // Security Check: Prevent non-SuperAdmin from creating SuperAdmin
        if ($request->role_name === 'super-admin' && !Auth::user()->hasRole('super-admin')) {
            abort(403, 'Unauthorized action. Only Super Admin can create another Super Admin.');
        }

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
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

        // V1.1 PATCH: Get available employers (unlinked OR this user's current linked one)
        $currentEmployerId = $user->employer->id ?? null;
        $employers = Employer::whereNull('user_id')
            ->orWhere('id', $currentEmployerId)
            ->get();

        return view('admin.users.edit', compact('user', 'roles', 'allPermissions', 'userPermissions', 'employers'));
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

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'], // <-- MUST BE NULLABLE on edit
                'role_name' => ['required', 'string', Rule::exists('roles', 'name')],
                'employer_id' => ['nullable', 'required_if:role_name,employer', Rule::exists('employers', 'id')], // <-- ADDED (Bug Fix)
                'permissions' => ['nullable', 'array']
            ]);

            // Update User Details
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

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


            // Sync Permissions
            $user->syncPermissions($request->input('permissions', []));

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

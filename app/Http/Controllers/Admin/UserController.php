<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('roles')->latest()->get(); // Eager load roles (Source 84)
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        $employers = Employer::whereNull('user_id')->get(); // Only show unlinked employers (Source 18)
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_name' => ['required', 'string', Rule::exists('roles', 'name')], // Use Spatie Role name (Source 9-15)
            'employer_id' => ['nullable', 'required_if:role_name,employer', Rule::exists('employers', 'id')], // Required only for employer role (Source 1)
        ]);

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active', // Default to active
        ]);

        // Assign Role
        $user->assignRole($request->role_name);

        // Link Employer (Feature A requirement) (Source 1)
        if ($request->role_name === 'employer' && $request->employer_id) {
            $employer = Employer::find($request->employer_id);
            if ($employer) {
                $employer->update(['user_id' => $user->id]); // Link user_id in employers table (Source 18)
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
        $roles = Role::all();
        $allPermissions = Permission::all(); // Get the Master List (Source 211)
        $userPermissions = $user->permissions->pluck('name')->toArray(); // Get only direct permissions
        return view('admin.users.edit', compact('user', 'roles', 'allPermissions', 'userPermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Check if this is a request from the full "Edit Form" (Feature D)
        if ($request->has('name')) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'role_name' => ['required', 'string', Rule::exists('roles', 'name')],
                'permissions' => ['nullable', 'array'] // 'permissions' is the name of our checkbox array
            ]);

            // Update User Details
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            // Sync Role (Note: We only sync the *first* role for simplicity)
            $user->syncRoles([$request->role_name]);

            // Sync Permissions (The core of Feature D) (Source 79)
            // This uses the HasRoles trait (Source 102, 103)
            $user->syncPermissions($request->input('permissions', []));

            return redirect()->route('admin.users.index')->with('success', 'User permissions and details updated.');
        } else {
            // This is a request from the "Status Toggle" (Feature C)
            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            $user->update(['status' => $newStatus]); // Uses 'status' from $fillable (Source 105)
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
}

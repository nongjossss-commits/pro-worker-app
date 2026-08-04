<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Login credentials for the 3 dedicated Pro Walker Labor roles
 * (labor-accounting, labor-shareholder, labor-team — e.g. team leads).
 *
 * Kept separate from Admin\UserController on purpose: account creation for
 * this module is Super Admin's exclusive responsibility (route already
 * gated by `role:super-admin` in routes/labor.php), and it's convenient to
 * manage them without leaving the module. Admin\UserController still works
 * too — both write to the same `users` table, so there's no duplication of
 * data, just two entry points.
 */
class LaborUserController extends Controller
{
    protected const LABOR_ROLES = ['labor-accounting', 'labor-shareholder', 'labor-team'];

    public function index()
    {
        $users = User::role(self::LABOR_ROLES)
            ->with(['roles', 'laborTeam'])
            ->orderBy('name')
            ->get();

        return view('labor.users.index', compact('users'));
    }

    public function create()
    {
        $teams = LaborTeam::orderBy('name')->get();
        return view('labor.users.create', compact('teams'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_name' => ['required', Rule::in(self::LABOR_ROLES)],
            'labor_team_id' => ['nullable', 'required_if:role_name,labor-team', Rule::exists('labor_teams', 'id')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'labor_team_id' => $validated['role_name'] === 'labor-team' ? $validated['labor_team_id'] : null,
        ]);

        $user->assignRole($validated['role_name']);

        return redirect()->route('labor.users.index')->with('success', 'สร้างบัญชีเรียบร้อยแล้ว');
    }

    public function edit(User $user)
    {
        abort_unless($user->hasAnyRole(self::LABOR_ROLES), 404);

        $teams = LaborTeam::orderBy('name')->get();
        return view('labor.users.edit', compact('user', 'teams'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->hasAnyRole(self::LABOR_ROLES), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_name' => ['required', Rule::in(self::LABOR_ROLES)],
            'labor_team_id' => ['nullable', 'required_if:role_name,labor-team', Rule::exists('labor_teams', 'id')],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'labor_team_id' => $validated['role_name'] === 'labor-team' ? $validated['labor_team_id'] : null,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$validated['role_name']]);

        return redirect()->route('labor.users.index')->with('success', 'อัปเดตบัญชีเรียบร้อยแล้ว');
    }

    public function toggleStatus(User $user)
    {
        abort_unless($user->hasAnyRole(self::LABOR_ROLES), 404);

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'อัปเดตสถานะบัญชีเรียบร้อยแล้ว');
    }
}

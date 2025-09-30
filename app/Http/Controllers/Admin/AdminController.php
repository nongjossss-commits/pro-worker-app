<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;       // เพิ่มบรรทัดนี้
use Spatie\Permission\Models\Permission; // เพิ่มบรรทัดนี้

class AdminController extends Controller
{
    /**
     * Display a listing of the roles and permissions.
     */
    public function indexRolesAndPermissions()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return view('admin.roles_permissions.index', compact('roles', 'permissions'));
    }
}
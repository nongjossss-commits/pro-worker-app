<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\View\View; // เพิ่มบรรทัดนี้

class AdminController extends Controller
{
    /**
     * Display a listing of the roles and permissions.
     */
    public function indexRolesAndPermissions(): View // เปลี่ยน Return Type เป็น View
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        // เปลี่ยนวิธีการ return view ให้ถูกต้องตามมาตรฐาน
        return view('admin.roles_permissions.index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
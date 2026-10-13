<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('id')->get();
        $permissions = Permission::orderBy('module')->orderBy('id')->get()->groupBy('module');

        $activeRole = Role::with('permissions')->find(request('role', $roles->first()->id ?? null));

        return view('roles.index', compact('roles', 'permissions', 'activeRole'));
    }

    public function update(Request $request, Role $role)
    {
        $permissionIds = $request->input('permissions', []);

        $role->permissions()->sync($permissionIds);

        return back()->with('status', 'Perubahan hak akses untuk role '.$role->name.' tersimpan.');
    }
}

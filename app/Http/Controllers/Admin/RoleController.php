<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\DataTables;

class RoleController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            // $roles = Role::all();
            $roles = Role::whereNull('parent_id')->get();
            return DataTables::of($roles)
                ->addIndexColumn()
                // ->addColumn('permissions', function ($row) {
                //     return $row->permissions->pluck('name')->implode(', ');
                // })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.roles.edit', $row->id),
                            'delete' => route('admin.roles.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.roles.index', [
            'title' => "Roles",
            'breadcrumbs' => [
                'Roles' => route('admin.roles.index'),
            ]
        ]);
    }

    public function create()
    {
        $permissions = Permission::all();
        $subAdmins = User::where('type', 'sub-admin')->get();
        return view('admin.roles.create', [
            'title' => "Create Role",
            'permissions' => $permissions,
            'subAdmins' => $subAdmins,
            'breadcrumbs' => [
                'Roles' => route('admin.roles.index'),
                'Create' => route('admin.roles.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array',
            'users' => 'array'
        ]);

        $role = Role::create(['name' => $request->name]);
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        if ($request->has('users')) {
            $users = User::whereIn('id', $request->users)->get();
            foreach ($users as $user) {
                $user->assignRole($role->name);
            }
        }

        flash_session('success', 'Role created successfully.');
        return redirect()->route('admin.roles.index');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $subAdmins = User::where('type', 'sub-admin')->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $roleUsers = User::role($role->name)->where('type', 'sub-admin')->pluck('id')->toArray();
        
        return view('admin.roles.edit', [
            'title' => "Edit Role",
            'role' => $role,
            'permissions' => $permissions,
            'subAdmins' => $subAdmins,
            'rolePermissions' => $rolePermissions,
            'roleUsers' => $roleUsers,
            'breadcrumbs' => [
                'Roles' => route('admin.roles.index'),
                'Edit' => route('admin.roles.edit', $role->id),
            ]
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'users' => 'array'
        ]);

        $role->name = $request->name;
        $role->save();

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        // Sync Sub-Admin users for this role
        $selectedUsers = $request->users ?? [];
        
        // Remove role from sub-admins who are no longer selected
        $currentSubAdminUsers = User::where('type', 'sub-admin')->role($role->name)->get();
        foreach ($currentSubAdminUsers as $user) {
            if (!in_array($user->id, $selectedUsers)) {
                $user->removeRole($role->name);
            }
        }

        // Add role to newly selected sub-admins
        if (!empty($selectedUsers)) {
            $usersToAssign = User::whereIn('id', $selectedUsers)->get();
            foreach ($usersToAssign as $user) {
                $user->assignRole($role->name);
            }
        }

        flash_session('success', 'Role updated successfully.');
        return redirect()->route('admin.roles.index');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json([
            'success' => true,
            'message' => __('Role has been successfully deleted.'),
        ]);
    }
}

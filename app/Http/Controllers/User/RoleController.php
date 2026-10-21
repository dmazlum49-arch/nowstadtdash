<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    protected $businessPermissions = [
        'business-list',
        'service-list',
        'nowis-point-list',
        'stamp-card-list',
        'my-event-list',
        'my-post-list',
        'notification-list',
        'role-list',
        'sub-user-list',
        'two-factor-auth',
    ];

    public function index()
    {
        if (request()->ajax()) {
            $roles = Role::where('parent_id', Auth::user()->primaryId())->get();
            return DataTables::of($roles)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('user.roles.edit', $row->id),
                            'delete' => route('user.roles.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('user.roles.index', [
            'title' => "Roles",
            'breadcrumbs' => [
                'Roles' => route('user.roles.index'),
            ]
        ]);
    }

    public function create()
    {
        $permissions = Permission::whereIn('name', $this->businessPermissions)->get();
        $subBusinessUsers = User::where('type', 'sub-businessuser')->where('parent_id', Auth::user()->primaryId())->get();
        return view('user.roles.create', [
            'title' => "Create Role",
            'permissions' => $permissions,
            'subBusinessUsers' => $subBusinessUsers,
            'breadcrumbs' => [
                'Roles' => route('user.roles.index'),
                'Create' => route('user.roles.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array'
        ]);

        $roleName = $request->name . ' (Business ' . Auth::user()->primaryId() . ')';
        
        // Check if role with this name exists for this user
        $exists = Role::where('name', $roleName)->where('parent_id', Auth::user()->primaryId())->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['name' => 'Role name already exists.']);
        }

        $role = Role::create([
            'name' => $roleName,
            'parent_id' => Auth::user()->primaryId(),
            'guard_name' => 'web'
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        if ($request->has('users')) {
            $users = User::whereIn('id', $request->users)->where('parent_id', Auth::user()->primaryId())->get();
            foreach ($users as $user) {
                $user->assignRole($role->name);
            }
        }

        flash_session('success', 'Role created successfully.');
        return redirect()->route('user.roles.index');
    }

    public function edit(Role $role)
    {
        if ($role->parent_id !== Auth::user()->primaryId()) {
            abort(403);
        }

        $permissions = Permission::whereIn('name', $this->businessPermissions)->get();
        $subBusinessUsers = User::where('type', 'sub-businessuser')->where('parent_id', Auth::user()->primaryId())->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $roleUsers = User::role($role->name)->where('parent_id', Auth::user()->primaryId())->pluck('id')->toArray();
        
        // Stripping the business suffix for display if needed, but the user likely wants to see just the name they gave
        $displayName = str_replace(' (Business ' . Auth::user()->primaryId() . ')', '', $role->name);

        return view('user.roles.edit', [
            'title' => "Edit Role",
            'role' => $role,
            'displayName' => $displayName,
            'permissions' => $permissions,
            'subBusinessUsers' => $subBusinessUsers,
            'rolePermissions' => $rolePermissions,
            'roleUsers' => $roleUsers,
            'breadcrumbs' => [
                'Roles' => route('user.roles.index'),
                'Edit' => route('user.roles.edit', $role->id),
            ]
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->parent_id !== Auth::user()->primaryId()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array'
        ]);

        $roleName = $request->name . ' (Business ' . Auth::user()->primaryId() . ')';

        $role->name = $roleName;
        $role->save();

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        // Sync Sub-Business users for this role
        $selectedUsers = $request->users ?? [];
        
        // Remove role from sub-admins who are no longer selected
        $currentSubUsers = User::where('parent_id', Auth::user()->primaryId())->role($role->name)->get();
        foreach ($currentSubUsers as $user) {
            if (!in_array($user->id, $selectedUsers)) {
                $user->removeRole($role->name);
            }
        }

        // Add role to newly selected sub-admins
        if (!empty($selectedUsers)) {
            $usersToAssign = User::whereIn('id', $selectedUsers)->where('parent_id', Auth::user()->primaryId())->get();
            foreach ($usersToAssign as $user) {
                $user->assignRole($role->name);
            }
        }

        flash_session('success', 'Role updated successfully.');
        return redirect()->route('user.roles.index');
    }

    public function destroy(Role $role)
    {
        if ($role->parent_id !== Auth::user()->primaryId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $role->delete();
        return response()->json([
            'success' => true,
            'message' => __('Role has been successfully deleted.'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\DataTables;

class PermissionController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $permissions = Permission::all();
            return DataTables::of($permissions)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.permissions.edit', $row->id),
                            'delete' => route('admin.permissions.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.permissions.index', [
            'title' => "Permissions",
            'breadcrumbs' => [
                'Permissions' => route('admin.permissions.index'),
            ]
        ]);
    }

    public function create()
    {
        return view('admin.permissions.create', [
            'title' => "Create Permission",
            'breadcrumbs' => [
                'Permissions' => route('admin.permissions.index'),
                'Create' => route('admin.permissions.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name]);

        flash_session('success', 'Permission created successfully.');
        return redirect()->route('admin.permissions.index');
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', [
            'title' => "Edit Permission",
            'permission' => $permission,
            'breadcrumbs' => [
                'Permissions' => route('admin.permissions.index'),
                'Edit' => route('admin.permissions.edit', $permission->id),
            ]
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
        ]);

        $permission->name = $request->name;
        $permission->save();

        flash_session('success', 'Permission updated successfully.');
        return redirect()->route('admin.permissions.index');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json([
            'success' => true,
            'message' => __('Permission has been successfully deleted.'),
        ]);
    }
}

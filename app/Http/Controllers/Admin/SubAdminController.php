<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class SubAdminController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $users = User::where('type', 'sub-admin')->get();
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('profile_image', function ($row) {
                    return get_fancy_box_html($row->profile_image);
                })
                ->addColumn('status', function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.sub-admins.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.sub-admins.edit', $row->id),
                            'delete' => route('admin.sub-admins.destroy', $row->id),
                        ]
                    ];
                    
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['profile_image', 'status', 'action'])
                ->make(true);
        }

        return view('admin.sub-admins.index', [
            'title' => "Sub Admins",
            'breadcrumbs' => [
                'Sub Admins' => route('admin.sub-admins.index'),
            ]
        ]);
    }

    public function create()
    {
        return view('admin.sub-admins.create', [
            'title' => "Create Sub Admin",
            'breadcrumbs' => [
                'Sub Admins' => route('admin.sub-admins.index'),
                'Create' => route('admin.sub-admins.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'confirmed'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif'],
        ]);

        $profileImagePath = null;
        if ($request->hasFile('profile_image')) {
            $profileImagePath = upload_file('profile_image', 'profile_images');
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'profile_image' => $profileImagePath,
            'type' => 'sub-admin',
            'status' => 'active',
            'username' => generateUsername($request->name),
        ]);

        flash_session('success', 'Sub Admin created successfully.');
        return redirect()->route('admin.sub-admins.index');
    }

    public function edit(User $sub_admin)
    {
        if ($sub_admin->type !== 'sub-admin') {
            abort(404);
        }

        return view('admin.sub-admins.edit', [
            'title' => "Edit Sub Admin",
            'data' => $sub_admin,
            'breadcrumbs' => [
                'Sub Admins' => route('admin.sub-admins.index'),
                'Edit' => route('admin.sub-admins.edit', $sub_admin->id),
            ]
        ]);
    }

    public function update(Request $request, User $sub_admin)
    {
        if ($sub_admin->type !== 'sub-admin') {
            abort(404);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($sub_admin->id)->whereNull('deleted_at')],
            'password' => ['nullable', 'string'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        if ($request->hasFile('profile_image')) {
            if ($sub_admin->getRawOriginal('profile_image')) {
                un_link_file($sub_admin->getRawOriginal('profile_image'));
            }
            $data['profile_image'] = upload_file('profile_image', 'profile_images');
        }

        $sub_admin->update($data);

        flash_session('success', 'Sub Admin updated successfully.');
        return redirect()->route('admin.sub-admins.index');
    }

    public function destroy(User $sub_admin)
    {
        if ($sub_admin->type !== 'sub-admin') {
            return response()->json(['success' => false, 'message' => 'Not a sub-admin.']);
        }

        if ($sub_admin->getRawOriginal('profile_image')) {
            un_link_file($sub_admin->getRawOriginal('profile_image'));
        }
        
        $sub_admin->forceDelete();
        
        return response()->json([
            'success' => true,
            'message' => __('Sub Admin has been successfully deleted.'),
        ]);
    }

    public function updateStatus($id)
    {
        $user = User::find($id);
        if ($user && $user->type === 'sub-admin') {
            $user->update(['status' => ($user->status == "inactive") ? "active" : "inactive"]);
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'User not found.']);
    }
}

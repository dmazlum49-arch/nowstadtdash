<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;

class SubBusinessUserController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $users = User::where('type', 'sub-businessuser')
                ->where('parent_id', Auth::id())
                ->get();
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('profile_image', function ($row) {
                    return get_fancy_box_html($row->profile_image);
                })
                ->addColumn('status', function ($row) {
                    return get_generate_switch($row->status, $row->id, route('user.sub-business-users.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('user.sub-business-users.edit', $row->id),
                            'delete' => route('user.sub-business-users.destroy', $row->id),
                        ]
                    ];
                    
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['profile_image', 'status', 'action'])
                ->make(true);
        }

        return view('user.sub-business-users.index', [
            'title' => "Sub Business Users",
            'breadcrumbs' => [
                'Sub Business Users' => route('user.sub-business-users.index'),
            ]
        ]);
    }

    public function create()
    {
        return view('user.sub-business-users.create', [
            'title' => "Create Sub Business User",
            'breadcrumbs' => [
                'Sub Business Users' => route('user.sub-business-users.index'),
                'Create' => route('user.sub-business-users.create'),
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
            'type' => 'sub-businessuser',
            'parent_id' => Auth::id(),
            'status' => 'active',
            'username' => generateUsername($request->name),
        ]);

        flash_session('success', 'Sub Business User created successfully.');
        return redirect()->route('user.sub-business-users.index');
    }

    public function edit($id)
    {
        $sub_business_user = User::where('id', $id)
            ->where('parent_id', Auth::id())
            ->where('type', 'sub-businessuser')
            ->firstOrFail();

        return view('user.sub-business-users.edit', [
            'title' => "Edit Sub Business User",
            'data' => $sub_business_user,
            'breadcrumbs' => [
                'Sub Business Users' => route('user.sub-business-users.index'),
                'Edit' => route('user.sub-business-users.edit', $sub_business_user->id),
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $sub_business_user = User::where('id', $id)
            ->where('parent_id', Auth::id())
            ->where('type', 'sub-businessuser')
            ->firstOrFail();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($sub_business_user->id)->whereNull('deleted_at')],
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
            if ($sub_business_user->getRawOriginal('profile_image')) {
                un_link_file($sub_business_user->getRawOriginal('profile_image'));
            }
            $data['profile_image'] = upload_file('profile_image', 'profile_images');
        }

        $sub_business_user->update($data);

        flash_session('success', 'Sub Business User updated successfully.');
        return redirect()->route('user.sub-business-users.index');
    }

    public function destroy($id)
    {
        $sub_business_user = User::where('id', $id)
            ->where('parent_id', Auth::id())
            ->where('type', 'sub-businessuser')
            ->first();

        if (!$sub_business_user) {
            return response()->json(['success' => false, 'message' => 'Not a sub-business user or not owned by you.']);
        }

        if ($sub_business_user->getRawOriginal('profile_image')) {
            un_link_file($sub_business_user->getRawOriginal('profile_image'));
        }
        
        $sub_business_user->forceDelete();
        
        return response()->json([
            'success' => true,
            'message' => __('Sub Business User has been successfully deleted.'),
        ]);
    }

    public function updateStatus($id)
    {
        $user = User::where('id', $id)
            ->where('parent_id', Auth::id())
            ->where('type', 'sub-businessuser')
            ->first();

        if ($user) {
            $user->update(['status' => ($user->status == "inactive") ? "active" : "inactive"]);
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'User not found.']);
    }
}

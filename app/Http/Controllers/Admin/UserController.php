<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $users = User::whereIn('type', ['user'])->get();
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('profile_image', function ($row) {
                    return get_fancy_box_html($row->profile_image);
                })
                ->addColumn('mobile', function ($row) {
                    return $row->country_code . ' ' . $row->mobile;
                })
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.users.update-status', $row->id));

                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            // 'edit' => route('admin.users.edit', $row->id),
                            'view' => route('admin.users.show', $row->id),
                            'delete' => route('admin.users.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['profile_image', 'status', 'action'])
                ->make(true);
        }

        return view('admin.users.index', [
            'title' => "Users",
            'breadcrumbs' => [
                'Users' => route('admin.users.index'),
            ]
        ]);
    }

    public function businessUserIndex()
    {
        if (request()->ajax()) {
            $users = User::whereIn('type', ['businessuser'])->get();
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('profile_image', function ($row) {
                    return get_fancy_box_html($row->profile_image);
                })
                ->addColumn('mobile', function ($row) {
                    return $row->country_code . ' ' . $row->mobile;
                })
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.users.update-status', $row->id));

                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            // 'edit' => route('admin.users.edit', $row->id),
                            'view' => route('admin.users.show', $row->id),
                            'delete' => route('admin.users.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['profile_image', 'status', 'action'])
                ->make(true);
        }

        return view('admin.users.businessUser', [
            'title' => "Business Users",
            'breadcrumbs' => [
                'Business Users' => route('admin.business-users.index'),
            ]
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'title' => "User",
            'breadcrumbs' => [
                'User' => route('admin.users.index'),
                'Create' => route('admin.users.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->whereNull('deleted_at')],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
        ]);
        flash_session('success', 'User created successfully.');
        return redirect()->route('admin.users.index');
    }

    public function show(User $user)
    {
        $user->load(['businesses', 'events']);
        return view('admin.users.show', [
            'title' => "User",
            'data' => $user,
            'breadcrumbs' => [
                'User' => route('admin.users.index'),
                'View' => route('admin.users.show', $user->id),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->update(['status' => ($user->status == "inactive") ? "active" : "inactive"]);
            $user->save();
            if ($user->status === 'inactive') {
                $user->tokens()->delete();
            }
            return response()->json(['success' => true, 'message' => 'User status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'User not found.']);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'title' => "User",
            'data' => $user,
            'breadcrumbs' => [
                'User' => route('admin.users.index'),
                'edit' => route('admin.users.edit', $user->id),
            ]
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        flash_session('success', 'User updated successfully.');
        return redirect()->route('admin.users.index');
    }

    public function destroy(User $user)
    {
        try {
            $user->posts()->delete();
            $user->businesses()->delete();
            $user->events()->delete();
            $user->delete();
            $user->forceDelete();
            return response()->json([
                'success' => true,
                'message' => __('User has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete user: ') . $e->getMessage(),
            ], 500);
        }
    }
    public function verifyUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }

        $user->is_verified = !$user->is_verified;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Business User verified successfully.']);
    }
}

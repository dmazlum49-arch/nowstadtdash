<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessPostPermission;
use App\Models\User;
use Google\Service\ServiceControl\Auth;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class BusinessPostPermissionController extends Controller
{
    public function index()
    {

       if (request()->ajax()) {
            $users = BusinessPostPermission::all();
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.business-post-permissions.edit', $row->id),
                            // 'view' => route('admin.business-post-permissions.show', $row->id),
                            'delete' => route('admin.business-post-permissions.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.businessuserPostPermission.index', [
            'title' => "Business Post Permissions",
            'breadcrumbs' => [
                'Business Post Permissions' => route('admin.business-post-permissions.index'),
            ]
        ]);
    }

    // public function show(BusinessPostPermission $businessPostPermission)
    // {
    //     return view('admin.businessuserPostPermission.show', [
    //         'title' => "Business Post Permission Details",
    //         'data' => $businessPostPermission,
    //         'breadcrumbs' => [
    //             'Business Post Permissions' => route('admin.business-post-permissions.index'),
    //             'View' => route('admin.business-post-permissions.show', $businessPostPermission->id),
    //         ],
    //     ]);
    // }

    public function create()
    {
        $BusinessUsers = User::where('type', 'businessuser')->get();
        return view('admin.businessuserPostPermission.create', [
            'title' => "Business Post Permissions",
            'BusinessUsers' => $BusinessUsers,
            'breadcrumbs' => [
                'Business Post Permissions' => route('admin.business-post-permissions.index'),
                'Create' => route('admin.business-post-permissions.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'max_posts' => 'nullable',
            'max_events' => 'nullable',
            'max_push_notifications' => 'nullable',
        ]);

        $input = $request->city_name;

        BusinessPostPermission::create([
            'user_id' => $request->user_id,
            'max_posts' => $request->max_posts,
            'max_events' => $request->max_events,
            'max_push_notifications' => $request->max_push_notifications,
        ]);
        flash_session('success', __('Business post permission successfully'));
        return redirect()->route('admin.business-post-permissions.index');

    }

    public function edit(BusinessPostPermission $businessPostPermission)
    {
        $BusinessUsers = User::where('type', 'businessuser')->get();
        return view('admin.businessuserPostPermission.edit', [
            'title' => "Edit Business Post Permission",
            'data' => $businessPostPermission,
            'BusinessUsers' => $BusinessUsers,
            'breadcrumbs' => [
                'Business Post Permissions' => route('admin.business-post-permissions.index'),
                'Edit' => route('admin.business-post-permissions.edit', $businessPostPermission->id),
            ],
        ]);
    }

    public function update(Request $request, BusinessPostPermission $businessPostPermission)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'max_posts' => 'nullable',
            'max_events' => 'nullable',
            'max_push_notifications' => 'nullable',
        ]);

        $businessPostPermission->update([
            'user_id' => $request->user_id,
            'max_posts' => $request->max_posts,
            'max_events' => $request->max_events,
            'max_push_notifications' => $request->max_push_notifications,
        ]);
        flash_session('success', 'Business Post Permission updated successfully.');
        return redirect()->route('admin.business-post-permissions.index');
    }

    public function destroy(BusinessPostPermission $businessPostPermission)
    {
        try {
            // $businessPostPermission->delete();
            BusinessPostPermission::where('id', $businessPostPermission->id)->delete();
            return response()->json([
                'success' => true,
                'message' => __('Business Post Permission has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Business Post Permission: ') . $e->getMessage(),
            ], 500);
        }
    }
}

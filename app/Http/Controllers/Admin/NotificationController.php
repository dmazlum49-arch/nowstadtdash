<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EventRejectedMail;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventImages;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function create()
    {
        $users = User::whereIn('type', ['user'])->get();
        return view('admin.notification.create', [
            'title' => "Notifications",
            'users' => $users,
            'breadcrumbs' => [
                'Send Push' => route('admin.notification.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'     => 'required|string|max:150',
            'message'   => 'required|string',
            'type'      => 'required|in:all,selected',
            'link'      => 'nullable|url',
            'user_ids'  => 'required_if:type,selected|array',
            'user_ids.*'=> 'exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $userIds = [];

            if ($request->type === 'all') {
                $userIds = User::whereIn('type', ['user'])->pluck('id')->toArray();
            } else {
                $userIds = $request->user_ids;
            }

            foreach ($userIds as $userId) {
                send_push($userId, [
                    'from_user_id' => auth()->id(),
                    'push_type'    => 8,
                    'push_title'   => $request->title,
                    'push_message' => $request->message,
                    'extra'        => ['link' => $request->link],
                ], false);
            }

            DB::commit();

            flash_session('success', __('Push notification sent successfully.'));
            return redirect()->back();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()]);
        }
    }

}
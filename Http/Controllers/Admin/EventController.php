<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EventRejectedMail;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Str;
use Yajra\DataTables\Facades\DataTables;

class EventController extends Controller
{
    public function pending()
    {
        if (request()->ajax()) {
            $event = Event::with('category')->where('type', 'pending')->get();
            return DataTables::of($event)
                ->addIndexColumn()
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })
                ->addColumn('event_category_id', function ($row) {
                    return $row->category->event_category ?? '-';
                })
                ->addColumn('event_name', function ($row) {
                    return $row->event_name;
                })
                ->addColumn('location', function ($row) {
                    return $row->location ? Str::limit($row->location, 43) : '-';
                })
                ->addColumn('about_event', function ($row) {
                    $full = strip_tags($row->about_event);
                    $short = Str::limit($full, 25);
                    return '<span class="desc-preview" style="cursor:pointer;" data-full="' . e($full) . '">' . $short . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $acceptUrl = route('admin.event.accept', $row->id);
                    $rejectUrl = route('admin.event.reject', $row->id);
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('admin.event.show', $row->id),
                        ]
                    ];
                    $defaultButtons = generate_actions_buttons($params);

                    // Custom Accept/Reject buttons
                    $customButtons = '
                        <a href="' . $acceptUrl . '" class="font-size-14 me-2 btn btn-sm btn-outline-success waves-effect waves-light btnAction" data-action="accept">
                            <i class="fas fa-check"></i>
                        </a>
                        <a href="' . $rejectUrl . '" class="font-size-14 me-2 btn btn-sm btn-outline-danger waves-effect waves-light btnAction" data-action="reject">
                            <i class="fas fa-times"></i>
                        </a>
                    ';

                    // Combine and return
                    return $defaultButtons . $customButtons;
                })
                ->rawColumns(['action', 'about_event'])
                ->make(true);
        }

        return view('admin.events.pending_index', [
            'title' => "Pending Event",
            'breadcrumbs' => [
                'Pending Event' => route('admin.pending.event'),
            ]
        ]);
    }

    public function approved()
    {
        if (request()->ajax()) {
            $event = Event::with('category')->where('type', 'approved')->get();
            return DataTables::of($event)
                ->addIndexColumn()
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })
                ->addColumn('event_category_id', function ($row) {
                    return $row->category->event_category ?? '-';
                })
                ->addColumn('event_name', function ($row) {
                    return $row->event_name;
                })
                ->addColumn('location', function ($row) {
                    return $row->location ? Str::limit($row->location, 43) : '-';
                })
                // ->addColumn('about_event', function ($row) {
                //     return $row->about_event ?? '-';
                // })
                ->addColumn('about_event', function ($row) {
                    $full = strip_tags($row->about_event);
                    $short = Str::limit($full, 25);
                    return '<span class="desc-preview" style="cursor:pointer;" data-full="' . e($full) . '">' . $short . '</span>';
                })
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.event.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('admin.event.show', $row->id),
                            'delete' => route('admin.event.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['about_event', 'status', 'action'])
                ->make(true);
        }

        return view('admin.events.approved_index', [
            'title' => "Approved Event",
            'breadcrumbs' => [
                'Approved Event' => route('admin.approved.event'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $event = Event::find($id);
        if ($event) {
            $event->update(['status' => ($event->status == "inactive") ? "active" : "inactive"]);
            $event->save();
            return response()->json(['success' => true, 'message' => 'Event status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Event Category not found.']);
    }

    public function show(Event $id)
    {
        $event = $id->load('eventImages');
        return view('admin.events.show', [
            'title' => "Event",
            'data' => $event,
            'breadcrumbs' => [
                'Event' => route('admin.pending.event'),
                'View' => route('admin.event.show', $id->id),
            ],
        ]);
    }

    public function destroy($id)
    {
        try {
            $event = Event::find($id);
            $event->delete();
            return response()->json([
                'success' => true,
                'message' => __('Event has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Event: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function accept($id)
    {
        // Event::where('id', $id)->update(['type' => 'approved']);
        $event = Event::findOrFail($id);

        $event->update(['type' => 'approved']);

        // Send email
        $user = $event->user;
        // dd($user);
        Mail::to($user->email)->send(new \App\Mail\EventApprovedMail($user, $event->event_name));

        $users = User::where('type', 'user')->get();

        foreach ($users as $user) {
            $locale = $user->language ?? 'en';
            App::setLocale($locale);
            send_push($user->id, [
                'push_type' => 1,
                'push_title' => __('admin.event_published_title'),
                'push_message' => $event->event_name ?? __('admin.event_published_message'),
                'from_user_id' => auth()->id(),
                'extra' => [
                    'event_id' => $event->id,
                ],
            ], true);
        }
        return redirect()->back()->with('success', 'Event accepted successfully.');
    }

    public function reject(Request $request, $id)
    {
        $event = Event::with('user')->findOrFail($id);

        $event->type = 'rejected';
        $event->save();

        $user = $event->user;
        // Send email
        Mail::to($user->email)->send(new EventRejectedMail($user, $event->event_name, $request->input('reason')));

        return redirect()->back()->with('success', 'Event rejected and notified via email.');
    }
}

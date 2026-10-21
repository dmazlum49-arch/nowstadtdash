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

class MyEventController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $event = Event::with('category')->where('user_id', Auth::id())->get();
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
                    
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('admin.my-event.show', $row->id),
                            'edit' => route('admin.my-event.edit', $row->id),
                            'delete' => route('admin.my-event.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);

                })
                ->rawColumns(['action', 'about_event'])
                ->make(true);
        }

        return view('admin.event.index', [
            'title' => "Event",
            'breadcrumbs' => [
                'Event' => route('admin.my-event.index'),
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

    public function show(Event $my_event)
    {
        $my_event = $my_event->load('eventImages');
        return view('admin.event.show', [
            'title' => "Event",
            'data' => $my_event,
            'breadcrumbs' => [
                'Event' => route('admin.my-event.index'),
                'View' => route('admin.my-event.show', $my_event),
            ],
        ]);
    }

    public function create()
    {
        $categories = EventCategory::where('status', 'active')->get();
        return view('admin.event.create', [
            'title' => "Add Event",
            'breadcrumbs' => [
                'Event' => route('admin.my-event.index'),
                'Add' => route('admin.my-event.create'),
            ],
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        $request->validate([
            'event_category_id' => ['required', 'exists:event_categories,id'],
            'event_name' => ['required', 'max:100'],
            'event_type' => ['nullable', 'max:100'],
            'about_event' => ['required'],
            'location' => ['required', 'max:255'],
            'latitude' => ['nullable'],
            'longitude' => ['nullable'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'banner_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp'],
            'event_images.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp'],
            'event_id' => ['nullable', 'exists:events,id'],
        ]);

        
        $event = Event::create([
            'user_id' => $userId,
            'event_category_id' => $request->event_category_id,
            'event_name' => $request->event_name,
            'event_type' => $request->event_type,
            'about_event' => $request->about_event,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'type' => 'approved',
        ]);

        $message = __('api.success_event_created');

        if ($request->hasFile('banner_image')) {
            $bannerImagePath = upload_file('banner_image', 'event_images');

            if ($event->banner_image) {
                un_link_file($event->banner_image);
            }

            $event->update(['banner_image' => $bannerImagePath]);
        }

        if ($request->hasFile('event_images')) {

            foreach ($request->file('event_images') as $index => $image) {
                $imagePath = upload_file("event_images.{$index}", 'event_images');

                EventImages::create([
                    'event_id' => $event->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        $supportMail = GeneralSetting::where('unique_name', 'SUPPORT_EMAIL')->first()->value ?? '';
        
        if (!$request->filled('event_id') && $supportMail) {
            
         Mail::to($supportMail)->send(new \App\Mail\EventCreateMail($request->user(), $event));
        }

        flash_session('success', 'event create successfully.');
        return redirect()->route('admin.my-event.index');
        
    }

    public function edit(Event $my_event)
    {
        $categories = EventCategory::where('status', 'active')->get();
        return view('admin.event.edit', [
            'title' => "Edit Event",
            'event' => $my_event,
            'breadcrumbs' => [
                'Event' => route('admin.my-event.index'),
                'Edit' => route('admin.my-event.edit', $my_event->id),
            ],
            'categories' => $categories
        ]);
    }

    public function update(Request $request, Event $my_event)
    {
        $request->validate([
            'event_category_id' => ['required', 'exists:event_categories,id'],
            'event_name' => ['required', 'max:100'],
            'event_type' => ['nullable', 'max:100'],
            'about_event' => ['required'],
            'location' => ['required', 'max:255'],
            'latitude' => ['nullable'],
            'longitude' => ['nullable'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'banner_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp'],
            'event_images.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp'],
            'event_id' => ['nullable', 'exists:events,id'],
        ]);

        $my_event->update([
            'event_category_id' => $request->event_category_id,
            'event_name' => $request->event_name,
            'event_type' => $request->event_type,
            'about_event' => $request->about_event,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        if ($request->hasFile('banner_image')) {
            $bannerImagePath = upload_file('banner_image', 'event_images');

            if ($my_event->banner_image) {
                un_link_file($my_event->banner_image);
            }

            $my_event->update(['banner_image' => $bannerImagePath]);
        }

        if ($request->hasFile('event_images')) {

            foreach ($request->file('event_images') as $index => $image) {
                $imagePath = upload_file("event_images.{$index}", 'event_images');

                EventImages::create([
                    'event_id' => $my_event->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        flash_session('success', 'event updated successfully.');
        return redirect()->route('admin.my-event.index');
    }

    public function destroy($id)
    {
        try {
            $event = Event::find($id);
            $event->eventImages()->delete();
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

}

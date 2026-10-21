<?php

namespace App\Http\Controllers\User;

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

class EventController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $event = Event::with('category')->where('user_id', Auth::user()->primaryId())->get();
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
                            'view' => route('user.event.show', $row->id),
                            'edit' => route('user.event.edit', $row->id),
                            'delete' => route('user.event.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);

                })
                ->rawColumns(['action', 'about_event'])
                ->make(true);
        }

        return view('user.event.index', [
            'title' => "Event",
            'breadcrumbs' => [
                'Event' => route('user.event.index'),
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

    public function show(Event $event)
    {
        $event = $event->load('eventImages');
        return view('user.event.show', [
            'title' => "Event",
            'data' => $event,
            'breadcrumbs' => [
                'Event' => route('user.event.index'),
                'View' => route('user.event.show', $event),
            ],
        ]);
    }

    public function create()
    {
        $categories = EventCategory::where('status', 'active')->get();
        return view('user.event.create', [
            'title' => "Add Event",
            'breadcrumbs' => [
                'Event' => route('user.event.index'),
                'Add' => route('user.event.create'),
            ],
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::user()->primaryId();
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
        return redirect()->route('user.event.index');
        
    }

    public function edit(Event $event)
    {
        $categories = EventCategory::where('status', 'active')->get();
        return view('user.event.edit', [
            'title' => "Edit Event",
            'event' => $event,
            'breadcrumbs' => [
                'Event' => route('user.event.index'),
                'Edit' => route('user.event.edit', $event->id),
            ],
            'categories' => $categories
        ]);
    }

    public function update(Request $request, Event $event)
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

        $event->update([
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

        flash_session('success', 'event updated successfully.');
        return redirect()->route('user.event.index');
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

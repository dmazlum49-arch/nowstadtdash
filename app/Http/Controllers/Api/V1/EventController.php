<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\Api\ResponseController;
use App\Models\BlockUser;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventImages;
use App\Models\EventUserJoin;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\GeneralSetting;

class EventController extends ResponseController
{
    public function createEditEvent(Request $request)
    {
        $userId = $request->user()->id;
        $this->directValidation([
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
        $user = $request->user();

        $limits = getBusinessUserFinalPostLimits($user->id);

        if (!$limits['has_subscription']) {
            return response()->json([
                'status' => 403,
                'message' => $limits['message']
            ]);
        }

        // ⭐ FINAL EVENT LIMIT (merging admin + plan)
        $maxEvents = $limits['max_events']; // null = unlimited

        if ($user->events()->count() >= $maxEvents) {
            return response()->json([
                'status' => 403,
                'message' => 'Event limit reached. Please upgrade your subscription.'
            ]);
        }
        if ($request->filled('event_id')) {
            $event = Event::findOrFail($request->event_id);
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

            $message = __('api.success_event_updated');

        } else {
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
        }

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

        return ResponseHelper::send(200, $message, [
            'id' => $event->id,
            'event_name' => $event->event_name,
            'about_event' => $event->about_event,
            'event_type' => $event->event_type,
            'category' => $event->category->event_category ?? null,
            'location' => $event->location,
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'banner_image' => $event->banner_image ? url($event->banner_image) : null,
            'event_images' => $event->eventImages->map(function ($img) {
                return [
                    'id' => $img->id,
                    'image_path' => $img->image_path ? url($img->image_path) : null,
                ];
            }),
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
        ]);
    }

    public function myEventList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $type = $request->type;
        $events = Event::with([
            'eventImages',
            'category',
            'joinedUsers' => function ($q) {
                $q->select('users.profile_image');
            }
        ])
            ->where(['user_id' => $userId,'type'=> $type])->latest()->limit($limit)->offset($offset)->get();

        $filteredEvents = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'event_image' => $event->banner_image ? url($event->banner_image) : null,
                'category' => $event->category->event_category ?? null,
                'start_date' => $event->start_date,
                'start_time' => $event->start_time,
                'joined_users' => $event->joinedUsers->take(3)->map(function ($user) {
                    return [
                        'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                    ];
                }),
                'joined_count' => $event->joinedUsers->count(),
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success'), $filteredEvents);
    }

    // public function getAllEventList(Request $request)
    // {
    //     $user = $request->user();
    //     // $userId = $request->user()->id;
    //     $userId = $user ? $user->id : null;
    //     $limit = $request->limit ?? 10;
    //     $offset = $request->offset ?? 0;
    //     // $blockedUserIds = BlockUser::where('user_id', $user->id)->pluck('block_user_id')->toArray();
    //     $blockedUserIds = $user
    //     ? BlockUser::where('user_id', $userId)->pluck('block_user_id')->toArray()
    //     : [];
    //     $events = Event::with([
    //         'eventImages',
    //         'category',
    //         'joinedUsers' => function ($q) {
    //             $q->select('users.profile_image');
    //         }
    //     ])->where('type', 'approved')->when($userId, function ($q) use ($userId) {
    //         $q->where('user_id', '!=', $userId); // sirf logged-in user ke events exclude karne hain
    //     })->whereNotIn('user_id', $blockedUserIds)
    //         ->latest()->limit($limit)->offset($offset)->get();

    //     $eventIds = $events->pluck('id')->toArray();

    //     // $favoriteEventIds = Favorite::where('user_id', $userId)
    //     //     ->where('type', 'event')
    //     //     ->whereIn('event_id', $eventIds)
    //     //     ->pluck('event_id')
    //     //     ->toArray();

    //     $favoriteEventIds = $user
    //     ? Favorite::where('user_id', $userId)
    //         ->where('type', 'event')
    //         ->whereIn('event_id', $eventIds)
    //         ->pluck('event_id')
    //         ->toArray()
    //     : [];

    //     $filteredEvents = $events->map(function ($event) use ($favoriteEventIds) {
    //         return [
    //             'id' => $event->id,
    //             'event_image' => $event->banner_image ? url($event->banner_image) : null,
    //             'category' => $event->category->event_category ?? null,
    //             'start_date' => $event->start_date,
    //             'start_time' => $event->start_time,
    //             'is_favorite' => in_array($event->id, $favoriteEventIds),
    //             'joined_users' => $event->joinedUsers->take(3)->map(function ($user) {
    //                 return [
    //                     // 'id' => $user->id,
    //                     // 'name' => $user->name,
    //                     'profile_image' => $user->profile_image ? url($user->profile_image) : null,
    //                 ];
    //             }),
    //             'joined_count' => $event->joinedUsers->count(),
    //             'created_at' => $event->created_at,
    //             'updated_at' => $event->updated_at,
    //         ];
    //     });

    //     return ResponseHelper::send(200, __('api.success'), $filteredEvents);
    // }


    public function getAllEventList(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $date = $request->date;
        $categoryId = $request->category_id;
        $today = now()->toDateString();

        $blockedUserIds = $user
            ? BlockUser::where('user_id', $userId)->pluck('block_user_id')->toArray()
            : [];

        $events = Event::with([
            'eventImages',
            'category',
            'joinedUsers' => function ($q) {
                $q->select('users.profile_image');
            }
        ])
            ->where('type', 'approved')
            // ->when($userId, function ($q) use ($userId) {
            //     $q->where('user_id', '!=', $userId); // exclude logged-in user's events
            // })
            ->whereNotIn('user_id', $blockedUserIds);

            $events->where(function ($q) use ($today) {
        $q->where(function ($sub) use ($today) {
            $sub->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today);
        })
        ->orWhere(function ($sub) use ($today) {
            $sub->whereDate('start_date', '>=', $today);
        });
    });

        // 🗓 Filter based on date
        if (!empty($date)) {
            $events->whereDate('start_date', $date);
        }

        // 🏷 Filter based on category
        if (!empty($categoryId)) {
            $events->where('event_category_id', $categoryId);
        }

        // $events = $events->latest()->limit($limit)->offset($offset)->get();
        $events = $events->orderBy('start_date', 'asc')
        ->limit($limit)
        ->offset($offset)
        ->get();

        $eventIds = $events->pluck('id')->toArray();

        $favoriteEventIds = $user
            ? Favorite::where('user_id', $userId)
                ->where('type', 'event')
                ->whereIn('event_id', $eventIds)
                ->pluck('event_id')
                ->toArray()
            : [];

        $filteredEvents = $events->map(function ($event) use ($favoriteEventIds) {
            return [
                'id' => $event->id,
                'event_image' => $event->banner_image ? url($event->banner_image) : null,
                'category' => $event->category->event_category ?? null,
                'start_date' => $event->start_date,
                'start_time' => $event->start_time,
                'is_favorite' => in_array($event->id, $favoriteEventIds),
                'joined_users' => $event->joinedUsers->take(3)->map(function ($user) {
                    return [
                        'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                    ];
                }),
                'joined_count' => $event->joinedUsers->count(),
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success'), $filteredEvents);
    }

    public function eventDetails(Request $request)
    {
        $this->directValidation([
            'event_id' => ['required', 'exists:events,id'],
        ]);

        $user = $request->user(); // null ho sakta hai
        $userId = $user ? $user->id : null;

        $event = Event::with([
            'eventImages',
            'category',
            'joinedUsers' => function ($q) {
                $q->select('users.profile_image');
            }
        ])
            ->where('id', $request->event_id)
            ->first();

        if (!$event) {
            return ResponseHelper::send(404, __('api.event_not_found'));
        }

        $isFavorite = false;
        $isUserJoined = false;

        if ($user) {
            $isFavorite = Favorite::where('user_id', $userId)
                ->where('event_id', $event->id)
                ->where('type', 'event')
                ->exists();

            $isUserJoined = $event->user_id == $userId
                ? true
                : EventUserJoin::where('event_id', $event->id)
                    ->where('user_id', $userId)
                    ->exists();
        }

        $filteredEvent = [
            'id' => $event->id,
            'event_name' => $event->event_name,
            'event_type' => $event->event_type,
            'about_event' => $event->about_event,
            'category_id' => $event->event_category_id,
            'category' => $event->category->event_category ?? null,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'is_user_join' => $isUserJoined,
            'location' =>  $event->location ,
            'latitude' =>  $event->latitude ,
            'longitude' =>  $event->longitude,
            'banner_image' => $event->banner_image ? url($event->banner_image) : null,
            'is_favorite' => $isFavorite,
            'joined_users' => $event->joinedUsers->take(3)->map(function ($user) {
                return [
                    'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                ];
            }),
            'joined_count' => $event->joinedUsers->count(),
            'event_images' => $event->eventImages->map(function ($img) {
                return [
                    'id' => $img->id,
                    'image_path' => $img->image_path ? url($img->image_path) : null,
                ];
            })->toArray(),
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
        ];

        return ResponseHelper::send(200, __('api.success'), $filteredEvent);
    }

    public function deleteEvent(Request $request)
    {
        $this->directValidation([
            'event_id' => ['required', 'exists:events,id'],
        ]);

        $event = Event::find($request->event_id);

        if (!$event) {
            return ResponseHelper::send(404, __('api.event_not_found'));
        }

        if ($event->banner_image) {
            un_link_file($event->banner_image);
        }

        // Delete associated images
        foreach ($event->eventImages as $image) {
            un_link_file($image->image_path);
            $image->delete();
        }

        // Delete the event
        $event->delete();

        return ResponseHelper::send(200, __('api.success_event_deleted'));
    }

    public function deleteImage(Request $request)
    {
        $rawImageIds = $request->input('image_id');
        $imageIds = [];

        if (is_array($rawImageIds)) {
            foreach ($rawImageIds as $value) {
                $imageIds = array_merge($imageIds, explode(',', $value));
            }
        }

        $imageIds = array_filter(array_unique(array_map('trim', $imageIds)));

        $validator = \Validator::make(['image_id' => $imageIds], [
            'image_id' => ['required', 'array'],
            'image_id.*' => ['required', 'exists:event_images,id'],
        ]);

        if ($validator->fails()) {
            return ResponseHelper::send(422, $validator->errors()->first());
        }

        $images = EventImages::whereIn('id', $imageIds)->get();

        if ($images->isEmpty()) {
            return ResponseHelper::send(404, __('api.error_content_not_found'));
        }

        foreach ($images as $image) {
            un_link_file($image->image_path);
            $image->delete();
        }

        return ResponseHelper::send(200, __('api.success'), []);
    }

    public function joinEvent(Request $request)
    {
        $this->directValidation([
            'event_id' => ['required', 'exists:events,id'],
        ]);

        $userId = $request->user()->id;
        $eventId = $request->event_id;

        $alreadyJoined = EventUserJoin::where('event_id', $eventId)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyJoined) {
            return ResponseHelper::send(409, 'You have already joined this event.');
        }

        EventUserJoin::insert([
            'event_id' => $eventId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ResponseHelper::send(200, __('api.success'));
    }

    public function eventCategory(Request $request)
    {
        $language = $request->header('Accept-Language') ?? 'en';
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $query = EventCategory::where('status', 'active')
            ->where('language', $language);


        $categories = $query->select('id', 'event_category')->latest()->limit($limit)->offset($offset)->get();

        return ResponseHelper::send(200, __('api.success'), $categories);
    }

}

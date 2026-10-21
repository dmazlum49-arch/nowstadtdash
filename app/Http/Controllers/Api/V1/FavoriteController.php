<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends ResponseController
{
    public function favorite(Request $request)
    {
        $this->directValidation([
            'type' => 'required|in:business,event',
            'id' => 'required|integer',
            'reminder_option' => 'nullable|in:same_day,1_day_before,2_days_before,5_days_before,1_week_before,custom',
            'reminder_date' => 'required_if:reminder_option,custom|nullable|date',
            'reminder_time' => 'required_if:reminder_option,custom|nullable|date_format:H:i'
        ]);

        $userId = $request->user()->id;
        $type = $request->type;

        $favoriteQuery = Favorite::where('user_id', $userId)->where('type', $type);

        if ($type === 'business') {
            $this->directValidation([
                'id' => 'exists:businesses,id',
            ]);
            $favoriteQuery->where('business_id', $request->id);
        } else {
            $this->directValidation([
                'id' => 'exists:events,id',
            ]);
            $favoriteQuery->where('event_id', $request->id);
        }

        $favorite = $favoriteQuery->first();

        if ($favorite) {
            $favorite->delete();
            return ResponseHelper::send(200, __('api.success_remove_favorite'));
        }

        $data = [
            'user_id' => $userId,
            'type' => $type,
            'business_id' => $type === 'business' ? $request->id : null,
            'event_id' => $type === 'event' ? $request->id : null,
            'reminder_option' => $type === 'event' ? ($request->reminder_option ?? 'none') : null,
            'reminder_date' => ($type === 'event' && $request->reminder_option === 'custom') ? $request->reminder_date : null,
            'reminder_time' => ($type === 'event' && $request->reminder_option === 'custom') ? $request->reminder_time : null,
        ];

        Favorite::create($data);

        return ResponseHelper::send(200, __('api.success_add_favorite'));
    }

    // public function myFavoriteList(Request $request)
    // {
    //     $this->directValidation([
    //         'type' => 'required|in:business,event',
    //     ]);

    //     $userId = $request->user()->id;
    //     $type = $request->type;
    //     $limit = $request->limit ?? 10;
    //     $offset = $request->offset ?? 0;

    //     $favorites = Favorite::with($type)
    //         ->where('user_id', $userId)
    //         ->where('type', $type)
    //         ->latest()
    //         ->limit($limit)
    //         ->offset($offset)
    //         ->get();

    //     $data = $favorites->map(function ($fav) use ($type) {
    //         if ($type === 'business' && $fav->business) {
    //             return [
    //                 'id' => $fav->business->id,
    //                 'business_name' => $fav->business->business_name,
    //                 'business_category_name' => $fav->business->category->business_category ?? null,
    //                 'city' => $fav->business->city,
    //                 'banner_image' => $fav->business->banner_image ? url($fav->business->banner_image) : null,
    //                 'is_favorite' => true
    //             ];
    //         }

    //         if ($type === 'event' && $fav->event) {
    //             return [
    //                 'id' => $fav->event->id,
    //                 'category' => $fav->event->category->event_category ?? null,
    //                 'location' => $fav->event->location,
    //                 'start_date' => $fav->event->start_date,
    //                 'start_time' => $fav->event->start_time,
    //                 'event_image' => $fav->event->banner_image ? url($fav->event->banner_image) : null,
    //                 'is_favorite' => true
    //             ];
    //         }

    //         return null;
    //     })->filter()->values();

    //     return ResponseHelper::send(200, __('api.success'), $data);
    // }

    public function myFavoriteList(Request $request)
{
    $this->directValidation([
        'type' => 'required|in:business,event',
    ]);

    $userId = $request->user()->id;
    $type = $request->type;
    $limit = $request->limit ?? 10;
    $offset = $request->offset ?? 0;

    // Load favorites with their related models
    $favorites = Favorite::with([
        $type => function ($query) {
            $query->with(['category']);
            if ($query->getModel() instanceof \App\Models\Event) {
                $query->with(['joinedUsers' => function ($q) {
                    $q->select('users.id', 'users.profile_image');
                }]);
            }
            if ($query->getModel() instanceof \App\Models\Business) {
                $query->withCount(['ratings as reviews_count' => function ($q) {
                    $q->where('status', 'active')->where('moderation_status', 'approved');
                }]);
                $query->withAvg(['ratings as average_rating' => function ($q) {
                    $q->where('status', 'active')->where('moderation_status', 'approved');
                }], 'rating');
            }
        }
    ])
        ->where('user_id', $userId)
        ->where('type', $type)
        ->latest()
        ->limit($limit)
        ->offset($offset)
        ->get();

    $data = $favorites->map(function ($fav) use ($type, $userId) {
        if ($type === 'business' && $fav->business) {
            return array_merge([
                'id' => $fav->business->id,
                'business_name' => $fav->business->business_name,
                'logo' => $fav->business->logo ? url($fav->business->logo) : null,
                'business_category_name' => $fav->business->category->business_category ?? null,
                'city' => $fav->business->city,
                'banner_image' => $fav->business->banner_image ? url($fav->business->banner_image) : null,
                'is_favorite' => true,
                'average_rating' => round($fav->business->average_rating ?? 0, 1),
                'reviews_count' => (int) $fav->business->reviews_count,
            ], $fav->business->getBoostData($userId));
        }

        if ($type === 'event' && $fav->event) {
            return [
                'id' => $fav->event->id,
                'category' => $fav->event->category->event_category ?? null,
                'location' => $fav->event->location,
                'start_date' => $fav->event->start_date,
                'start_time' => $fav->event->start_time,
                'event_image' => $fav->event->banner_image ? url($fav->event->banner_image) : null,
                'joined_users' => $fav->event->joinedUsers->take(3)->map(function ($user) {
                    return [
                        'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                    ];
                }),
                'joined_count' => $fav->event->joinedUsers->count(),
                'is_favorite' => true
            ];
        }

        return null;
    })->filter()->values();

    return ResponseHelper::send(200, __('api.success'), $data);
}

}

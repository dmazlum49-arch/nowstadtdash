<?php
namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Models\BlockUser;
use App\Models\Business;
use App\Models\GeneralSetting;
use App\Models\BusinessCategory;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\HomeBanner;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends ResponseController
{
    // public function home(Request $request)
    // {
    //     $user = $request->user();
    //     $latitude = $request->header('latitude') ?? ($user ? $user->latitude : null);
    //     $longitude = $request->header('longitude') ?? ($user ? $user->longitude : null);
    //     // $radius    = 10;
    //     $radius = GeneralSetting::where('unique_name', 'SITE_RADIUS')->first()->value ?? '';

    //     $language = $request->header('Accept-Language') ?? 'en';

    //     // Filters
    //     $search = $request->search;
    //     $city = $request->city;
    //     $categoryIds = $request->filled('category_id')
    //         ? explode(',', $request->category_id)
    //         : [];

    //     $myBusinessCount = $user ? Business::where('user_id', $user->id)->count() : 0;
    //     $myPostCount = $user ? Post::where('user_id', $user->id)->count() : 0;
    //     $myEventCount = $user ? Event::where('user_id', $user->id)->count() : 0;
    //     $myNowisPoints = $user ? $user->nowis_points : 0;

    //     $categories = BusinessCategory::select('id', 'business_category', 'image')
    //         ->where('status', 'active')
    //         ->where('language', $language)
    //         ->take(10)
    //         ->get()
    //         ->map(function ($cat) {
    //             return [
    //                 'id' => $cat->id,
    //                 'image' => $cat->image ? url($cat->image) : null,
    //                 'name' => $cat->business_category,
    //             ];
    //         });
    //     $favoriteBusinessIds = [];
    //     $blockedUserIds = [];
    //     if ($user) {
    //         $favoriteBusinessIds = Favorite::where('user_id', $user->id)
    //             ->where('type', 'business')
    //             ->pluck('business_id')
    //             ->toArray();

    //         $blockedUserIds = BlockUser::where('user_id', $user->id)->pluck('block_user_id')->toArray();
    //     }

    //     $rawNearbyBusinesses = Business::select('*', DB::raw("
    //             (6371 * acos(
    //                 cos(radians($latitude)) *
    //                 cos(radians(latitude)) *
    //                 cos(radians(longitude) - radians($longitude)) +
    //                 sin(radians($latitude)) *
    //                 sin(radians(latitude))
    //             )) AS distance
    //         "))
    //         ->having('distance', '<=', $radius)
    //         ->when(!empty($blockedUserIds), fn($q) => $q->whereNotIn('user_id', $blockedUserIds))
    //         ->where('status', 'active')
    //         ->where('type', 'approved')
    //         ->when(
    //             !empty($categoryIds),
    //             fn($query) =>
    //             $query->whereIn('business_category_id', $categoryIds)
    //         )
    //         ->when($search, function ($query, $search) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('business_name', 'like', "%$search%")
    //                     ->orWhere('city', 'like', "%$search%");
    //             });
    //         })
    //         ->when(
    //             $city,
    //             fn($query) =>
    //             $query->where('city', 'like', "%$city%")
    //         )
    //         ->orderBy('distance', 'asc')
    //         ->with('category');

    //     $totalNearbyCount = null;
    //     if (!empty($search)) {
    //         $totalNearbyCount = $rawNearbyBusinesses->count();
    //     }

    //     $nearbyBusinesses = $rawNearbyBusinesses
    //         ->limit(10)
    //         ->get()
    //         ->map(function ($business) use ($favoriteBusinessIds) {
    //             return [
    //                 'id' => $business->id,
    //                 'business_name' => $business->business_name,
    //                 'business_category_name' => $business->category->business_category ?? null,
    //                 'city' => $business->city,
    //                 'banner_image' => $business->banner_image ? url($business->banner_image) : null,
    //                 'logo' => $business->logo ? url($business->logo) : null,
    //                 'is_favorite' => in_array($business->id, $favoriteBusinessIds),
    //             ];
    //         });

    //     $banner = HomeBanner::where('status', 'active')
    //         ->where('language', $language)
    //         ->orderBy('created_at', 'asc')
    //         ->get()
    //         ->map(function ($banner) {
    //             return [
    //                 'id' => $banner->id,
    //                 'image' => $banner->image ? url($banner->image) : null,
    //             ];
    //         });

    //     $response = [
    //         'user' => $user ? [
    //             'name' => $user->name,
    //             'location' => $user->location ?? '',
    //             'profile_image' => $user->profile_image ? url($user->profile_image) : null,
    //         ] : null,
    //         'banners' => $banner,
    //         'counts' => [
    //             'my_business' => $myBusinessCount,
    //             'my_post' => $myPostCount,
    //             'my_event' => $myEventCount,
    //             'my_nowis' => $myNowisPoints,
    //         ],
    //         'business_categories' => $categories,
    //         'nearby_businesses' => $nearbyBusinesses,
    //     ];

    //     if (!empty($search)) {
    //         $response['nearby_businesses_count'] = $totalNearbyCount;
    //     }

    //     return ResponseHelper::send(200, __('api.success'), $response);
    // }

    public function home(Request $request)
{
    $user = $request->user();
    $latitude = $request->header('latitude') ?? ($user ? $user->latitude : null);
    $longitude = $request->header('longitude') ?? ($user ? $user->longitude : null);
    $radius = GeneralSetting::where('unique_name', 'SITE_RADIUS')->first()->value ?? 10;
    $language = $request->header('Accept-Language') ?? 'en';

    // Filters
    $search = $request->search;
    $city = $request->city;
    $categoryIds = $request->filled('category_id')
        ? explode(',', $request->category_id)
        : [];

    // User data & counts
    $myBusinessCount = $user ? Business::where('user_id', $user->id)->count() : 0;
    $myPostCount = $user ? Post::where('user_id', $user->id)->count() : 0;
    $myEventCount = $user ? Event::where('user_id', $user->id)->count() : 0;
    $myNowisPoints = $user ? $user->nowis_points : 0;

    // Categories
    $categories = BusinessCategory::select('id', 'business_category', 'image')
        ->where('status', 'active')
        ->where('language', $language)
        ->whereHas('businesses', function ($q) {
            $q->where('status', 'active')
              ->where('type', 'approved');
        })
        ->take(10)
        ->get()
        ->map(function ($cat) {
            return [
                'id' => $cat->id,
                'image' => $cat->image ? url($cat->image) : null,
                'name' => $cat->business_category,
            ];
        });

    // Favorites and blocked users
    $favoriteBusinessIds = [];
    $blockedUserIds = [];
    $userId = $user ? $user->id : null;
    if ($user) {
        $favoriteBusinessIds = Favorite::where('user_id', $user->id)
            ->where('type', 'business')
            ->pluck('business_id')
            ->toArray();

        $blockedUserIds = BlockUser::where('user_id', $user->id)
            ->pluck('block_user_id')
            ->toArray();
    }

    // 🔹 1. Nearby Businesses
    $rawNearbyBusinesses = Business::select('*', DB::raw("
        (6371 * acos(
            cos(radians($latitude)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians($longitude)) +
            sin(radians($latitude)) *
            sin(radians(latitude))
        )) AS distance
    "))
        ->having('distance', '<=', $radius)
        ->when(!empty($blockedUserIds), fn($q) => $q->whereNotIn('user_id', $blockedUserIds))
        ->where('status', 'active')
        ->where('type', 'approved')
        ->when(
            !empty($categoryIds),
            fn($query) => $query->whereIn('business_category_id', $categoryIds)
        )
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%");
            });
        })
        ->when($city, fn($query) => $query->where('city', 'like', "%$city%"))
        ->orderBy('distance', 'asc')
        ->with('category')
        ->withCount(['ratings as reviews_count' => function ($query) {
            $query->where('status', 'active')->where('moderation_status', 'approved');
        }])
        ->withAvg(['ratings as average_rating' => function ($query) {
            $query->where('status', 'active')->where('moderation_status', 'approved');
        }], 'rating');

    $totalNearbyCount = null;
    if (!empty($search)) {
        $totalNearbyCount = $rawNearbyBusinesses->count();
    }

    $nearbyBusinesses = $rawNearbyBusinesses
        ->limit(10)
        ->get()
        ->map(function ($business) use ($favoriteBusinessIds, $userId) {
            return array_merge([
                'id' => $business->id,
                'business_name' => $business->business_name,
                'business_category_name' => $business->category->business_category ?? null,
                'city' => $business->city,
                'banner_image' => $business->banner_image ? url($business->banner_image) : null,
                'logo' => $business->logo ? url($business->logo) : null,
                'is_favorite' => in_array($business->id, $favoriteBusinessIds),
                'average_rating' => round($business->average_rating ?? 0, 1),
                'reviews_count' => (int) $business->reviews_count,
            ], $business->getBoostData($userId));
        });

        $now = Carbon::now('Europe/Vienna');
        $today = strtolower($now->format('l')); // tuesday
        $currentTime = $now->format('H:i');

    // 🔹 2. Open Now Businesses (recently added within last 24 hours)
    // $openNowBusinesses = Business::select('*', DB::raw("
    //     (6371 * acos(
    //         cos(radians($latitude)) *
    //         cos(radians(latitude)) *
    //         cos(radians(longitude) - radians($longitude)) +
    //         sin(radians($latitude)) *
    //         sin(radians(latitude))
    //     )) AS distance
    // "))
    //     ->having('distance', '<=', $radius)
    //     ->when(!empty($blockedUserIds), fn($q) => $q->whereNotIn('user_id', $blockedUserIds))
    //     ->where('status', 'active')
    //     ->where('type', 'approved')
    //     ->where('created_at', '>=', now()->subDay()) // last 24 hours
    //     ->orderBy('distance', 'asc')
    //     ->limit(10)
    //     ->with('category')
    //     ->get()
    //     ->map(function ($business) use ($favoriteBusinessIds) {
    //         return [
    //             'id' => $business->id,
    //             'business_name' => $business->business_name,
    //             'business_category_name' => $business->category->business_category ?? null,
    //             'city' => $business->city,
    //             'banner_image' => $business->banner_image ? url($business->banner_image) : null,
    //             'logo' => $business->logo ? url($business->logo) : null,
    //             'is_favorite' => in_array($business->id, $favoriteBusinessIds),
    //         ];
    //     });

    $openNowBusinesses = Business::where('status', 'active')
    ->where('type', 'approved')
    ->whereHas('timings', function ($q) use ($today, $currentTime) {
        $q->where('day', $today)
          ->where('is_open', 1)
          ->whereTime('start_time', '<=', $currentTime)
          ->whereTime('end_time', '>=', $currentTime);
    })
    ->when(!empty($blockedUserIds), fn($q) => $q->whereNotIn('user_id', $blockedUserIds))
    ->with('category')
    ->withCount(['ratings as reviews_count' => function ($query) {
        $query->where('status', 'active')->where('moderation_status', 'approved');
    }])
    ->withAvg(['ratings as average_rating' => function ($query) {
        $query->where('status', 'active')->where('moderation_status', 'approved');
    }], 'rating')
    ->limit(10)
    ->get()
    ->map(function ($business) use ($favoriteBusinessIds, $userId) {
        return array_merge([
            'id' => $business->id,
            'business_name' => $business->business_name,
            'business_category_name' => $business->category->business_category ?? null,
            'city' => $business->city,
            'banner_image' => $business->banner_image ? url($business->banner_image) : null,
            'logo' => $business->logo ? url($business->logo) : null,
            'is_favorite' => in_array($business->id, $favoriteBusinessIds),
            'average_rating' => round($business->average_rating ?? 0, 1),
            'reviews_count' => (int) $business->reviews_count,
        ], $business->getBoostData($userId));
    });

    // 🔹 3. Top Businesses (most viewed/popular)
    $topBusinesses = Business::select('*', DB::raw("
        (6371 * acos(
            cos(radians($latitude)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians($longitude)) +
            sin(radians($latitude)) *
            sin(radians(latitude))
        )) AS distance
    "))
        ->having('distance', '<=', $radius)
        ->when(!empty($blockedUserIds), fn($q) => $q->whereNotIn('user_id', $blockedUserIds))
        ->where('status', 'active')
        ->where('type', 'approved')
        ->orderByDesc('views') // change this to 'rating' or 'likes' if needed
        ->limit(10)
        ->with('category')
        ->withCount(['ratings as reviews_count' => function ($query) {
            $query->where('status', 'active')->where('moderation_status', 'approved');
        }])
        ->withAvg(['ratings as average_rating' => function ($query) {
            $query->where('status', 'active')->where('moderation_status', 'approved');
        }], 'rating')
        ->get()
        ->map(function ($business) use ($favoriteBusinessIds, $userId) {
            return array_merge([
                'id' => $business->id,
                'business_name' => $business->business_name,
                'business_category_name' => $business->category->business_category ?? null,
                'city' => $business->city,
                'banner_image' => $business->banner_image ? url($business->banner_image) : null,
                'logo' => $business->logo ? url($business->logo) : null,
                'is_favorite' => in_array($business->id, $favoriteBusinessIds),
                'average_rating' => round($business->average_rating ?? 0, 1),
                'reviews_count' => (int) $business->reviews_count,
            ], $business->getBoostData($userId));
        });

    // 🔹 Banners
    $banner = HomeBanner::where('status', 'active')
        ->where('language', $language)
        ->inRandomOrder()
        ->get()
        ->map(function ($banner) {
            return [
                'id' => $banner->id,
                'image' => $banner->image ? url($banner->image) : null,
            ];
        });

    // 🔹 Final Response
    $response = [
        'user' => $user ? [
            'name' => $user->name,
            'location' => $user->location ?? '',
            'profile_image' => $user->profile_image ? url($user->profile_image) : null,
        ] : null,
        'banners' => $banner,
        'counts' => [
            'my_business' => $myBusinessCount,
            'my_post' => $myPostCount,
            'my_event' => $myEventCount,
            'my_nowis' => $myNowisPoints,
        ],
        'business_categories' => $categories,
        'nearby_businesses' => $nearbyBusinesses,
        'open_now_businesses' => $openNowBusinesses,
        'top_businesses' => $topBusinesses,
    ];

    if (!empty($search)) {
        $response['nearby_businesses_count'] = $totalNearbyCount;
    }

    return ResponseHelper::send(200, __('api.success'), $response);
}


    public function nearByBusiness(Request $request)
    {
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $user = $request->user();
        $latitude = $request->header('latitude') ?? ($user ? $user->latitude : null);
        $longitude = $request->header('longitude') ?? ($user ? $user->longitude : null);
        // $radius    = 10;
        $radius = GeneralSetting::where('unique_name', 'SITE_RADIUS')->first()->value ?? '';

        $favoriteBusinessIds = [];
        $blockedUserIds = [];
        $userId = null;

        if ($user) {
            $userId = $user->id;

            $favoriteBusinessIds = Favorite::where('user_id', $userId)
                ->where('type', 'business')
                ->pluck('business_id')
                ->toArray();

            $blockedUserIds = BlockUser::where('user_id', $userId)->pluck('block_user_id')->toArray();
        }

        $nearbyBusinesses = Business::select('*', DB::raw("
            (6371 * acos(
                cos(radians($latitude)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians($longitude)) +
                sin(radians($latitude)) *
                sin(radians(latitude))
            )) AS distance
        "))
            ->having('distance', '<=', $radius)
            // ->where('user_id', '!=', $userId)
            ->when(!empty($blockedUserIds), function ($query) use ($blockedUserIds) {
                $query->whereNotIn('user_id', $blockedUserIds);
            })
            ->where('status', 'active')
            ->where('type', 'approved')
            ->orderBy('distance', 'asc')
            ->with('category')
            ->withCount(['ratings as reviews_count' => function ($query) {
                $query->where('status', 'active')->where('moderation_status', 'approved');
            }])
            ->withAvg(['ratings as average_rating' => function ($query) {
                $query->where('status', 'active')->where('moderation_status', 'approved');
            }], 'rating')
            ->limit($limit)->offset($offset)
            ->get()
            ->map(function ($business) use ($favoriteBusinessIds, $userId) {
                return array_merge([
                    'id' => $business->id,
                    'business_name' => $business->business_name,
                    'business_category_name' => $business->category->business_category ?? null,
                    'city' => $business->city,
                    'banner_image' => $business->banner_image ? url($business->banner_image) : null,
                    'logo' => $business->logo ? url($business->logo) : null,
                    'is_favorite' => in_array($business->id, $favoriteBusinessIds),
                    'average_rating' => round($business->average_rating ?? 0, 1),
                    'reviews_count' => (int) $business->reviews_count,
                ], $business->getBoostData($userId));
            });

        return ResponseHelper::send(200, __('api.success'), $nearbyBusinesses);
    }

    public function bannerDetails(Request $request)
    {
        $language = $request->header('Accept-Language') ?? 'en';

        $bannerId = $request->banner_id;

        $banner = HomeBanner::where('status', 'active')
            ->where('language', $language)
            ->where('id', $bannerId)
            ->select('id', 'image', 'link', 'title', 'description')
            ->first();

        return ResponseHelper::send(200, __('api.success'), $banner);
    }

//     public function mapBusinesses(Request $request)
//     {
//         $user = $request->user();
//         $latitude = $request->header('latitude');
//         $longitude = $request->header('longitude');

//         $request->validate([
//             'distance' => 'nullable|numeric',
//             'category_id' => 'nullable|string',
//             'search' => 'nullable|string',
//             'is_open' => 'nullable|boolean', // ✅ new filter
//             'reward' => 'nullable|string|in:all,stamp_card,nowis'
//         ]);

//         $distance = $request->distance ?? GeneralSetting::where('unique_name', 'SITE_RADIUS')->value('value') ?? 10;

//         $categoryIds = $request->filled('category_id')
//             ? explode(',', $request->category_id)
//             : [];

//         $haversine = "(6371 * acos(cos(radians($latitude)) 
//         * cos(radians(latitude)) 
//         * cos(radians(longitude) - radians($longitude)) 
//         + sin(radians($latitude)) 
//         * sin(radians(latitude))))";

//         $now = now();
//         $currentDay = strtolower($now->format('l'));
//         $currentTime = $now->format('H:i:s');

//         $query = Business::with(['category:id,business_category,image', 'timings'])
//             ->where('status', 'active')
//             ->where('type', 'approved')
//             ->when($request->search, function ($q) use ($request) {
//                 $q->where('business_name', 'like', '%' . $request->search . '%');
//             })
//             ->whereHas('user', function($q) {
//     $q->where(function($sub){
//         $sub->whereExists(function($inner){
//             $inner->select(DB::raw(1))
//                 ->from('transactions')
//                 ->whereColumn('transactions.user_id', 'users.id')
//                 ->where('transactions.status', 'active');
//         });
//     });
// })
//             ->when(!empty($categoryIds), function ($q) use ($categoryIds) {
//                 $q->whereIn('business_category_id', $categoryIds);
//             })
//             ->when($request->filled('is_open') && $request->is_open == true, function ($q) use ($currentDay, $currentTime) {
//                 $q->whereHas('timings', function ($timing) use ($currentDay, $currentTime) {
//                     $timing->where('day', $currentDay)
//                         ->where('is_open', 1)
//                         ->whereTime('start_time', '<=', $currentTime)
//                         ->whereTime('end_time', '>=', $currentTime);
//                 });
//             })
//             ->when($request->filled('reward'), function ($q) use ($request) {

//                 if ($request->reward === 'stamp_card') {
//                     $q->whereHas('stampCards');
//                 }

//                 if ($request->reward === 'nowis') {
//                     $q->whereHas('nowisPoints');
//                 }

//                 if ($request->reward === 'all') {
//                     $q->where(function($sub) {
//                         $sub->whereHas('stampCards')
//                             ->orWhereHas('nowisPoints');
//                     });
//                 }

//             });

//         $businesses = $query
//             ->select([
//                 'id',
//                 'business_category_id',
//                 'latitude',
//                 'longitude'
//             ])
//             ->selectRaw("$haversine AS distance")
//             ->having('distance', '<=', $distance)
//             ->orderBy('distance')
//             ->get()
//             ->map(function ($business) use ($currentDay, $currentTime) {
//                 $todayTiming = $business->timings
//                     ->where('day', $currentDay)
//                     ->first();

//                 $isOpen = false;
//                 if ($todayTiming && $todayTiming->is_open) {
//                     if ($todayTiming->start_time && $todayTiming->end_time) {
//                         $isOpen = $currentTime >= $todayTiming->start_time && $currentTime <= $todayTiming->end_time;
//                     }
//                 }

//                 return [
//                     'id' => $business->id,
//                     'business_category_id' => $business->business_category_id,
//                     'business_category' => optional($business->category)->business_category,
//                     'image' => optional($business->category)->image
//                         ? url(optional($business->category)->image)
//                         : null,
//                     'latitude' => $business->latitude,
//                     'longitude' => $business->longitude,
//                     'is_open' => $isOpen, // ✅ new field
//                 ];
//             });

//         return ResponseHelper::send(200, __('api.success'), $businesses);
//     }
    

    public function mapBusinesses(Request $request)
    {
        $user = $request->user();
        $latitude = $request->header('latitude');
        $longitude = $request->header('longitude');

        $request->validate([
            'distance'   => 'nullable|numeric',
            'category_id'=> 'nullable|string',
            'search'     => 'nullable|string',
            'is_open'    => 'nullable|boolean',
            'reward'     => 'nullable|string|in:all,stamp_card,nowis'
        ]);

        $distance = $request->distance
            ?? GeneralSetting::where('unique_name', 'SITE_RADIUS')->value('value')
            ?? 10;

        $categoryIds = $request->filled('category_id')
            ? explode(',', $request->category_id)
            : [];

        /* ===============================
        TIME HANDLING (IMPORTANT)
        =============================== */
        $now = Carbon::now('Europe/Vienna');
        $currentDay = strtolower($now->format('l'));
        $currentTime = $now->format('H:i:s');

        $haversine = "(6371 * acos(
            cos(radians($latitude)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians($longitude)) +
            sin(radians($latitude)) *
            sin(radians(latitude))
        ))";

        $query = Business::with(['category:id,business_category,image', 'timings'])
            ->where('status', 'active')
            ->where('type', 'approved')

            /* 🔍 Search */
            ->when($request->search, function ($q) use ($request) {
                $q->where('business_name', 'like', '%' . $request->search . '%');
            })

            /* 🚫 Blocked users */
            ->whereHas('user', function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('transactions')
                        ->whereColumn('transactions.user_id', 'users.id')
                        ->where('transactions.status', 'active');
                });
            })

            /* 📂 Category filter */
            ->when(!empty($categoryIds), function ($q) use ($categoryIds) {
                $q->whereIn('business_category_id', $categoryIds);
            })

            /* 🟢 OPEN NOW FILTER (CORRECT LOGIC) */
            ->when(
                $request->filled('is_open') && $request->is_open == true,
                function ($q) use ($currentDay, $currentTime) {
                    $q->whereHas('timings', function ($t) use ($currentDay, $currentTime) {
                        $t->where('day', $currentDay)
                        ->where('is_open', 1)
                        ->whereTime('start_time', '<=', $currentTime)
                        ->whereTime('end_time', '>=', $currentTime);
                    });
                }
            )

            /* 🎁 Rewards filter */
            ->when($request->reward, function ($q) use ($request) {
                if ($request->reward === 'stamp_card') {
                    $q->whereHas('stampCards');
                }

                if ($request->reward === 'nowis') {
                    $q->whereHas('nowisPoints');
                }

                // if ($request->reward === 'all') {
                //     $q->where(function ($sub) {
                //         $sub->whereHas('stampCards')
                //             ->orWhereHas('nowisPoints');
                //     });
                // }
            });

        $user = $request->user();
        $userId = $user ? $user->id : null;

        $businesses = $query
            ->select([
                'id',
                'business_name',
                'business_category_id',
                'latitude',
                'longitude'
            ])
            ->selectRaw("$haversine AS distance")
            ->having('distance', '<=', $distance)
            ->orderBy('distance')
            ->get()
            ->map(function ($business) use ($currentDay, $currentTime, $userId) {

                $todayTiming = $business->timings
                    ->where('day', $currentDay)
                    ->first();

                $isOpen = false;

                if ($todayTiming && $todayTiming->is_open) {
                    $isOpen = (
                        $currentTime >= $todayTiming->start_time &&
                        $currentTime <= $todayTiming->end_time
                    );
                }

                return array_merge([
                    'id' => $business->id,
                    'business_name' => $business->business_name,
                    'business_category_id' => $business->business_category_id,
                    'business_category' => optional($business->category)->business_category,
                    'image' => optional($business->category)->image
                        ? url(optional($business->category)->image)
                        : null,
                    'latitude' => $business->latitude,
                    'longitude' => $business->longitude,
                    'is_open' => $isOpen,
                ], $business->getBoostData($userId));
            });

        return ResponseHelper::send(200, __('api.success'), $businesses);
    }

    public function openNowBusiness(Request $request)
{
    $limit = $request->limit ?? 10;
    $offset = $request->offset ?? 0;

    $user = $request->user();

    $favoriteBusinessIds = [];
    $blockedUserIds = [];
    $userId = $user ? $user->id : null;

    if ($user) {
        $favoriteBusinessIds = Favorite::where('user_id', $user->id)
            ->where('type', 'business')
            ->pluck('business_id')
            ->toArray();

        $blockedUserIds = BlockUser::where('user_id', $user->id)
            ->pluck('block_user_id')
            ->toArray();
    }

    $now = Carbon::now('Europe/Vienna');
    $today = strtolower($now->format('l')); // tuesday
    $currentTime = $now->format('H:i');
    $businesses = Business::query()
        ->where('status', 'active')
        ->where('type', 'approved')
        ->when(!empty($blockedUserIds), function ($query) use ($blockedUserIds) {
            $query->whereNotIn('user_id', $blockedUserIds);
        })
        ->whereHas('timings', function ($q) use ($today, $currentTime) {
        $q->where('day', $today)
          ->where('is_open', 1)
          ->whereTime('start_time', '<=', $currentTime)
        ->whereTime('end_time', '>=', $currentTime);
    })
        ->with(['category'])
        ->withCount(['ratings as reviews_count' => function ($query) {
            $query->where('status', 'active')->where('moderation_status', 'approved');
        }])
        ->withAvg(['ratings as average_rating' => function ($query) {
            $query->where('status', 'active')->where('moderation_status', 'approved');
        }], 'rating')
        ->latest('created_at')
        ->limit($limit)
        ->offset($offset)
        ->get()
        ->map(function ($business) use ($favoriteBusinessIds, $userId) {
            return array_merge([
                'id' => $business->id,
                'business_name' => $business->business_name,
                'business_category_name' => $business->category->business_category ?? null,
                'city' => $business->city,
                'banner_image' => $business->banner_image ? url($business->banner_image) : null,
                'logo' => $business->logo ? url($business->logo) : null,
                'is_favorite' => in_array($business->id, $favoriteBusinessIds),
                'average_rating' => round($business->average_rating ?? 0, 1),
                'reviews_count' => (int) $business->reviews_count,
            ], $business->getBoostData($userId));
        });

    return ResponseHelper::send(200, __('api.success'), $businesses);
}

public function topBusiness(Request $request)
{
    $limit = $request->limit ?? 10;
    $offset = $request->offset ?? 0;

    $user = $request->user();

    $favoriteBusinessIds = [];
    $blockedUserIds = [];
    $userId = $user ? $user->id : null;

    if ($user) {
        $favoriteBusinessIds = Favorite::where('user_id', $user->id)
            ->where('type', 'business')
            ->pluck('business_id')
            ->toArray();

        $blockedUserIds = BlockUser::where('user_id', $user->id)
            ->pluck('block_user_id')
            ->toArray();
    }

    $businesses = Business::query()
        ->where('status', 'active')
        ->where('type', 'approved')
        ->when(!empty($blockedUserIds), function ($query) use ($blockedUserIds) {
            $query->whereNotIn('user_id', $blockedUserIds);
        })
        // If 'views' column exists, use it; otherwise fallback to 'created_at'
        ->orderByDesc(DB::raw("COALESCE(views, 0)"))
        ->orderByDesc('created_at')
        ->with(['category'])
        ->withCount(['ratings as reviews_count' => function ($query) {
            $query->where('status', 'active')->where('moderation_status', 'approved');
        }])
        ->withAvg(['ratings as average_rating' => function ($query) {
            $query->where('status', 'active')->where('moderation_status', 'approved');
        }], 'rating')
        ->limit($limit)
        ->offset($offset)
        ->get()
        ->map(function ($business) use ($favoriteBusinessIds, $userId) {
            return array_merge([
                'id' => $business->id,
                'business_name' => $business->business_name,
                'business_category_name' => $business->category->business_category ?? null,
                'city' => $business->city,
                'banner_image' => $business->banner_image ? url($business->banner_image) : null,
                'logo' => $business->logo ? url($business->logo) : null,
                'is_favorite' => in_array($business->id, $favoriteBusinessIds),
                'average_rating' => round($business->average_rating ?? 0, 1),
                'reviews_count' => (int) $business->reviews_count,
            ], $business->getBoostData($userId));
        });

    return ResponseHelper::send(200, __('api.success'), $businesses);
}


}

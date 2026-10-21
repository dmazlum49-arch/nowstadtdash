<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\Controller;
use App\Models\BlockUser;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessImages;
use App\Models\BusinessStampCard;
use App\Models\BusinessTiming;
use App\Models\Favorite;
use App\Models\QrScanLog;
use App\Models\Service;
use App\Models\ChangeBusinessQrCode;
use App\Models\Boost;
use App\Models\BoostClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

use function PHPSTORM_META\type;

class BusinessController extends ResponseController
{
    // public function createBusiness(Request $request)
    // {
    //     $userId = $request->user()->id;
    //     $this->directValidation([
    //         'business_name' => 'required|string|max:255',
    //         'business_category_id' => 'required|exists:business_categories,id',
    //         'city' => 'nullable|string|max:255',
    //         'website' => 'nullable|url|max:255',
    //         'about_business' => 'nullable|string',
    //         'banner_image' => 'nullable|file|mimes:jpg,jpeg,png,webp',
    //         'business_images.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp'],
    //         // 'business_timing' => 'required|array',
    //         'business_timing' => 'required|string',

    //     ]);

    //     $business = Business::create([
    //         'user_id' => $userId,
    //         'business_name' => $request->business_name,
    //         'about_business' => $request->about_business,
    //         'business_category_id' => $request->business_category_id,
    //         'city' => $request->city,
    //         'website' => $request->website,
    //     ]);

    //     if ($request->hasFile('banner_image')) {
    //         $bannerImagePath = upload_file('banner_image', 'business_images');

    //         if ($business->banner_image) {
    //             un_link_file($business->banner_image);
    //         }

    //         $business->update(['banner_image' => $bannerImagePath]);
    //     }

    //     if ($request->hasFile('business_images')) {

    //         foreach ($request->file('business_images') as $index => $image) {
    //             $imagePath = upload_file("business_images.{$index}", 'business_images');

    //             BusinessImages::create([
    //                 'business_id' => $business->id,
    //                 'image_path' => $imagePath,
    //             ]);
    //         }
    //     }

    //     $timings = json_decode($request->business_timing, true);

    //     if (!is_array($timings)) {
    //         return ResponseHelper::send(422, 'Invalid business timing format');
    //     }

    //     foreach ($timings as $timing) {
    //         \Validator::make($timing, [
    //             'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //             'is_open' => 'required|boolean',
    //             'start_time' => 'nullable|date_format:H:i',
    //             'end_time' => 'nullable|date_format:H:i',
    //         ])->validate();

    //         BusinessTiming::create([
    //             'business_id' => $business->id,
    //             'day' => $timing['day'],
    //             'is_open' => $timing['is_open'],
    //             'start_time' => $timing['start_time'],
    //             'end_time' => $timing['end_time'],
    //         ]);
    //     }

    //     $business->save();

    //     return ResponseHelper::send(201, __('api.success_business_created'), [
    //         'id' => $business->id,
    //         'business_name' => $business->business_name,
    //         'about_business' => $business->about_business,
    //         'business_category_id' => $business->business_category_id,
    //         'city' => $business->city,
    //         'website' => $business->website,
    //         'banner_image' => $business->banner_image,
    //         'business_images' => $business->businessImages->map(function ($img) {
    //             return [
    //                 'id' => $img->id,
    //                 'image_path' => $img->image_path,
    //             ];
    //         }),
    //         'business_timing' => $business->timings->map(function ($hour) {
    //             return [
    //                 'day' => $hour->day,
    //                 'is_open' => $hour->is_open,
    //                 'start_time' => $hour->start_time,
    //                 'end_time' => $hour->end_time,
    //             ];
    //         }),
    //         'created_at' => $business->created_at,
    //         'updated_at' => $business->updated_at,

    //     ]);
    // }


    public function createOrUpdateBusiness(Request $request)
    {
        $userId = $request->user()->id;
        $limits = getUserPlanLimits($userId);

        if ($limits['has_subscription'] === false || $limits['plan_name'] === 'Free') {
            return ResponseHelper::send(403, 'Your subscription does not allow creating or updating a business. Please upgrade your plan.');
        }

        $this->directValidation([
            'business_id' => 'nullable|exists:businesses,id',
            'business_name' => 'required|string|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            'city' => 'nullable|string|max:255',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'website' => 'nullable|url|max:255',
            'country_code' => 'nullable|max:10',
            'mobile' => $this->validationService->mobileRules($request),
            'about_business' => 'nullable|string',
            'company_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp',
            'banner_image' => 'nullable|file|mimes:jpg,jpeg,png,webp',
            'logo' => 'nullable|image',
            'business_images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp',
            'business_timing' => 'nullable|string',
        ]);

        $isUpdate = $request->filled('business_id');
        $message = $isUpdate ? __('api.success_business_updated') : __('api.success_business_created');

        if ($isUpdate) {
            $business = Business::where('user_id', $userId)->findOrFail($request->business_id);
            $business->update($request->only('business_name', 'about_business', 'business_category_id', 'city', 'latitude', 'longitude', 'website'));
        } else {
            $business = Business::create($request->only('business_name', 'about_business', 'business_category_id', 'city', 'latitude', 'longitude', 'website','country_code','mobile') + ['user_id' => $userId]);
        }

        // Upload banner image
        if ($request->hasFile('banner_image')) {
            $bannerImagePath = upload_file('banner_image', 'business_images');

            if ($business->banner_image && $isUpdate) {
                un_link_file($business->banner_image);
            }

            $business->update(['banner_image' => $bannerImagePath]);
        }
        if ($request->hasFile('logo')) {
            $logoImagePath = upload_file('logo', 'business_images');

            if ($business->logo && $isUpdate) {
                un_link_file($business->logo);
            }

            $business->update(['logo' => $logoImagePath]);
        }
        if ($request->hasFile('company_registration')) {
            $companyRegPath = upload_file('company_registration', 'company_registration');

            if ($business->company_registration && $isUpdate) {
                un_link_file($business->company_registration);
            }

            $business->update(['company_registration' => $companyRegPath]);
        }

        // Upload multiple business images
        if ($request->hasFile('business_images')) {
            if ($isUpdate) {
                foreach ($business->businessImages as $img) {
                    un_link_file($img->image_path);
                    $img->delete();
                }
            }

            foreach ($request->file('business_images') as $index => $image) {
                $imagePath = upload_file("business_images.$index", 'business_images');

                BusinessImages::create([
                    'business_id' => $business->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        if ($request->filled('business_timing')) {
            $timings = json_decode($request->business_timing, true);

            if (!is_array($timings)) {
                return ResponseHelper::send(422, 'Invalid business_timing format (must be valid JSON)');
            }

            foreach ($timings as $timing) {
                \Validator::make($timing, [
                    'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                    'is_open' => 'required|boolean',
                    'start_time' => 'nullable|date_format:H:i',
                    'end_time' => 'nullable|date_format:H:i',
                ])->validate();

                BusinessTiming::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'day' => $timing['day'],
                    ],
                    [
                        'is_open' => $timing['is_open'],
                        'start_time' => $timing['start_time'],
                        'end_time' => $timing['end_time'],
                    ]
                );
            }
        }

        $business->load(['businessImages', 'timings']);

        

        $supportMail = GeneralSetting::where('unique_name', 'SUPPORT_EMAIL')->first()->value ?? '';
        // Send email notification to admin for new business creation
            // dd($supportMail);
            Mail::to($supportMail)->send(new \App\Mail\BusinessCreateMail($request->user()->name, $business));

        return ResponseHelper::send(200, $message, [
            'id' => $business->id,
            'business_name' => $business->business_name,
            'about_business' => $business->about_business,
            'business_category_id' => $business->business_category_id,
            'city' => $business->city,
            'website' => $business->website,
            'country_code' => $business->country_code,
            'mobile' => $business->mobile ?? null,
            'company_registration' => $business->company_registration ? url($business->company_registration) : null,
            'logo' => $business->logo ? url($business->logo) : null,
            'banner_image' => $business->banner_image ? url($business->banner_image) : null,
            'business_images' => $business->businessImages->map(fn($img) => [
                'id' => $img->id,
                'image_path' => $img->image_path ? url($img->image_path) : null,
            ]),
            'business_timing' => $business->timings->map(fn($hour) => [
                'day' => $hour->day,
                'is_open' => $hour->is_open,
                'start_time' => $hour->start_time,
                'end_time' => $hour->end_time,
            ]),
            'created_at' => $business->created_at,
            'updated_at' => $business->updated_at,
            'daily_code' => $business->getDailyCode(),
        ]);
    }

    public function updateBusiness(Request $request)
    {
        $userId = $request->user()->id;

        $this->directValidation([
            'business_id' => 'required|exists:businesses,id',
            'type' => 'required|in:info,gallery,timing',
        ]);

        $business = Business::where('user_id', $userId)->findOrFail($request->business_id);
        $type = $request->type;

        if ($type === 'info') {
            $this->directValidation([
                'business_name' => 'required|string|max:255',
                'business_category_id' => 'required|exists:business_categories,id',
                'city' => 'nullable|string|max:255',
                'latitude' => 'nullable',
                'longitude' => 'nullable',
                'website' => 'nullable|url|max:255',
                'country_code' => 'nullable|max:10',
                'mobile' => $this->validationService->mobileRules($request),
                'about_business' => 'nullable|string',
                'logo' => 'nullable|image',
                'company_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp',
            ]);

            $logoPath = upload_file('logo', 'business_images');

            if ($logoPath && $business->logo) {
                un_link_file($business->logo);
            }

            $companyRegPath = upload_file('company_registration', 'company_registration');

            if ($companyRegPath && $business->company_registration) {
                un_link_file($business->company_registration);
            }

            $business->update($request->only([
                'business_name',
                'about_business',
                'business_category_id',
                'city',
                'latitude',
                'longitude',
                'website',
                'country_code',
                'mobile',
            ]) + ['logo' => $logoPath ?: $business->logo, 'company_registration' => $companyRegPath ?: $business->company_registration]);

        } else if ($type === 'gallery') {
            $this->directValidation([
                'banner_image' => 'required|file|mimes:jpg,jpeg,png,webp',
                'business_images.*' => 'required|file|mimes:jpg,jpeg,png,webp',
            ]);

            $bannerPath = upload_file('banner_image', 'business_images');

            if ($bannerPath && $business->banner_image) {
                un_link_file($business->banner_image);
            }

            $business->update(['banner_image' => $bannerPath]);

            // Clear old images
            if ($business->businessImages) {
                foreach ($business->businessImages as $img) {
                    un_link_file($img->image_path);
                    $img->delete();
                }
            }

            if ($request->hasFile('business_images')) {
                foreach ($request->file('business_images') as $index => $image) {
                    $path = upload_file("business_images.$index", 'business_images');

                    BusinessImages::create([
                        'business_id' => $business->id,
                        'image_path' => $path,
                    ]);
                }
            }

        } else if ($type === 'timing') {
            $this->directValidation([
                'business_timing' => 'required|string',
            ]);

            $timings = json_decode($request->business_timing, true);

            if (!is_array($timings)) {
                return ResponseHelper::send(422, 'Invalid business_timing format');
            }

            foreach ($timings as $timing) {
                \Validator::make($timing, [
                    'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                    'is_open' => 'required|boolean',
                    'start_time' => 'nullable|date_format:H:i',
                    'end_time' => 'nullable|date_format:H:i',
                ])->validate();

                BusinessTiming::updateOrCreate(
                    ['business_id' => $business->id, 'day' => $timing['day']],
                    ['is_open' => $timing['is_open'], 'start_time' => $timing['start_time'], 'end_time' => $timing['end_time']]
                );
            }
        }

        $business->load(['businessImages', 'timings']);

        return ResponseHelper::send(200, __('api.success_business_updated'), [
            'id' => $business->id,
            'business_name' => $business->business_name,
            'about_business' => $business->about_business,
            'business_category_id' => $business->business_category_id,
            'city' => $business->city,
            'website' => $business->website,
            'country_code' => $business->country_code,
            'mobile' => $business->mobile ?? null,
            'company_registration' => $business->company_registration ? url($business->company_registration) : null,
            'logo' => $business->logo ? url($business->logo) : null,
            'banner_image' => $business->banner_image ? url($business->banner_image) : null,
            'business_images' => $business->businessImages->map(fn($img) => [
                'id' => $img->id,
                'image_path' => $img->image_path ? url($img->image_path) : null,
            ]),
            'business_timing' => $business->timings->map(fn($hour) => [
                'day' => $hour->day,
                'is_open' => $hour->is_open,
                'start_time' => $hour->start_time,
                'end_time' => $hour->end_time,
            ]),
            'daily_code' => $business->getDailyCode(),
            'created_at' => $business->created_at,
            'updated_at' => $business->updated_at,
        ]);
    }


    public function myBusinessList(Request $request)
    {
        $userId = $request->user()->id;

        $this->directValidation([
            'type' => ['nullable', 'in:pending,approved'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = Business::where('user_id', $userId);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $businesses = $query->latest()
            ->limit($limit)
            ->offset($offset)
            ->with(['businessImages', 'timings'])
            ->get();

        $formatted = $businesses->map(function ($business) {
            return [
                'id' => $business->id,
                'business_name' => $business->business_name,
                'daily_code' => $business->getDailyCode(),
            ];
        });

        return ResponseHelper::send(200, __('api.success_businesses_retrieved'), $formatted);
    }

    public function businessServices(Request $request)
    {
        $this->directValidation([
            'business_id' => 'required'
        ]);

        $services = Service::where('Business_id', $request->business_id)->get();

        return ResponseHelper::send(200, __('api.success_services_retrieved'), $services);
    }

    public function businessCategory(Request $request)
    {
        $language = $request->header('Accept-Language') ?? 'en';
        $search = $request->search ?? '';
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $query = BusinessCategory::where('status', 'active')
            ->where('language', $language);

        if (!empty($search)) {
            $query->where('business_category', 'like', "%{$search}%");
        }

        $categories = $query->select('id', 'business_category', 'image')->latest()->limit($limit)->offset($offset)->get();

        return ResponseHelper::send(200, __('api.success'), $categories);
    }

    public function getBusinessByCategory(Request $request)
    {
        $user = $request->user();
        $this->directValidation([
            'business_category_id' => 'nullable|exists:business_categories,id',
        ]);

        // $userId = $request->user()->id;
        $user = $request->user(); // null ho sakta hai
        $userId = $user ? $user->id : null;
        $business_category_id = $request->business_category_id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        // $blockedUserIds = BlockUser::where('user_id', $user->id)->pluck('block_user_id')->toArray();
         $blockedUserIds = $user 
        ? BlockUser::where('user_id', $userId)->pluck('block_user_id')->toArray() 
        : [];

        $businesses = Business::with(['category', 'activeBoost'])
            ->where('type', 'approved')
            ->whereNotIn('user_id', $blockedUserIds)
            ->when($business_category_id, function ($query) use ($business_category_id) {
                $query->where('business_category_id', $business_category_id);
            })
            ->withCount(['ratings as reviews_count' => function ($query) {
                $query->where('status', 'active')->where('moderation_status', 'approved');
            }])
            ->withAvg(['ratings as average_rating' => function ($query) {
                $query->where('status', 'active')->where('moderation_status', 'approved');
            }], 'rating')
            ->latest()
            ->limit($limit)->offset($offset)
            ->get();

        $businessIds = $businesses->pluck('id')->toArray();

        $favoriteBusinessIds = $user 
        ? Favorite::where('user_id', $userId)
            ->where('type', 'business')
            ->whereIn('business_id', $businessIds)
            ->pluck('business_id')
            ->toArray()
        : [];

        $formatted = $businesses->map(function ($business) use ($favoriteBusinessIds, $userId) {
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

        return ResponseHelper::send(200, __('api.success'), $formatted);
    }

    public function businessDetails(Request $request)
    {
        $this->directValidation([
            'business_id' => ['required', 'exists:businesses,id'],
            'type' => ['nullable', 'in:service,about,rate'],
        ]);

         $user = $request->user();
         $userId = $user ? $user->id : null;

        $business = Business::with(['user', 'category', 'timings', 'businessImages'])
            ->withCount(['ratings as reviews_count' => function ($query) {
                $query->where('status', 'active')->where('moderation_status', 'approved');
            }])
            ->withAvg(['ratings as average_rating' => function ($query) {
                $query->where('status', 'active')->where('moderation_status', 'approved');
            }], 'rating')
            ->findOrFail($request->business_id);
        $type = $request->type;

        // $favoriteBusinessIds = Favorite::where('user_id', $request->user()->id)
        //     ->where('type', 'business')
        //     ->pluck('business_id')
        //     ->toArray();

        $business->increment('views');

        $favoriteBusinessIds = $user
        ? Favorite::where('user_id', $userId)
            ->where('type', 'business')
            ->pluck('business_id')
            ->toArray()
        : [];

        $response = array_merge([
            'id' => $business->id,
            'business_name' => $business->business_name,
            'business_category_id' => $business->business_category_id,
            'business_category_name' => $business->category->business_category ?? null,
            'banner_image' => $business->banner_image ? url($business->banner_image) : null,
            'gallery' => $business->businessImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_path' => $image->image_path ? url($image->image_path) : ''
                ];
            }),
            'website' => $business->website,
            'city' => $business->city,
            'latitude' => $business->latitude,
            'longitude' => $business->longitude,
            'company_registration' => $business->company_registration ? url($business->company_registration) : null,
            'logo' => $business->logo,
            'user_id' => $business->user_id,
            'country_code' => $business->country_code ?? null,
            'mobile' => $business->mobile ?? null,
            'average_rating' => round($business->average_rating ?? 0, 1),
            'reviews_count' => (int) $business->reviews_count,
            'is_favorite' => in_array($business->id, $favoriteBusinessIds),
            'business_timing' => $business->timings->map(function ($hour) {
                return [
                    'day' => $hour->day,
                    'is_open' => $hour->is_open,
                    'start_time' => $hour->start_time,
                    'end_time' => $hour->end_time,
                ];
            }),
        ], $business->getBoostData($userId));

        if ($user && $business->user_id === $userId) {
            $response['daily_code'] = $business->getDailyCode();
        }

        if ($type === 'service') {
            $response['services'] = $business->services()
                ->latest()
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'title' => $service->title,
                        'description' => $service->description,
                        'service_image' => $service->service_image ? url($service->service_image) : null,
                        'price' => $service->price
                    ];
                });
        } elseif ($type === 'about') {
            $response['about_business'] = $business->about_business ?? '';

        } elseif ($type === 'rate') {
            $response['ratings'] = $business->ratings()
                ->where('status', 'active')
                ->where('moderation_status', 'approved')
                ->with('user:id,name,profile_image')
                ->latest()
                ->get()
                ->map(function ($rating) use ($userId) {
                    return [
                        'id' => $rating->id,
                        'user_id' => $rating->user_id,
                        'business_id' => $rating->business_id,
                        'rating' => $rating->rating,
                        'review' => $rating->review,
                        'is_mine' => $userId ? ($rating->user_id == $userId) : false,
                        'created_at' => $rating->created_at,
                        'user' => [
                            'id' => $rating->user->id ?? null,
                            'name' => $rating->user->name ?? '',
                            'profile_image' => $rating->user->profile_image ?? '',
                        ]
                    ];
                });
        }

        return ResponseHelper::send(200, __('api.success'), $response);
    }

    // public function scanQr(Request $request, $businessId)
    // {
    //     $user = $request->user();

    //     if (!$user) {
    //         return response()->json(['message' => 'Unauthorized.'], 401);
    //     }

    //     $business = Business::where('id', $businessId)
    //     ->where('is_active_qr', true)
    //     ->first();
    //     if (!$business) {
    //         return ResponseHelper::send(404, 'Invalid QR code: Business not found.');
    //     }

    //     $user->nowis_points += 3;
    //     $user->save();

    //     $businessStampCard = BusinessStampCard::firstOrNew([
    //         'user_id' => $user->id,
    //         'business_id' => $businessId,
    //     ]);

    //     if ($businessStampCard->exists) {
    //         $businessStampCard->stamp_cards += 1;
    //     } else {
    //         $businessStampCard->stamp_cards = 1;
    //     }

    //     $businessStampCard->save();

    //     return ResponseHelper::send(200, __('api.QR scanned successfully.'));

    // }

    
    public function scanQr(Request $request, $businessId)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $business = Business::where('id', $businessId)
            ->where('is_active_qr', true)
            ->first();

        if (!$business) {
            return ResponseHelper::send(404, 'Invalid QR code: Business not found.');
        }

        $today = now()->startOfDay();
        $scanCount = QrScanLog::where('user_id', $user->id)
            ->where('business_id', $businessId)
            ->where('created_at', '>=', $today)
            ->count();

        if ($scanCount >= 2) {
            return ResponseHelper::send(403, 'You can only scan this business QR 2 times per day.');
        }

        // If code is not provided, return business info and prompt for code
        if (!$request->filled('code')) {
            return ResponseHelper::send(200, 'Business found. Please enter verification code.', [
                'business_id' => $business->id,
                'business_name' => $business->business_name,
                'requires_code' => true
            ]);
        }

        $this->directValidation([
            'code' => 'required|string|size:4',
        ]);

        // Ensure business has today's code generated
        $currentDailyCode = $business->getDailyCode();

        if ($request->code !== $currentDailyCode) {
            return ResponseHelper::send(400, 'Invalid verification code.');
        }

        $businessStampCard = BusinessStampCard::firstOrNew([
            'user_id' => $user->id,
            'business_id' => $businessId,
        ]);

        if ($businessStampCard->exists) {
            $businessStampCard->stamp_cards += 1;
        } else {
            $businessStampCard->stamp_cards = 1;
        }

        $businessStampCard->save();

        $qrScanLog = QrScanLog::create([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'type' => 'qr',
        ]);

        $awardResult = $this->processScanPointsAward($user, $businessId, $qrScanLog);

        $responsePayload = array_merge($qrScanLog->toArray(), $awardResult);

        $message = $awardResult['is_boost']
            ? __("QR scanned successfully! {$awardResult['boost_multiplier']}x Nowis applied (+{$awardResult['points_awarded']} points).")
            : __('api.QR scanned successfully.');

        return ResponseHelper::send(200, $message, $responsePayload);
    }

    private function processScanPointsAward($user, $businessId, $qrScanLog = null)
    {
        $basePoints = 10;
        $pointsToAward = $basePoints;
        $isBoostApplied = false;
        $appliedMultiplier = 1;

        $activeBoost = Boost::where('business_id', $businessId)->active()->first();

        if ($activeBoost) {
            // Each user can use a specific business boost only once per boost campaign.
            // After the user has already received the boosted Nowis from that business,
            // the same boost must not be applied again for that user, even if they scan the QR code multiple times.
            $alreadyClaimed = BoostClaim::where('user_id', $user->id)
                ->where('boost_id', $activeBoost->id)
                ->exists();

            if (!$alreadyClaimed) {
                $appliedMultiplier = (int) ($activeBoost->multiplier ?? 2);
                $pointsToAward = $basePoints * $appliedMultiplier;
                $isBoostApplied = true;

                BoostClaim::create([
                    'boost_id' => $activeBoost->id,
                    'business_id' => $businessId,
                    'user_id' => $user->id,
                    'multiplier' => $appliedMultiplier,
                    'points_awarded' => $pointsToAward,
                    'qr_scan_log_id' => $qrScanLog ? $qrScanLog->id : null,
                ]);
            }
        }

        $user->nowis_points += $pointsToAward;
        $user->save();

        return [
            'points_awarded' => $pointsToAward,
            'is_boost' => $isBoostApplied,
            'boost_multiplier' => $appliedMultiplier,
            'my_nowis_points' => $user->nowis_points,
        ];
    }

    public function changeQrCode(Request $request)
    {
        $user = $request->user();

        $this->directValidation([
            'business_id' => 'nullable|exists:businesses,id',
            'description' => 'required|string',
        ]);

        $qrChange = ChangeBusinessQrCode::create([
            'user_id'     => $user->id,
            'business_id' => $request->business_id,
            'description' => $request->description,
            'type'        => 'pending', // default value
        ]);

        return ResponseHelper::send(200, 'QR code change request submitted successfully.', $qrChange);
    }

    public function generateVerificationCode(Request $request)
    {
        $this->directValidation([
            'business_id' => 'required|exists:businesses,id',
        ]);

        $user = $request->user();
        $businessId = $request->business_id;

        // Check if already verified today (last 24 hours)
        $today = now()->startOfDay();
        $scanCount = QrScanLog::where('user_id', $user->id)
            ->where('business_id', $businessId)
            ->where('type', 'code')
            ->where('created_at', '>=', $today)
            ->count();

        if ($scanCount >= 1) {
            return ResponseHelper::send(403, __('api.you_have_already_verified_this_business_today_using_a_code'));
        }

        // Generate 6-digit code
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in cache for 10 minutes
        // We store it both ways: user->code (to check limits) and code->user (for business owner to verify)
        Cache::put("business_code_{$user->id}_{$businessId}", $code, now()->addMinutes(10));
        Cache::put("verify_code_{$code}_{$businessId}", $user->id, now()->addMinutes(10));

        return ResponseHelper::send(200, __('api.code_generated_successfully'), ['code' => $code]);
    }

    public function verifyVerificationCode(Request $request)
    {
        $this->directValidation([
            'business_id' => 'required|exists:businesses,id',
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $businessId = $request->business_id;
        $code = $request->code;

        $cacheKey = "business_code_{$user->id}_{$businessId}";
        $storedCode = Cache::get($cacheKey);

        if (!$storedCode || $storedCode !== $code) {
            return ResponseHelper::send(400, 'Invalid or expired verification code.');
        }

        // Update stamp card
        $businessStampCard = BusinessStampCard::firstOrNew([
            'user_id' => $user->id,
            'business_id' => $businessId,
        ]);
        $businessStampCard->stamp_cards += 1;
        $businessStampCard->save();

        // Log the verification
        $log = QrScanLog::create([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'type' => 'code',
            'code' => $code,
        ]);

        // Clear cache
        Cache::forget($cacheKey);

        $awardResult = $this->processScanPointsAward($user, $businessId, $log);
        $responsePayload = array_merge($log->toArray(), $awardResult);

        $message = $awardResult['is_boost']
            ? "Verification successful! {$awardResult['boost_multiplier']}x Nowis applied (+{$awardResult['points_awarded']} points)."
            : 'Verification successful. points awarded.';

        return ResponseHelper::send(200, $message, $responsePayload);
    }

    public function getAllApprovedBusinesses(Request $request)
    {
        $this->directValidation([
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $businesses = Business::where('type', 'approved')
            ->select('id', 'business_name as name')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return ResponseHelper::send(200, __('api.success'), $businesses);
    }

    public function regenerateDailyCode(Request $request)
    {
        $this->directValidation([
            'business_id' => 'required|exists:businesses,id',
        ]);

        $user = $request->user();
        $business = Business::where('user_id', $user->id)->findOrFail($request->business_id);

        $newCode = $business->getDailyCode(true);

        return ResponseHelper::send(200, 'Daily code regenerated successfully.', [
            'daily_code' => $newCode
        ]);
    }
}

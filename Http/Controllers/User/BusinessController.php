<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessImages;
use App\Models\BusinessTiming;
use App\Models\GeneralSetting;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\QrScanLog;
use App\Models\BusinessStampCard;
use Illuminate\Support\Facades\Cache;

class BusinessController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $business = Business::with('category')->where('user_id', Auth::user()->primaryId())->get();
            return DataTables::of($business)
                ->addIndexColumn()
                ->addColumn('logo', function ($row) {
                    return get_fancy_box_html($row->logo);
                })
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })
                ->addColumn('business_category_id', function ($row) {
                    return $row->category->business_category ?? '-';
                })
                ->addColumn('business_name', function ($row) {
                    return $row->business_name;
                })
                ->addColumn('city', function ($row) {
                    return $row->city ?? '-';
                })
                ->addColumn('daily_code', function ($row) {
                    return $row->getDailyCode();
                })
                ->addColumn('action', function ($row) {
                    $verifyUrl = route('user.business.verify-code');
                    $regenerateUrl = route('user.business.regenerate-code');

                    $verifyBtn = '<button type="button" class="btn btn-sm btn-outline-info waves-effect waves-light btnVerifyCode" data-id="'.$row->id.'" data-url="'.$verifyUrl.'" title="Verify Code"><i class="mdi mdi-check-decagram"></i></button>';
                    $regenerateBtn = '<button type="button" class="btn btn-sm btn-outline-warning waves-effect waves-light btnRegenerateCode" data-id="'.$row->id.'" data-url="'.$regenerateUrl.'" title="Regenerate Code"><i class="mdi mdi-refresh"></i></button>';

                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('user.business.show', $row->id),
                            'edit' => route('user.business.edit', $row->id),
                        ]
                    ];
                    // Combine and return
                    return $regenerateBtn . ' ' . generate_actions_buttons($params);
                })
                ->rawColumns(['logo', 'action'])
                ->make(true);
        }

        return view('user.business.index', [
            'title' => "Business",
            'breadcrumbs' => [
                'Business' => route('user.business.index'),
            ]
        ]);
    }

    public function show(Business $business)
    {
        $business = $business->load(['timings', 'businessImages', 'services']);
        
        return view('user.business.show', [
            'title' => "Business",
            'data' => $business,
            'breadcrumbs' => [
                'Business' => route('user.business.index'),
                'View' => route('user.business.show', $business),
            ],
        ]);
    }

    public function create()
    {
        $categories = BusinessCategory::where('status', 'active')->get();
        return view('user.business.create', [
            'title' => "Business",
            'breadcrumbs' => [
                'Business' => route('user.business.index'),
                'Create' => route('user.business.create'),
            ],
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::user()->primaryId();

        $request->validate([
            'business_name' => 'required|string|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            'city' => 'nullable|string|max:255',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'website' => 'nullable|url|max:255',
            'about_business' => 'nullable|string',
            'logo' => 'nullable|image',
        ]);

        $isUpdate = $request->filled('business_id');
        $message = $isUpdate ? __('api.success_business_updated') : __('api.success_business_created');

        $business = Business::create($request->only('business_name', 'about_business', 'business_category_id', 'city', 'latitude', 'longitude', 'website','country_code','mobile') + ['user_id' => $userId]);

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

            // if (!is_array($timings)) {
            //     return ResponseHelper::send(422, 'Invalid business_timing format (must be valid JSON)');
            // }

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


        flash_session('success', 'Business created successfully.');
        return redirect()->route('user.business.index');
    }

    public function edit(Business $business)
    {
        $categories = BusinessCategory::where('status', 'active')->get();
        return view('user.business.edit', [
            'title' => "Edit Business",
            'business' => $business,
            'categories' => $categories,
            'breadcrumbs' => [
                'Business' => route('user.business.index'),
                'Edit' => route('user.business.edit', $business->id),
            ],
        ]);
    }

    public function update(Request $request, Business $business)
    {
        // dd($request->all());
        $userId = Auth::user()->primaryId();

        // Check user owns business
        $business = Business::where('user_id', $userId)
            ->findOrFail($business->id);

            // dd($business);
        // Validation
        $request->validate([
            'business_name' => 'required|string|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            'city' => 'nullable|string|max:255',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'website' => 'nullable|url|max:255',
            'about_business' => 'nullable|string',
            'logo' => 'nullable|image',
            'business_timing' => 'required|string',
        ]);

        // Handle file uploads
        // $logoPath = upload_file('logo', 'business_images');
        // dd($logoPath);
        // if ($logoPath && $business->logo) {
        //     un_link_file($business->logo);
        // }
        $logoPath = $business->getRawOriginal('logo');

        if (!empty($request->image_base64)) {
            $logoPath = upload_base_64_img($request->image_base64, get_constants('upload_paths.business_images'));

            if ($business->logo) {
                un_link_file($business->logo);
            }
            if (!$logoPath) {
                flash_session('error', 'Image upload failed.');
                return redirect()->back()->withInput();
            }
        }

        // $companyRegPath = upload_file('company_registration', 'company_registration');
        // if ($companyRegPath && $business->company_registration) {
        //     un_link_file($business->company_registration);
        // }

        // $bannerPath = upload_file('banner_image', 'business_images');
        // if ($bannerPath && $business->banner_image) {
        //     un_link_file($business->banner_image);
        // }

        // Update business main details
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
        ]) + [
            'logo' => $logoPath ?: $business->logo,
            // 'company_registration' => $companyRegPath ?: $business->company_registration,
            // 'banner_image' => $bannerPath ?: $business->banner_image,
        ]);

        // Replace old images if new provided
        if ($request->hasFile('business_images')) {
            foreach ($business->businessImages as $img) {
                un_link_file($img->image_path);
                $img->delete();
            }

            foreach ($request->file('business_images') as $index => $image) {
                $path = upload_file("business_images.$index", 'business_images');

                BusinessImages::create([
                    'business_id' => $business->id,
                    'image_path' => $path,
                ]);
            }
        }

        // Business timings
        $timings = json_decode($request->business_timing, true);

        foreach ($timings as $timing) {
            \Validator::make($timing, [
                'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'is_open' => 'required|boolean',
                'start_time' => 'nullable',
                'end_time' => 'nullable',
            ])->validate();

            BusinessTiming::updateOrCreate(
                ['business_id' => $business->id, 'day' => $timing['day']],
                [
                    'is_open' => $timing['is_open'],
                    'start_time' => $timing['start_time'] ?: "00:00",
                    'end_time' => $timing['end_time'] ?: "00:00",
                ]
            );
        }

        $business->load(['businessImages', 'timings']);

        flash_session('success', 'Business updated successfully.');
        return redirect()->route('user.business.index');
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'code' => 'required|string|size:6',
        ]);

        $businessId = $request->business_id;
        $code = $request->code;

        // Check if the business belongs to the logged-in user
        $business = Business::where('id', $businessId)->where('user_id', Auth::user()->primaryId())->first();
        if (!$business) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized or invalid business.'], 403);
        }

        // Find the user who generated this code for this business
        $cacheKey = "verify_code_{$code}_{$businessId}";
        $userId = Cache::get($cacheKey);

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired verification code.'], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        // Award points
        $user->nowis_points += 3;
        $user->save();

        // Update stamp card
        $businessStampCard = BusinessStampCard::firstOrNew([
            'user_id' => $user->id,
            'business_id' => $businessId,
        ]);
        $businessStampCard->stamp_cards += 1;
        $businessStampCard->save();

        // Log the verification
        QrScanLog::create([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'type' => 'code',
            'code' => $code,
        ]);

        // Send push notification to the user
        $payload = [
            'from_user_id' => Auth::user()->primaryId(),
            'push_type'    => 11,
            'push_title'   => 'Points Awarded!',
            'push_message' => 'Your code was verified at ' . $business->business_name . '. You earned 3 points!',
            'extra' => [
                'business_id' => $business->id,
                'points' => 3,
                'profile_image' => $user->profile_image ? url($user->profile_image) : null,
            ],
        ];
        send_push($user->id, $payload, false);

        // Clear cache
        Cache::forget($cacheKey);
        Cache::forget("business_code_{$user->id}_{$businessId}");

        return response()->json(['status' => 'success', 'message' => 'Code verified successfully. User awarded with points.']);
    }

    public function regenerateCode(Request $request)
    {
        $businessId = $request->business_id;
        $userId = Auth::user()->primaryId();

        $business = Business::where('user_id', $userId)->findOrFail($businessId);
        $newCode = $business->getDailyCode(true);

        return response()->json([
            'status' => 'success',
            'message' => 'Daily code regenerated successfully.',
            'daily_code' => $newCode,
        ]);
    }
}
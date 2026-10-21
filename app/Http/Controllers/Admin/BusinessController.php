<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EventRejectedMail;
use App\Mail\NowisPointQrMail;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessImages;
use App\Models\BusinessTiming;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Yajra\DataTables\Facades\DataTables;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BusinessController extends Controller
{
    public function pending()
    {
        if (request()->ajax()) {
            $business = Business::with('category')->where('type', 'pending')->get();
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
                ->addColumn('action', function ($row) {
                    $acceptUrl = route('admin.business.accept', $row->id);
                    $rejectUrl = route('admin.business.reject', $row->id);
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('admin.business.show', $row->id),
                            'edit' => route('admin.business.edit', $row->id),
                        ]
                    ];
                    $defaultButtons = generate_actions_buttons($params);

                    // Custom Accept/Reject buttons
                    $customButtons = '
                        <a href="' . $acceptUrl . '" class="font-size-14 me-2 btn btn-sm btn-outline-success waves-effect waves-light btnAction" data-action="approved">
                            <i class="fas fa-check"></i>
                        </a>
                        <a href="' . $rejectUrl . '" class="font-size-14 me-2 btn btn-sm btn-outline-danger waves-effect waves-light btnAction" data-action="rejected">
                            <i class="fas fa-times"></i>
                        </a>
                    ';

                    // Combine and return
                    return $defaultButtons . $customButtons;
                })
                ->rawColumns(['logo', 'action'])
                ->make(true);
        }

        return view('admin.business.pending_index', [
            'title' => "Pending Business",
            'breadcrumbs' => [
                'Pending Business' => route('admin.pending.business'),
            ]
        ]);
    }

    public function accept($id)
    {

        $business = Business::with('user')->findOrFail($id);

        $business->update(['type' => 'approved']);

        $qrContent = route('business.qr.scan', ['id' => $business->id]);
        $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode($qrContent) . '&size=200';

        $qrImageData = Http::get($qrCodeUrl)->body();

        // Save it temporarily
        $tempPath = public_path('uploads/qrcodes/');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $fileName = 'business_qr_' . $business->id . '.png';
        $filePath = $tempPath . $fileName;
        file_put_contents($filePath, $qrImageData);

        $business->update([
        'qr_code'     => 'uploads/qrcodes/' . $fileName,
        'is_active_qr' => true,
        ]);

        Mail::to($business->user->email)->send(new NowisPointQrMail($business, $filePath));

        $users = User::where('type','user')->get();
        foreach ($users as $user) {
            $locale = $user->language ?? 'en';
            App::setLocale($locale);
            send_push($user->id, [
                'from_user_id' => auth()->id(),
                'push_type'    => 3,
                'push_title'   => __('admin.business_created_title'),
                'push_message' => $business->business_name ?? __('admin.business_created_message'),
                'extra' => [
                    'business_id' => $business->id,
                ],
            ], true);
        }

        return redirect()->back()->with('success', 'Business accepted and email sent successfully.');
    }

    public function reject(Request $request, $id)
    {
        $business = Business::with('user')->findOrFail($id);

        $business->type = 'rejected';
        $business->save();

        $user = $business->user;
        // Send email
        Mail::to($user->email)->send(new EventRejectedMail($user, $business->business_name, $request->input('reason')));

        return redirect()->back()->with('success', 'Business rejected and notified via email.');
    }

    public function approved()
    {
        if (request()->ajax()) {
            $business = Business::with('category')->where('type', 'approved')->get();
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
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.business.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('admin.business.show', $row->id),
                            'edit' => route('admin.business.edit', $row->id),
                            'delete' => route('admin.business.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['logo', 'status', 'action'])
                ->make(true);
        }

        return view('admin.business.approved_index', [
            'title' => "Approved Business",
            'breadcrumbs' => [
                'Approved Business' => route('admin.approved.business'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $business = Business::find($id);
        if ($business) {
            $business->update(['status' => ($business->status == "inactive") ? "active" : "inactive"]);
            $business->save();
            return response()->json(['success' => true, 'message' => 'Business status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Business not found.']);
    }

    public function show(Business $id)
    {
        $business = $id->load(['timings', 'businessImages','services']);
        
        return view('admin.business.show', [
            'title' => "Business",
            'data' => $business,
            'breadcrumbs' => [
                'Business' => route('admin.pending.business'),
                'View' => route('admin.business.show', $id->id),
            ],
        ]);
    }

    public function destroy($id)
    {
        try {
            $business = Business::find($id);
            $business->delete();
            return response()->json([
                'success' => true,
                'message' => __('Business has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Business: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function edit(Business $id)
    {
        $categories = BusinessCategory::where('status', 'active')->get();
        return view('admin.business.edit', [
            'title' => "Edit Business",
            'business' => $id,
            'categories' => $categories,
            'breadcrumbs' => [
                'Business' => route('admin.approved.business'),
                'Edit' => route('admin.business.edit', $id->id),
            ],
        ]);
    }

    public function update(Request $request, Business $id)
    {
        $business = Business::where('id', $id->id)->first();

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

        $bannerPath = $business->getRawOriginal('banner_image');

        if (!empty($request->banner_base64)) {
            $bannerPath = upload_base_64_img($request->banner_base64, get_constants('upload_paths.business_images'));

            if ($business->banner_image) {
                un_link_file($business->banner_image);
            }
            if (!$bannerPath) {
                flash_session('error', 'Banner upload failed.');
                return redirect()->back()->withInput();
            }
        } elseif ($request->hasFile('banner_image')) {
             $bannerPath = upload_file('banner_image', 'business_images');
             if ($business->banner_image) {
                un_link_file($business->banner_image);
            }
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
        ]) + [
            'logo' => $logoPath ?: $business->logo,
            // 'company_registration' => $companyRegPath ?: $business->company_registration,
            'banner_image' => $bannerPath ?: $business->banner_image,
        ]);

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
        return redirect()->route('admin.approved.business');
    }
}

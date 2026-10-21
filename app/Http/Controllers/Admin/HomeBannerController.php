<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HomeBannerController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $banner = HomeBanner::where('language', 'en')->get();
            return DataTables::of($banner)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    return get_fancy_box_html($row->image);
                })
                ->addColumn('title', function ($row) {
                    return $row->title;
                })
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.home-banner.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.home-banner.edit', $row->id),
                            'view' => route('admin.home-banner.show', $row->id),
                            'delete' => route('admin.home-banner.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('admin.homeBanner.index', [
            'title' => "Banner",
            'breadcrumbs' => [
                'Banner' => route('admin.home-banner.index'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $banner = HomeBanner::find($id);
        if ($banner) {
            $banners = HomeBanner::where('unique_id', $banner->unique_id)->get();
            foreach ($banners as $cat) {
                $cat->update(['status' => ($cat->status == "inactive") ? "active" : "inactive"]);
                $cat->save();
            }
            return response()->json(['success' => true, 'message' => 'Banner status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Banner not found.']);
    }

    public function show(HomeBanner $home_banner)
    {
        return view('admin.homeBanner.show', [
            'title' => "Banner",
            'data' => $home_banner,
            'breadcrumbs' => [
                'Banner' => route('admin.home-banner.index'),
                'View' => route('admin.home-banner.show', $home_banner->id),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.homeBanner.create', [
            'title' => "Home Banner",
            'breadcrumbs' => [
                'Home Banner' => route('admin.home-banner.index'),
                'Create' => route('admin.home-banner.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            // 'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|string|max:255',
            'title' => 'required|array',
            'title.*' => 'nullable|string',
            'description' => 'required|array',
            'description.*' => 'nullable|string',
        ]);

        $imagePath = null;
        if (!empty($request->image_base64)) {
            $imagePath = upload_base_64_img($request->image_base64, get_constants('upload_paths.home_banner_images'));

            if (!$imagePath) {
                flash_session('error', 'Image upload failed.');
                return redirect()->back()->withInput();
            }
        }

        do {
            $uniqueId = mt_rand(100000, 999999);
        } while (HomeBanner::where('unique_id', $uniqueId)->exists());

        $titles = $request->input('title');
        $descriptions = $request->input('description');

        if (empty($titles['de'])) {
            $titles['de'] = $titles['en'];
            $descriptions['de'] = $descriptions['en'];
        }

        foreach ($titles as $lang => $title) {
            HomeBanner::create([
                'unique_id' => $uniqueId,
                'language' => $lang,
                'title' => $title,
                'description' => $descriptions[$lang] ?? '',
                'image' => $imagePath,
                'link' => $request->input('link'),
            ]);
        }

        flash_session('success', __('Banner created successfully'));
        return redirect()->route('admin.home-banner.index');
    }


    public function edit(HomeBanner $home_banner)
    {
        $translations = HomeBanner::where('unique_id', $home_banner->unique_id)
            ->get()
            ->keyBy('language')
            ->map(function ($item) {
                return [
                    'title' => $item->title,
                    'description' => $item->description,
                ];
            });
        return view('admin.homeBanner.edit', [
            'title' => "Banner",
            'data' => $home_banner,
            'translations' => $translations,
            'breadcrumbs' => [
                'Banner' => route('admin.home-banner.index'),
                'Edit' => route('admin.home-banner.edit', $home_banner->id),
            ],
        ]);
    }

    public function update(Request $request, HomeBanner $home_banner)
    {
        $request->validate([
            // 'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'link' => 'nullable|string|url',
            'title' => 'required|array',
            'title.*' => 'nullable|string',
            'description' => 'required|array',
            'description.*' => 'nullable|string',
        ]);

        $uniqueId = $home_banner->unique_id;

        // ✅ Handle Image Upload (optional, replace existing image)
        $imagePath = $home_banner->getRawOriginal('image');

        if (!empty($request->image_base64)) {
            $imagePath = upload_base_64_img($request->image_base64, get_constants('upload_paths.home_banner_images'));

            if ($home_banner->image) {
                un_link_file($home_banner->image);
            }
            if (!$imagePath) {
                flash_session('error', 'Image upload failed.');
                return redirect()->back()->withInput();
            }
        }

        foreach ($request->title as $lang => $title) {
            $description = $request->description[$lang] ?? '';

            $banner = HomeBanner::where('unique_id', $uniqueId)
                ->where('language', $lang)
                ->first();

            if ($banner) {
                $banner->update([
                    'title' => $title,
                    'description' => $description,
                    'image' => $imagePath,
                    'link' => $request->input('link'),
                ]);
            } else {
                HomeBanner::create([
                    'unique_id' => $uniqueId,
                    'language' => $lang,
                    'title' => $title,
                    'description' => $description,
                    'image' => $imagePath,
                    'link' => $request->input('link'),
                ]);
            }
        }

        flash_session('success', 'Banner updated successfully.');
        return redirect()->route('admin.home-banner.index');
    }

    public function destroy(HomeBanner $home_banner)
    {
        try {
            if ($home_banner->image) {
                un_link_file($home_banner->image);
            }
            HomeBanner::where('unique_id', $home_banner->unique_id)->delete();
            return response()->json([
                'success' => true,
                'message' => __('Banner has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Banner: ') . $e->getMessage(),
            ], 500);
        }
    }
}

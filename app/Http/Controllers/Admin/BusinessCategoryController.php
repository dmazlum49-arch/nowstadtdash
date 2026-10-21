<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use Str;
use Yajra\DataTables\Facades\DataTables;

class BusinessCategoryController extends Controller
{
    public function __construct()
    {
        // $multi
    }
    public function index()
    {
        if (request()->ajax()) {
            $category = BusinessCategory::where('language', 'en')->get();
            return DataTables::of($category)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    return get_fancy_box_html($row->image);
                })
                ->addColumn('business_category', function ($row) {
                    return $row->business_category;
                })
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.business-category.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.business-category.edit', $row->id),
                            // 'view' => route('admin.business-category.show', $row->id),
                            'delete' => route('admin.business-category.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('admin.businessCategory.index', [
            'title' => "Business Categories",
            'breadcrumbs' => [
                'Business Categories' => route('admin.business-category.index'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $category = BusinessCategory::find($id);
        if ($category) {
            $categories = BusinessCategory::where('unique_id', $category->unique_id)->get();
            foreach ($categories as $cat) {
                $cat->update(['status' => ($cat->status == "inactive") ? "active" : "inactive"]);
                $cat->save();
            }
            return response()->json(['success' => true, 'message' => 'Business Category status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Business Category not found.']);
    }

    public function show(BusinessCategory $business_category)
    {
        return view('admin.businessCategory.show', [
            'title' => "Business Categories",
            'data' => $business_category,
            'breadcrumbs' => [
                'Business Categories' => route('admin.business-category.index'),
                'View' => route('admin.business-category.show', $business_category->id),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.businessCategory.create', [
            'title' => "Business Categories",
            'breadcrumbs' => [
                'Business Categories' => route('admin.business-category.index'),
                'Create' => route('admin.business-category.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'business_category' => 'required',
        // ]);

        // $category = new BusinessCategory();
        // $category->business_category = $request->business_category;

        // $category->save();

        // flash_session('success', __('Business Category created successfully'));
        // return redirect()->route('admin.business-category.index');

        $request->validate([
            'business_category' => 'required|array',
            'business_category.*' => 'nullable|string|max:255',
        ]);

        $imagePath = null;
        if (!empty($request->image_base64)) {
            $imagePath = upload_base_64_img($request->image_base64, get_constants('upload_paths.category_images'));

            if (!$imagePath) {
                flash_session('error', 'Image upload failed.');
                return redirect()->back()->withInput();
            }
        }

        do {
            $uniqueId = mt_rand(100000, 999999);
        } while (BusinessCategory::where('unique_id', $uniqueId)->exists());

        $input = $request->business_category;

        if (empty($input['de'])) {
            $input['de'] = $input['en'];
        }

        foreach ($input as $lang => $name) {
            BusinessCategory::create([
                'business_category' => $name ?? '',
                'language' => $lang,
                'unique_id' => $uniqueId,
                'image' => $imagePath,
            ]);
        }
        flash_session('success', __('Business Category created successfully'));
        return redirect()->route('admin.business-category.index');
    }

    public function edit(BusinessCategory $business_category)
    {
        $translations = BusinessCategory::where('unique_id', $business_category->unique_id)
            ->pluck('business_category', 'language');
        return view('admin.businessCategory.edit', [
            'title' => "Business Categories",
            'data' => $business_category,
            'translations' => $translations,
            'breadcrumbs' => [
                'Business Categories' => route('admin.business-category.index'),
                'Edit' => route('admin.business-category.edit', $business_category->id),
            ],
        ]);
    }

    public function update(Request $request, BusinessCategory $business_category)
    {
        // $request->validate([
        //     'business_category' => 'required',
        // ]);

        // $business_category->business_category = $request->business_category;
        // $business_category->save();
        $request->validate([
            'business_category' => 'required|array',
            'business_category.*' => 'nullable|string|max:255',
        ]);

        $uniqueId = $business_category->unique_id;

        $imagePath = $business_category->getRawOriginal('image');

        if (!empty($request->image_base64)) {
            $imagePath = upload_base_64_img($request->image_base64, get_constants('upload_paths.category_images'));

            if ($business_category->image) {
                un_link_file($business_category->image);
            }
            if (!$imagePath) {
                flash_session('error', 'Image upload failed.');
                return redirect()->back()->withInput();
            }
        }

        foreach ($request->business_category as $lang => $name) {
            $category = BusinessCategory::where('unique_id', $uniqueId)
                ->where('language', $lang)
                ->first();

            if ($category) {
                $category->business_category = $name ?? '';
                $category->image = $imagePath;
                $category->save();
            } else {
                BusinessCategory::create([
                    'business_category' => $name ?? '',
                    'language' => $lang,
                    'unique_id' => $uniqueId,
                    'image' => $imagePath,
                ]);
            }
        }
        flash_session('success', 'Business Category updated successfully.');
        return redirect()->route('admin.business-category.index');
    }

    public function destroy(BusinessCategory $business_category)
    {
        try {
            // $business_category->delete();
            BusinessCategory::where('unique_id', $business_category->unique_id)->delete();
            return response()->json([
                'success' => true,
                'message' => __('Business Category has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Business Category: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function checkName(Request $request)
    {
        $business_category = $request->val;

        $exists = BusinessCategory::where('business_category', $business_category)->exists();

        return response()->json(!$exists);
    }

    public function checkNameInEdit(Request $request)
    {
        $id = $request->id;
        $name = $request->val;

        $current = BusinessCategory::find($id);
        if (!$current) {
            return response()->json(['status' => false, 'message' => 'Invalid record'], 404);
        }

        $exists = BusinessCategory::where('business_category', $name)
            ->where('unique_id', '!=', $current->unique_id)
            ->exists();

        return response()->json(!$exists);
    }
}

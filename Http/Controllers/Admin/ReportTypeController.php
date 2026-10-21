<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReportTypeController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $banner = ReportType::all();
            return DataTables::of($banner)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    return get_fancy_box_html($row->image);
                })
                ->addColumn('name', function ($row) {
                    return $row->name;
                })
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.report-type.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.report-type.edit', $row->id),
                            // 'view' => route('admin.report-type.show', $row->id),
                            'delete' => route('admin.report-type.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('admin.reportType.index', [
            'title' => "Report Type",
            'breadcrumbs' => [
                'Report Type' => route('admin.report-type.index'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $banner = ReportType::find($id);
        if ($banner) {
            $banners = ReportType::where('id', $banner->id)->get();
            foreach ($banners as $cat) {
                $cat->update(['status' => ($cat->status == "inactive") ? "active" : "inactive"]);
                $cat->save();
            }
            return response()->json(['success' => true, 'message' => 'Report Type status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Report Type not found.']);
    }

    public function show(ReportType $report_type)
    {
        return view('admin.reportType.show', [
            'title' => "Report Type",
            'data' => $report_type,
            'breadcrumbs' => [
                'Report Type' => route('admin.report-type.index'),
                'View' => route('admin.report-type.show', $report_type->id),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.reportType.create', [
            'title' => "Report Type",
            'breadcrumbs' => [
                'Report Type' => route('admin.report-type.index'),
                'Create' => route('admin.report-type.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            // 'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'nullable|string|max:255',
        ]);

        $imagePath = null;
        if (!empty($request->image_base64)) {
            $imagePath = upload_base_64_img($request->image_base64, get_constants('upload_paths.report_type_images'));

            if (!$imagePath) {
                flash_session('error', 'Image upload failed.');
                return redirect()->back()->withInput();
            }
        }

        $reportType = ReportType::create([
            'name' => $request->name,
            'image' => $imagePath,
        ]);
        flash_session('success', __('Report Type created successfully'));
        return redirect()->route('admin.report-type.index');
    }


    public function edit(ReportType $report_type)
    {
           
        return view('admin.reportType.edit', [
            'title' => "Report Type",
            'data' => $report_type,
            'breadcrumbs' => [
                'Report Type' => route('admin.report-type.index'),
                'Edit' => route('admin.report-type.edit', $report_type->id),
            ],
        ]);
    }

    public function update(Request $request, ReportType $report_type)
    {
        $request->validate([
            // 'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'link' => 'nullable|string|url',
            'name' => 'required|string|max:255',
        ]);

        if (!empty($request->image_base64)) {
            $imagePath = upload_base_64_img($request->image_base64, get_constants('upload_paths.report_type_images'));

            if ($report_type->image) {
                un_link_file($report_type->image);
            }
            if (!$imagePath) {
                flash_session('error', 'Image upload failed.');
                return redirect()->back()->withInput();
            }
        }

        $report_type->update([
            'name' => $request->name,
            'image' => $imagePath ?? $report_type->getRawOriginal('image'),
        ]);

        flash_session('success', 'Report Type updated successfully.');
        return redirect()->route('admin.report-type.index');
    }

    public function destroy(ReportType $report_type)
    {
        try {
            if ($report_type->image) {
                un_link_file($report_type->image);
            }
            ReportType::where('id', $report_type->id)->delete();
            return response()->json([
                'success' => true,
                'message' => __('Report Type has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Report Type: ') . $e->getMessage(),
            ], 500);
        }
    }
}

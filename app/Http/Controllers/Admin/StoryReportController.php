<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\Request;
use App\Models\StoryReport;
use Yajra\DataTables\DataTables;
use Str;

class StoryReportController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {

            $storyReport = StoryReport::with(['user'])->where('status', 'pending')->latest();

            return DataTables::of($storyReport)
                ->addIndexColumn()

                // Reported By Username
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })

                ->addColumn('reported_from', function ($row) {
                    return $row->story->user->name ?? '-';
                })

                ->addColumn('reason', function ($row) {
                    $full = strip_tags($row->reason);
                    $short = Str::limit($full, 30);
                    return '<span class="desc-preview" style="cursor:pointer; color:#1CBADA;" data-full="' . e($full) . '">' . $short . '</span>';
                })
                ->addColumn('date_added', function ($row) {
                    return $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : 'N/A';
                })

                // Action buttons
                ->addColumn('action', function ($row) {

                    $deleteStoryUrl = route('admin.delete-story', $row->story_id);
                    $ignoreUrl = route('admin.ignore-story', $row->id);

                    return '
                        <button class="font-size-14 me-2 btn btn-outline-danger btn-sm delete-story" data-url="' . $deleteStoryUrl . '"><i class="fa fa-trash"></i></button>
                        <button class="font-size-14 me-2 btn btn-outline-secondary btn-sm ignore-report" data-url="' . $ignoreUrl . '"><i class="fa fa-eye-slash"></i></button>
                    ';
                })

                ->rawColumns(['reason', 'status', 'action'])
                ->make(true);
        }

        return view('admin.storyReport.index', [
            'title' => "Report Story",
            'breadcrumbs' => [
                'Report Story' => route('admin.story-report.index'),
            ]
        ]);
    }

    public function show(StoryReport $storyReport)
    {
        return view('admin.storyReport.show', [
            'title' => "Report Story Details",
            'data' => $storyReport->load(['user']),
            'breadcrumbs' => [
                'Report Stories' => route('admin.story-report.index'),
                'View' => route('admin.story-report.show', $storyReport->id),
            ]
        ]);
    }

    public function destroy(StoryReport $storyReport)
    {
        try {
            $storyReport->delete();

            return response()->json([
                'success' => true,
                'message' => __('Report Story has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Delete failed: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteStory($storyId)
    {
        $post = Story::find($storyId);

        if ($post) {

            // FULL DELETE from database
            $post->delete();

            // Mark all its reports as resolved
            StoryReport::where('story_id', $storyId)->update([
                'status' => 'resolved'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Story permanently deleted.'
        ]);
    }

    public function ignore($reportId)
    {
        StoryReport::where('id', $reportId)->update([
            'status' => 'reviewed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report ignored.'
        ]);
    }
}

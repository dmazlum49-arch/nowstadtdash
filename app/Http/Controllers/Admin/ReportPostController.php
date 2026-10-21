<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\ReportPost;
use Yajra\DataTables\DataTables;
use Str;
class ReportPostController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {

            $reportPost = ReportPost::with(['user'])->where('status', 'pending')->latest();

            return DataTables::of($reportPost)
                ->addIndexColumn()

                // Reported By Username
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })

                ->addColumn('reported_from', function ($row) {
                    return $row->post->user->name ?? '-';
                })
                // Post Title
                ->addColumn('post_title', function ($row) {
                    return Str::limit($row->post->title ?? '-', 30);
                })
                ->addColumn('category', function ($row) {
                    return Str::limit($row->category ?? '-', 30);
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

                    $viewPostUrl = route('admin.report-post.show', $row->id);
                    $deletePostUrl = route('admin.delete-post', $row->post_id);
                    $banPostUrl = route('admin.ban-post', $row->post_id);
                    $ignoreUrl = route('admin.ignore', $row->id);

                    return '
                        <a href="' . $viewPostUrl . '" class="font-size-14 me-2 btn btn-outline-primary btn-sm"><i class="fa fa-eye"></i></a>
                        <button class="font-size-14 me-2 btn btn-outline-danger btn-sm delete-post" data-url="' . $deletePostUrl . '"><i class="fa fa-trash"></i></button>
                        <button class="font-size-14 me-2 btn btn-outline-warning btn-sm ban-post" data-url="' . $banPostUrl . '"><i class="fa fa-ban"></i></button>
                        <button class="font-size-14 me-2 btn btn-outline-secondary btn-sm ignore-report" data-url="' . $ignoreUrl . '"><i class="fa fa-eye-slash"></i></button>
                    ';
                })

                ->rawColumns(['reason', 'status', 'action'])
                ->make(true);
        }

        return view('admin.reportPost.index', [
            'title' => "Report Post",
            'breadcrumbs' => [
                'Report Post' => route('admin.report-post.index'),
            ]
        ]);
    }

    public function show(ReportPost $reportPost)
    {
        return view('admin.reportPost.show', [
            'title' => "Report Post Details",
            'data' => $reportPost->load(['user']),
            'breadcrumbs' => [
                'Report Posts' => route('admin.report-post.index'),
                'View' => route('admin.report-post.show', $reportPost->id),
            ]
        ]);
    }

    public function destroy(ReportPost $reportPost)
    {
        try {
            $reportPost->delete();

            return response()->json([
                'success' => true,
                'message' => __('Report Post has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Delete failed: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function deletePost($postId)
    {
        $post = Post::find($postId);

        if ($post) {

            // FULL DELETE from database
            $post->delete();

            // Mark all its reports as resolved
            ReportPost::where('post_id', $postId)->update([
                'status' => 'resolved'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post permanently deleted.'
        ]);
    }

    public function banPost($postId)
    {
        $post = Post::find($postId);

        if ($post) {
            $post->update([
                'status' => 'banned'
            ]);

            ReportPost::where('post_id', $postId)->update([
                'status' => 'resolved'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post banned successfully.'
        ]);
    }

    public function ignore($reportId)
    {
        ReportPost::where('id', $reportId)->update([
            'status' => 'reviewed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report ignored.'
        ]);
    }

}
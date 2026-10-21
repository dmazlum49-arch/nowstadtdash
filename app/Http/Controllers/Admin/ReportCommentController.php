<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PostComment;
use App\Models\ReportComment;
use Yajra\DataTables\DataTables;
use Str;
class ReportCommentController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {

            $reportComment = ReportComment::with(['user'])->where('status', 'pending')->latest();

            return DataTables::of($reportComment)
                ->addIndexColumn()

                // Reported By Username
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })

                ->addColumn('reported_from', function ($row) {
                    return $row->comment->user->name ?? '-';
                })
                ->addColumn('comment', function ($row) {
                    // return Str::limit($row->comment->comment ?? '-', 30);
                    $full = strip_tags($row->comment->comment ?? '-');
                    $short = Str::limit($full, 30);
                    return '<span class="comment-preview" style="cursor:pointer; color:#1CBADA;" data-full="' . e($full) . '">' . $short . '</span>';
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

                    $deleteCommentUrl = route('admin.delete-comment', $row->comment_id);
                    $banCommentUrl = route('admin.ban-comment', $row->comment_id);
                    $ignoreUrl = route('admin.ignore-comment', $row->id);

                    return '
                        <button class="font-size-14 me-2 btn btn-outline-danger btn-sm delete-comment" data-url="' . $deleteCommentUrl . '"><i class="fa fa-trash"></i></button>
                        <button class="font-size-14 me-2 btn btn-outline-warning btn-sm ban-comment" data-url="' . $banCommentUrl . '"><i class="fa fa-ban"></i></button>
                        <button class="font-size-14 me-2 btn btn-outline-secondary btn-sm ignore-report" data-url="' . $ignoreUrl . '"><i class="fa fa-eye-slash"></i></button>
                    ';
                })

                ->rawColumns(['reason','comment', 'status', 'action'])
                ->make(true);
        }

        return view('admin.reportComment.index', [
            'title' => "Report Comment",
            'breadcrumbs' => [
                'Report Comment' => route('admin.report-comment.index'),
            ]
        ]);
    }

    public function show(ReportComment $reportComment)
    {
        return view('admin.reportComment.show', [
            'title' => "Report Comment Details",
            'data' => $reportComment->load(['user', 'comment.post.post_type']),
            'breadcrumbs' => [
                'Report Comments' => route('admin.report-comment.index'),
                'View' => route('admin.report-comment.show', $reportComment->id),
            ]
        ]);
    }

    public function destroy(ReportComment $reportComment)
    {
        try {
            $reportComment->delete();

            return response()->json([
                'success' => true,
                'message' => __('Report Comment has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Delete failed: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteComment($commentId)
    {
        $comment = PostComment::find($commentId);

        if ($comment) {
            // FULL DELETE from database
            $comment->delete();
            // Mark all its reports as resolved
            ReportComment::where('comment_id', $commentId)->update([
                'status' => 'resolved'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment permanently deleted.'
        ]);
    }

    public function banComment($commentId)
    {
        $comment = PostComment::find($commentId);

        if ($comment) {
            $comment->update([
                'status' => 'banned'
            ]);

            ReportComment::where('comment_id', $commentId)->update([
                'status' => 'resolved'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment banned successfully.'
        ]);
    }

    public function ignore($reportId)
    {
        ReportComment::where('id', $reportId)->update([
            'status' => 'reviewed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report ignored.'
        ]);
    }

}
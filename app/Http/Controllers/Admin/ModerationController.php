<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\ThreadMessage;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ModerationController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.moderation.posts');
    }

    public function posts(Request $request)
    {
        if ($request->ajax()) {
            $data = Post::with('user')->where('moderation_status', 'flagged')->latest('ai_flagged_at');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user_name', function($row){
                    return $row->user->name ?? 'N/A';
                })
                ->addColumn('flagged_at', function($row){
                    return $row->ai_flagged_at;
                })
                ->addColumn('details', function($row){
                    return $row->moderation_details;
                })
                ->addColumn('action', function($row){
                    $approveUrl = route('admin.moderation.approve-post', $row->id);
                    $rejectUrl = route('admin.moderation.reject-post', $row->id);
                    return '<a href="'.$approveUrl.'" class="btn btn-success btn-sm">Approve</a> <a href="'.$rejectUrl.'" class="btn btn-danger btn-sm">Reject/Ban</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        
        return view('admin.moderation.posts', [
            'title' => 'Flagged Posts'
        ]);
    }

    public function comments(Request $request)
    {
        if ($request->ajax()) {
            $data = PostComment::with('user', 'post')->where('moderation_status', 'flagged')->latest('ai_flagged_at');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user_name', function($row){
                    return $row->user->name ?? 'N/A';
                })
                ->addColumn('post_title', function($row){
                    return $row->post->title ?? 'N/A';
                })
                ->addColumn('flagged_at', function($row){
                    return $row->ai_flagged_at;
                })
                 ->addColumn('details', function($row){
                    return $row->moderation_details;
                })
                ->addColumn('action', function($row){
                    $approveUrl = route('admin.moderation.approve-comment', $row->id);
                    $rejectUrl = route('admin.moderation.reject-comment', $row->id);
                    return '<a href="'.$approveUrl.'" class="btn btn-success btn-sm">Approve</a> <a href="'.$rejectUrl.'" class="btn btn-danger btn-sm">Reject</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.moderation.comments', [
            'title' => 'Flagged Comments'
        ]);
    }

    public function messages(Request $request)
    {
        if ($request->ajax()) {
            $data = ThreadMessage::with('sender', 'receiver')->where('moderation_status', 'flagged')->latest('ai_flagged_at');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('sender_name', function($row){
                    return $row->sender->name ?? 'N/A';
                })
                ->addColumn('receiver_name', function($row){
                    return $row->receiver->name ?? 'N/A';
                })
                ->addColumn('flagged_at', function($row){
                    return $row->ai_flagged_at;
                })
                 ->addColumn('details', function($row){
                    return $row->moderation_details;
                })
                ->addColumn('action', function($row){
                    $approveUrl = route('admin.moderation.approve-message', $row->id);
                    $rejectUrl = route('admin.moderation.reject-message', $row->id);
                    return '<a href="'.$approveUrl.'" class="btn btn-success btn-sm">Approve</a> <a href="'.$rejectUrl.'" class="btn btn-danger btn-sm">Delete</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.moderation.messages', [
            'title' => 'Flagged Messages'
        ]);
    }

    // ACTIONS

    public function approvePost($id)
    {
        $post = Post::findOrFail($id);
        $post->update(['moderation_status' => 'approved', 'status' => 'active']);
        return back()->with('success', 'Post approved successfully.');
    }

    public function rejectPost($id)
    {
        $post = Post::findOrFail($id);
        $post->update(['moderation_status' => 'rejected', 'status' => 'banned']);
        return back()->with('success', 'Post rejected/banned successfully.');
    }

    public function approveComment($id)
    {
        $comment = PostComment::findOrFail($id);
        $comment->update(['moderation_status' => 'approved', 'status' => 'active']); // Assuming 'active' is default valid status
        return back()->with('success', 'Comment approved successfully.');
    }

    public function rejectComment($id)
    {
        $comment = PostComment::findOrFail($id);
        $comment->delete(); // Or soft delete / set status to banned
        return back()->with('success', 'Comment deleted successfully.');
    }

    public function approveMessage($id)
    {
        $msg = ThreadMessage::findOrFail($id);
        $msg->update(['moderation_status' => 'approved']);
        return back()->with('success', 'Message approved successfully.');
    }

    public function rejectMessage($id)
    {
        $msg = ThreadMessage::findOrFail($id);
        $msg->delete(); // Delete the message
        return back()->with('success', 'Message deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\EventRejectedMail;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventImages;
use App\Models\GeneralSetting;
use App\Models\Post;
use App\Models\PostImages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $post = Post::where('user_id', Auth::user()->primaryId())->get();
            return DataTables::of($post)
                ->addIndexColumn()
                ->addColumn('description', function ($row) {
                    $full = strip_tags($row->description);
                    $short = Str::limit($full, 25);
                    return '<span class="desc-preview" style="cursor:pointer;" data-full="' . e($full) . '">' . $short . '</span>';
                })
                ->addColumn('action', function ($row) {
                    
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('user.post.show', $row->id),
                            'edit' => route('user.post.edit', $row->id),
                            'delete' => route('user.post.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);

                })
                ->rawColumns(['action', 'description'])
                ->make(true);
        }

        return view('user.post.index', [
            'title' => "Post",
            'breadcrumbs' => [
                'Post' => route('user.post.index'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $post = Post::find($id);
        if ($post) {
            $post->update(['status' => ($post->status == "inactive") ? "active" : "inactive"]);
            $post->save();
            return response()->json(['success' => true, 'message' => 'Post status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Post not found.']);
    }

    public function show(Post $post)
    {
        $post = $post->load('postImages');
        return view('user.post.show', [
            'title' => "Post",
            'data' => $post,
            'breadcrumbs' => [
                'Post' => route('user.post.index'),
                'View' => route('user.post.show', $post),
            ],
        ]);
    }

    public function create()
    {
        return view('user.post.create', [
            'title' => "Add Post",
            'breadcrumbs' => [
                'Post' => route('user.post.index'),
                'Add' => route('user.post.create'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::user()->primaryId();
        $request->validate([
            // 'title' => ['required'],
            'description' => ['required'],
            'city' => ['required'],
            'post_images.*' => ['nullable', 'file','mimetypes:image/jpeg,image/png,image/jpg,video/mp4,video/quicktime,video/x-msvideo'],
            'post_thumbnails.*' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/jpg'],
        ]);

        $post = Post::create([
            'user_id' => $userId,
            // 'title' => $request->title,
            'description' => $request->description,
            'city' => $request->city,
        ]);

        if ($request->hasFile('post_images')) {

            foreach ($request->file('post_images') as $index => $image) {
                $imagePath = upload_file("post_images.{$index}", 'post_images');
                $mimeType = $image->getMimeType();
                $type = str_contains($mimeType, 'video') ? 'video' : 'image';

                $thumbnailPath = null;

                // If it's a video and thumbnail is provided in same index
                // if ($type === 'video' && $request->hasFile("post_thumbnails.$index")) {
                //     $thumbnailPath = upload_file("post_thumbnails.$index", 'post_images');
                // }
                if ($type === 'video') {
                    $thumbnailFileName = 'thumb_' . uniqid() . '.jpg';
                    $thumbnailRelativePath = 'uploads/posts/' . $thumbnailFileName;
                    $thumbnailFullPath = public_path($thumbnailRelativePath);

                    // Generate thumbnail using helper
                    $generated = generate_video_thumbnail($imagePath, $thumbnailFullPath);

                    if ($generated) {
                        $thumbnailPath = $thumbnailRelativePath;
                    }
                }

                PostImages::create([
                    'post_id' => $post->id,
                    'image_path' => $imagePath,
                    'thumbnail_path' => $thumbnailPath,
                    'type' => $type,
                ]);
            }
        }

        flash_session('success', 'Post created successfully.');
        return redirect()->route('user.post.index');
        
    }

    public function edit(Post $post)
    {
        return view('user.post.edit', [
            'title' => "Edit Post",
            'data' => $post,
            'breadcrumbs' => [
                'Post' => route('user.post.index'),
                'Edit' => route('user.post.edit', $post->id),
            ],
        ]);
    }

    public function update(Request $request, Post $post)
{
    $request->validate([
        // 'title' => ['required'],
        'description' => ['required'],
        'city' => ['required'],
        'post_images.*' => ['nullable','file','mimetypes:image/jpeg,image/png,image/jpg,video/mp4,video/quicktime,video/x-msvideo'],
        'post_thumbnails.*' => ['nullable','file','mimetypes:image/jpeg,image/png,image/jpg'],
    ]);

    $post->update([
        // 'title' => $request->title,
        'description' => $request->description,
        'city' => $request->city,
    ]);

    if ($request->hasFile('post_images')) {
        foreach ($request->file('post_images') as $index => $file) {
            $path = upload_file("post_images.$index", 'post_images');
            $mimeType = $file->getMimeType();
            $type = str_contains($mimeType, 'video') ? 'video' : 'image';

            $thumbnailPath = null;
            // if ($type === 'video' && $request->hasFile("post_thumbnails.$index")) {
            //     $thumbnailPath = upload_file("post_thumbnails.$index", 'post_images');
            // }

            if ($type === 'video') {
                $thumbnailFileName = 'thumb_' . uniqid() . '.jpg';
                $thumbnailRelativePath = 'uploads/posts/' . $thumbnailFileName;
                $thumbnailFullPath = public_path($thumbnailRelativePath);

                // Generate thumbnail using helper
                $generated = generate_video_thumbnail($path, $thumbnailFullPath);

                if ($generated) {
                    $thumbnailPath = $thumbnailRelativePath;
                }
            }

            PostImages::create([
                'post_id' => $post->id,
                'image_path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'type' => $type,
            ]);
        }
    }

    flash_session('success', 'Post updated successfully.');
    return redirect()->route('user.post.index');
}

    public function destroy($id)
    {
        try {
            $post = Post::find($id);
            $post->postImages()->delete();
            $post->delete();
            return response()->json([
                'success' => true,
                'message' => __('Post has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Post: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteImage($id)
{
    $image = PostImages::findOrFail($id);

    if ($image->image_path) {
        un_link_file($image->image_path); // custom helper
    }

    if ($image->thumbnail_path) {
        un_link_file($image->thumbnail_path);
    }

    $image->delete();

    return response()->json(['success' => true]);
}

}

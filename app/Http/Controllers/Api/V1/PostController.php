<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Models\BlockUser;
use App\Models\BookmarkPost;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostImages;
use App\Models\PostLike;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends ResponseController
{
    public function createEditPost(Request $request)
    {
        $userId = $request->user()->id;
        $this->directValidation([
            'title' => ['nullable'],
            'description' => ['nullable'],
            'city' => ['nullable'],
            'post_images.*' => ['nullable', 'file','mimetypes:image/jpeg,image/png,image/jpg,video/mp4,video/quicktime,video/x-msvideo'],
            'post_thumbnails.*' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/jpg'],
        ]);

        $user = $request->user();

        $limits = getBusinessUserFinalPostLimits($user->id);

        $maxPosts = $limits['max_posts'];

        if (!$limits['has_subscription']) {
            return response()->json([
                'status' => 403,
                'message' => $limits['message']
            ]);
        }

        if ($user->posts()->count() >= $maxPosts) {
            return response()->json([
                'status' => 403,
                'message' => 'Post limit reached.',
            ]);
        }

        // MODERATION CHECK
        $moderationService = app(\App\Services\ContentModerationService::class);
        $checkText = ($request->title ?? '') . ' ' . ($request->description ?? '');
        $moderationResult = $moderationService->checkContent($checkText);

        $isFlagged = $moderationResult['flagged'] ?? false;

        $moderationData = [
            'moderation_status' => $moderationResult['flagged'] ? 'flagged' : 'pending',
            'moderation_details' => json_encode($moderationResult),
            'ai_flagged_at' => $moderationResult['flagged'] ? now() : null,
        ];

        if ($moderationResult['flagged']) {
        }

        if ($request->filled('post_id')) {
            $post = Post::findOrFail($request->post_id);
            $post->update(array_merge([
                'title' => $request->title,
                'description' => $request->description,
                'city' => $request->city,
            ], $moderationData));

            $message = __('api.success_post_updated');

        } else {
            $post = Post::create(array_merge([
                'user_id' => $userId,
                'title' => $request->title,
                'description' => $request->description,
                'city' => $request->city,
            ], $moderationData));

            $message = __('api.success_post_created');
        }

        if ($moderationResult['flagged']) {
            $message .= ' (Flagged for review)';
        }


        if ($request->hasFile('post_images')) {

            foreach ($request->file('post_images') as $index => $image) {
                $imagePath = upload_file("post_images.{$index}", 'post_images');
                $mimeType = $image->getMimeType();
                $type = str_contains($mimeType, 'video') ? 'video' : 'image';

                $thumbnailPath = null;

                if ($type === 'video' && $request->hasFile("post_thumbnails.$index")) {
                    $thumbnailPath = upload_file("post_thumbnails.$index", 'post_images');
                }

                PostImages::create([
                    'post_id' => $post->id,
                    'image_path' => $imagePath,
                    'thumbnail_path' => $thumbnailPath,
                    'type' => $type,
                ]);
            }
        }

        if ($isFlagged) {
            return ResponseHelper::send(422, __('We detected inappropriate language in your post, Please try again.'));
        }

        return ResponseHelper::send(200, $message, [
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'city' => $post->city,
            'post_images' => $post->postImages->map->toResponseArray(),
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ]);
    }

    public function myPostList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $posts = Post::with(['postImages', 'user'])->whereNotIn('moderation_status', ['flagged', 'rejected'])->where(['user_id' => $userId])->latest()->limit($limit)->offset($offset)->get();

        $postIds = $posts->pluck('id')->toArray();

        $favoritePostsIds = PostLike::where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->toArray();
        $bookmarkedPostIds = BookmarkPost::where('user_id', $userId)
            ->whereIn('post_id', values: $postIds)
            ->pluck('post_id')
            ->toArray();

        $filteredPosts = $posts->map(function ($post) use ($favoritePostsIds, $bookmarkedPostIds) {
            return [
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'profile_image' => $post->user->profile_image ? url($post->user->profile_image) : null,
                    'is_verified' => $post->user->is_verified,
                ],
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'city' => $post->city,
                'is_bookmark' => in_array($post->id, $bookmarkedPostIds),
                'is_like' => in_array($post->id, $favoritePostsIds),
                'likes_count' => $post->like_count,
                'comments_count' => $post->comment_count,
                'post_images' => $post->postImages->map->toResponseArray(),
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success_posts_retrieved'), $filteredPosts);
    }

    public function getAllPostList(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;
        $search = $request->search ?? '';
        $blockedUserIds = $user
        ? BlockUser::where('user_id', $userId)->pluck('block_user_id')->toArray()
        : [];
        if ($search) {
            $posts = Post::with(['postImages', 'user'])
                ->whereNotIn('user_id', $blockedUserIds)
                ->where('status', 'active')
                ->whereNotIn('moderation_status', ['flagged', 'rejected'])
                ->whereHas('user')
                ->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
        } else {
            $posts = Post::with(['postImages', 'user'])->where('status', 'active')
                ->whereNotIn('moderation_status', ['flagged', 'rejected'])
                ->whereNotIn('user_id', $blockedUserIds)->whereHas('user');
        }
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $posts = $posts->latest()->limit($limit)->offset($offset)->get();

        $postIds = $posts->pluck('id')->toArray();

        $favoritePostsIds = $user
        ? PostLike::where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->toArray()
        : [];

        $bookmarkedPostIds = $user
            ? BookmarkPost::where('user_id', $userId)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->toArray()
            : [];

        $filteredPosts = $posts->map(function ($post) use ($favoritePostsIds, $bookmarkedPostIds, $user) {
            return [
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'profile_image' => $post->user->profile_image ? url($post->user->profile_image) : null,
                    'is_verified' => $post->user->is_verified,
                ],
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'city' => $post->city,
                'is_bookmark' => $user ? in_array($post->id, $bookmarkedPostIds) : false,
                'is_like' => $user ? in_array($post->id, $favoritePostsIds) : false,
                'likes_count' => $post->like_count,
                'comments_count' => $post->comment_count,
                'post_images' => $post->postImages->map->toResponseArray(),
                'is_my_post' => $user ? ($post->user_id === $user->id) : false,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success_posts_retrieved'), $filteredPosts);
    }

    public function deletePost(Request $request)
    {
        $this->directValidation([
            'post_id' => ['required', 'exists:posts,id'],
        ]);

        $post = Post::findOrFail($request->post_id);
        if ($post->user_id !== $request->user()->id) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        foreach ($post->postImages as $image) {
            un_link_file($image->image_path);
            un_link_file($image->thumbnail_path);
            $image->delete();
        }

        $post->delete();

        return ResponseHelper::send(200, __('api.success_post_deleted'));
    }

    public function deleteImage(Request $request)
    {
        $rawImageIds = $request->input('image_id');
        $imageIds = [];

        if (is_array($rawImageIds)) {
            foreach ($rawImageIds as $value) {
                $imageIds = array_merge($imageIds, explode(',', $value));
            }
        }

        $imageIds = array_filter(array_unique(array_map('trim', $imageIds)));

        $validator = \Validator::make(['image_id' => $imageIds], [
            'image_id' => ['required', 'array'],
            'image_id.*' => ['required', 'exists:post_images,id'],
        ]);

        if ($validator->fails()) {
            return ResponseHelper::send(422, $validator->errors()->first());
        }

        $images = PostImages::whereIn('id', $imageIds)->get();

        if ($images->isEmpty()) {
            return ResponseHelper::send(404, __('api.error_content_not_found'));
        }

        foreach ($images as $image) {
            un_link_file($image->image_path);
            un_link_file($image->thumbnail_path);
            $image->delete();
        }

        return ResponseHelper::send(200, __('api.success'), []);
    }

    public function postDetails(Request $request)
    {
        $this->directValidation([
            'post_id' => ['required', 'exists:posts,id'],
        ]);

        $user = $request->user();
        $userId = $user ? $user->id : null;

        $post = Post::with(['postImages', 'user', 'comments'])->findOrFail($request->post_id);

        $isLike = $user
        ? PostLike::where('user_id', $userId)
            ->where('post_id', $post->id)
            ->exists()
        : false;

        $bookmarkedPost = $user
            ? BookmarkPost::where('user_id', $userId)
                ->where('post_id', $post->id)
                ->exists()
            : false;

        return ResponseHelper::send(200, __('api.success'), [
            'user' => [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'profile_image' => $post->user->profile_image ? url($post->user->profile_image) : null,
                'is_verified' => $post->user->is_verified,
            ],
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'city' => $post->city,
            'is_bookmark' => $bookmarkedPost,
            'is_like' => $isLike,
            'likes_count' => $post->like_count,
            'comments_count' => $post->comment_count,
            'post_images' => $post->postImages->map->toResponseArray(),
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'comments' => $post->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'profile_image' => $comment->user->profile_image ? url($comment->user->profile_image) : null,
                    ],
                ];
            }),

        ]);
    }

    public function otherUserProfile(Request $request)
    {
        $this->directValidation([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['nullable', 'in:post,business,event'],
        ]);

        $user = User::findOrFail($request->user_id);
        $type = $request->type;
        $authUser = $request->user();
        $authUserId = $authUser ? $authUser->id : null;

        $thread = Thread::where(function ($q) use ($request) {
                $q->where('user_id', $request->user_id)
                  ->orWhere('friend_id', $request->user_id);
            })
            ->where(function ($q) use ($authUserId) {
                $q->where('user_id', $authUserId)
                  ->orWhere('friend_id', $authUserId);
            })
            ->first();

        $response = [
            'id' => $user->id,
            'name' => $user->name,
            'profile_image' => $user->profile_image ? url($user->profile_image) : null,
            'is_verified' => $user->is_verified,
            'is_chat' => $thread ? true : false,
            'thread_type' => $thread ? $thread->type : null,
            'thread_id' => $thread ? $thread->id : null,
            'type' => $user->type,
        ];

        if ($type === 'post') {
            $posts = $user->posts()->with('postImages')->where('status', 'active')->latest()->get();

            $postIds = $posts->pluck('id')->toArray();

            $likedPostIds = $authUser
            ? PostLike::where('user_id', $authUserId)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->toArray()
            : [];

            $bookmarkedPostIds = $authUser
                ? BookmarkPost::where('user_id', $authUserId)
                    ->whereIn('post_id', $postIds)
                    ->pluck('post_id')
                    ->toArray()
                : [];

            $response['posts'] = $posts->map(function ($post) use ($likedPostIds, $bookmarkedPostIds, $authUser) {
                return [
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'profile_image' => $post->user->profile_image ? url($post->user->profile_image) : null,
                        'is_verified' => $post->user->is_verified,
                    ],
                    'id' => $post->id,
                    'title' => $post->title,
                    'description' => $post->description,
                    'city' => $post->city,
                    'likes_count' => $post->like_count,
                    'comments_count' => $post->comment_count,
                    'is_bookmark' => $authUser ? in_array($post->id, $bookmarkedPostIds) : false,
                    'is_like' => $authUser ? in_array($post->id, $likedPostIds) : false,
                    'post_images' => $post->postImages->map->toResponseArray(),
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                ];
            });
        } elseif ($type === 'business') {
            $response['businesses'] = $user->businesses()
                ->with('businessImages')
                ->latest()
                ->get()
                ->map(function ($biz) {
                    return [
                        'id' => $biz->id,
                        'name' => $biz->business_name,
                        'city' => $biz->city,
                        'website' => $biz->website,
                        'about' => $biz->about_business,
                        'banner_image' => $biz->banner_image ? url($biz->banner_image) : null,
                        'business_images' => $biz->businessImages->map(function ($img) {
                            return [
                                'id' => $img->id,
                                'image_path' => url($img->image_path),
                            ];
                        }),
                    ];
                });
        } elseif ($type === 'event') {
            $response['events'] = $user->events()
                ->with(['eventImages', 'category', 'joinedUsers'])
                ->latest()
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'event_name' => $event->event_name,
                        'about_event' => $event->about_event,
                        'category' => $event->category->event_category ?? null,
                        'start_date' => $event->start_date,
                        'end_date' => $event->end_date,
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'banner_image' => $event->banner_image ? url($event->banner_image) : null,
                        'event_images' => $event->eventImages->map(function ($img) {
                            return [
                                'id' => $img->id,
                                'image_path' => url($img->image_path),
                            ];
                        }),
                        'joined_users' => $event->joinedUsers->take(3)->map(function ($user) {
                            return [
                                'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                            ];
                        }),
                        'joined_count' => $event->joinedUsers->count(),
                    ];
                });
        }

        return ResponseHelper::send(200, __('api.success'), $response);
    }

    public function likePost(Request $request)
    {
        $this->directValidation([
            'post_id' => ['required', 'exists:posts,id'],
        ]);

        $post = Post::findOrFail($request->post_id);
        $userId = $request->user()->id;
        $user = $request->user();

        if ($post->likes()->where('user_id', $userId)->exists()) {
            // Unlike the post
            $post->likes()->where('user_id', $userId)->delete();
            $post->decrement('like_count');
            if ($post->like_count < 0) {
                $post->like_count = 0;
            }
            $message = __('api.success_post_unliked');
        } else {
            // Like the post
            $post->likes()->create(['user_id' => $userId, 'post_id' => $post->id]);
            $post->increment('like_count');
            $message = __('api.success_post_liked');
            
            $payload = array(
                'from_user_id' =>$userId,
                'push_type' => 6,
                'push_message' => $user->name . ' ' . 'liked your post.',
                'push_title' => 'New Like',
                'object_id' => $post->id,
                'object_type' => null,
                'extra' => [
                    'post_id' => $post->id,
                    'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                ],
            );

            send_push($post->user_id, $payload, true);
        }

        return ResponseHelper::send(200, $message, [
            'likes_count' => $post->like_count,
        ]);
    }

    public function likeList(Request $request)
    {
        $this->directValidation([
            'post_id' => ['required', 'exists:posts,id'],
            'search' => ['nullable', 'string'],
        ]);

        $post = Post::findOrFail($request->post_id);

        $query = $post->likes()->with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $likes = $query->get()->map(function ($like) {
            return [
                'id' => $like->id,
                'user_id' => $like->user->id,
                'name' => $like->user->name,
                'profile_image' => $like->user->profile_image ? url($like->user->profile_image) : null,
                'is_verified' => $like->user->is_verified,
            ];
        });

        return ResponseHelper::send(200, __('api.success'), [
            'like_count' => $post->like_count,
            'likes' => $likes
        ]);
    }

    public function addComment(Request $request)
    {
        $this->directValidation([
            'post_id' => ['required', 'exists:posts,id'],
            'comment' => ['required', 'string'],
            'parent_id' => ['nullable', 'exists:post_comments,id'],
        ]);

        $post = Post::findOrFail($request->post_id);
        $userId = $request->user()->id;

        // MODERATION CHECK
        $moderationService = app(\App\Services\ContentModerationService::class);
        $moderationResult = $moderationService->checkContent($request->comment);

        $isFlagged = $moderationResult['flagged'] ?? false;
        
        $moderationData = [
            'moderation_status' => $moderationResult['flagged'] ? 'flagged' : 'pending',
            'moderation_details' => json_encode($moderationResult),
            'ai_flagged_at' => $moderationResult['flagged'] ? now() : null,
        ];

        $comment = $post->comments()->create(array_merge([
            'user_id' => $userId,
            'post_id' => $post->id,
            'comment' => $request->comment,
            'parent_id' => $request->parent_id,
        ], $moderationData));

        if ($isFlagged) {
            return ResponseHelper::send(422, __('We detected inappropriate language in your comment, Please try again.'));
        }

        $user = $request->user();
        $payload = array(
            'from_user_id' =>$userId,
            'push_type' => 7,
            'push_message' => $user->name . ' ' . 'commented on your post.',
            'push_title' => 'New Comment',
            'object_id' => $post->id,
            'object_type' => null,
            'extra' => [
                'post_id' => $post->id,
                'profile_image' => $user->profile_image ? url($user->profile_image) : null,
            ],
        );
        send_push($post->user_id, $payload, true);

         // Increment comment count only for top-level comments

        // if (!$request->parent_id) {
        //     $post->increment('comment_count');
        // }
        $post->increment('comment_count');

        return ResponseHelper::send(200, __('api.success_comment_added'), [
            'comment' => $comment,
        ]);
    }

    // public function commentList(Request $request)
    // {
    //     $this->directValidation([
    //         'post_id' => ['required', 'exists:posts,id'],
    //     ]);

    //     $post = Post::findOrFail($request->post_id);

    //     $comments = $post->comments()->with('user')->get()->map(function ($comment) {
    //         return [
    //             'id' => $comment->id,
    //             'user_id' => $comment->user->id,
    //             'name' => $comment->user->name,
    //             'profile_image' => $comment->user->profile_image ? url($comment->user->profile_image) : null,
    //             'comment' => $comment->comment,
    //             'created_at' => $comment->created_at,
    //             'replies' => $comment->replies->map(function ($reply) {
    //                 return [
    //                     'id' => $reply->id,
    //                     'user_id' => $reply->user->id,
    //                     'name' => $reply->user->name,
    //                     'profile_image' => $reply->user->profile_image ? url($reply->user->profile_image) : null,
    //                     'comment' => $reply->comment,
    //                     'created_at' => $reply->created_at,
    //                 ];
    //             }),
    //         ];
    //     });

    //     return ResponseHelper::send(200, __('api.success'), $comments);
    // }


    public function commentList(Request $request)
    {
        $this->directValidation([
            'post_id' => ['required', 'exists:posts,id'],
            'filter' => ['nullable', 'in:for_you,newest,all'],
        ]);

        $post = Post::findOrFail($request->post_id);

        $query = $post->comments()
            ->where('status', 'active')
            ->whereNotIn('moderation_status', ['flagged', 'rejected'])
            ->whereNull('parent_id')
            ->with([
            'user',
            'replies' => function ($q) {
                $q->where('status', 'active')
                  ->whereNotIn('moderation_status', ['flagged', 'rejected'])
                  ->with('user');
            }
        ]);

        switch ($request->filter) {

            case 'for_you':
                $query->withCount('replies')
                    ->orderByDesc('replies_count')
                    ->take(10);
                break;

            case 'newest':
                $query->latest();
                break;

            case 'all':
            default:
                $query->oldest();
                break;
        }

        $comments = $query->get()->map(function ($comment) {
            return $this->formatCommentRecursive($comment);
        });

        return ResponseHelper::send(200, __('api.success'), $comments);
    }

    private function formatCommentRecursive($comment)
    {
        return [
            'id' => $comment->id,
            'user_id' => $comment->user->id ?? null,
            'name' => $comment->user->name ?? null,
            'profile_image' => $comment->user && $comment->user->profile_image
                ? url($comment->user->profile_image)
                : null,
            'is_verified' => $comment->user->is_verified ?? false,
            'comment' => $comment->comment,
            'created_at' => $comment->created_at,

            // 🔥 Nested replies (unlimited depth)
            'replies' => $comment->replies->map(function ($reply) {
                return $this->formatCommentRecursive($reply);
            }),
        ];
    }

    public function blockUnBlockUser(Request $request)
    {
        $this->directValidation([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:block,unblock'],
        ]);

        $authUserId = $request->user()->id;
        $blockUserId = $request->user_id;

        if ($authUserId == $blockUserId) {
            return ResponseHelper::send(422, 'You cannot block or unblock yourself.');
        }

        if ($request->type === 'block') {
            $alreadyBlocked = BlockUser::where('user_id', $authUserId)
                ->where('block_user_id', $blockUserId)
                ->first();

            if ($alreadyBlocked) {
                return ResponseHelper::send(200, __('api.success_already_block'));
            }

            BlockUser::create([
                'user_id' => $authUserId,
                'block_user_id' => $blockUserId,
            ]);

            return ResponseHelper::send(200, __('api.success_block'));
        }

        if ($request->type === 'unblock') {
            $blocked = BlockUser::where('user_id', $authUserId)
                ->where('block_user_id', $blockUserId)
                ->first();

            if ($blocked) {
                $blocked->delete();
                return ResponseHelper::send(200, __('api.success_unblock'));
            } else {
                return ResponseHelper::send(404, __('api.error_not_block'));
            }
        }

        return ResponseHelper::send(422, __('api.error_invalid'));
    }

    public function bookmarkPost(Request $request)
    {
        $this->directValidation([
            'post_id' => ['required', 'exists:posts,id'],
        ]);

        $post = Post::findOrFail($request->post_id);
        $userId = $request->user()->id;

        if ($post->bookmarks()->where('user_id', $userId)->exists()) {
            // Unlike the post
            $post->bookmarks()->where('user_id', $userId)->delete();

            $message = __('api.success_post_unbookmarked');
        } else {
            // Like the post
            $post->bookmarks()->create(['user_id' => $userId, 'post_id' => $post->id]);

            $message = __('api.success_post_bookmarked');
        }

        return ResponseHelper::send(200, $message);
    }

    public function bookmarkList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $bookmarkedPosts = BookmarkPost::with([
            'post.user',
            'post.postImages',
        ])
            ->where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();

        $postIds = $bookmarkedPosts->pluck('post.id')->filter()->toArray();

        $favoritePostsIds = PostLike::where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->toArray();

        $response = $bookmarkedPosts->map(function ($bookmark) use ($favoritePostsIds) {
            $post = $bookmark->post;

            if (!$post) {
                return null;
            }

            return [
                'user' => [
                    'id' => $post->user->id ?? null,
                    'name' => $post->user->name ?? null,
                    'profile_image' => $post->user->profile_image ? url($post->user->profile_image) : null,
                    'is_verified' => $post->user->is_verified ?? false,
                ],
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'city' => $post->city,
                'is_bookmark' => true,
                'is_like' => in_array($post->id, $favoritePostsIds),
                'likes_count' => $post->like_count ?? 0,
                'comments_count' => $post->comment_count ?? 0,
                'post_images' => $post->postImages->map->toResponseArray(),
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        })->filter()->values();

        return ResponseHelper::send(200, __('api.success'), $response);
    }

    public function reportPost(Request $request)
    {
        $this->directValidation([
            'post_id' => ['required', 'exists:posts,id'],
            'category' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
        ]);

        $post = Post::findOrFail($request->post_id);
        $userId = $request->user()->id;

        $report = $post->reports()->create([
            'user_id' => $userId,
            'post_id' => $post->id,
            'category' => $request->category,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return ResponseHelper::send(200, __('api.success_post_reported'), [
            'report' => $report,
        ]);
    }

    public function reportComment(Request $request)
    {
        $this->directValidation([
            'comment_id' => ['required', 'exists:post_comments,id'],
            'category' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
        ]);

        $comment = PostComment::findOrFail($request->comment_id);
        $userId = $request->user()->id;

        $report = $comment->reports()->create([
            'user_id' => $userId,
            'comment_id' => $comment->id,
            'category' => $request->category,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return ResponseHelper::send(200, __('api.success_comment_reported'), [
            'report' => $report,
        ]);
    }

    public function deleteComment(Request $request)
    {
        $this->directValidation([
            'comment_id' => ['required', 'exists:post_comments,id'],
        ]);

        $comment = PostComment::findOrFail($request->comment_id);
        $userId = $request->user()->id;

        if ($comment->user_id !== $userId) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        DB::transaction(function () use ($comment) {

            $post = Post::find($comment->post_id);

            // Delete comment
            $comment->delete();

            // Decrease comment count safely
            if ($post && $post->comment_count > 0) {
                $post->decrement('comment_count');
            }
        });

        return ResponseHelper::send(200, __('api.success_comment_deleted'));
    }

}


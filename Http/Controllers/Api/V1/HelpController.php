<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Models\BookmarkHelp;
use App\Models\BookmarkPost;
use App\Models\HelpComment;
use App\Models\HelpImage;
use App\Models\HelpLike;
use App\Models\HelpUser;
use App\Models\PostLike;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ResponseController;
use App\Models\Help;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\ThreadMessage;

class HelpController extends ResponseController
{
    public function createEditHelp(Request $request)
    {
        $userId = $request->user()->id;
        $this->directValidation([
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'string'],
            'longitude' => ['nullable', 'string'],
            'urgency' => ['nullable'],
            'date' => ['nullable', 'date'],
            'time' => ['nullable'],
            'help_images.*' => ['nullable', 'file','mimetypes:image/jpeg,image/png,image/jpg,video/mp4,video/quicktime,video/x-msvideo'],
            'help_thumbnails.*' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/jpg'],
            'help_id' => ['nullable', 'exists:helps,id'],
        ]);

        $user = $request->user();

        if ($request->filled('help_id')) {
            $help = Help::findOrFail($request->help_id);
            $help->update([
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'urgency' => $request->urgency,
                'date' => $request->date,
                'time' => $request->time,
            ]);

            $message = 'Help request updated successfully.';
        } else {
            $help = Help::create([
                'user_id' => $userId,
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'urgency' => $request->urgency,
                'date' => $request->date,
                'time' => $request->time,
            ]);

            $message = 'Help request created successfully.';
        }

        if ($request->hasFile('help_images')) {

            foreach ($request->file('help_images') as $index => $image) {
                $imagePath = upload_file("help_images.{$index}", 'help_images');
                $mimeType = $image->getMimeType();
                $type = str_contains($mimeType, 'video') ? 'video' : 'image';

                $thumbnailPath = null;

                if ($type === 'video' && $request->hasFile("help_thumbnails.$index")) {
                    $thumbnailPath = upload_file("help_thumbnails.$index", 'help_images');
                }

                HelpImage::create([
                    'help_id' => $help->id,
                    'image_path' => $imagePath,
                    'thumbnail_path' => $thumbnailPath,
                    'type' => $type,
                ]);
            }
        }

        return ResponseHelper::send(200, $message, $help);
    }

    public function getAllHelpList(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;

        $limit  = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $userLat = $request->header('latitude') ?? ($user ? $user->latitude : null);
        $userLng = $request->header('longitude') ?? ($user ? $user->longitude : null);

        $query = Help::with(['helpImages', 'user'])
            ->whereHas('user')
            ->withCount('helpers')
            ->withExists([
                'helpers as i_can_help' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ])
            ->where('status', 'pending');

        // if ($userId) {
        //     $query->where('user_id', '!=', $userId);
        // }

        if ($userLat && $userLng) {
            $query->selectRaw("
                helps.*,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
            ", [$userLat, $userLng, $userLat])
            ->orderByRaw("
                CASE urgency
                    WHEN 'urgent' THEN 1
                    WHEN 'time_based' THEN 2
                    WHEN 'flexible' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('distance', 'asc'); // nearest first
        } else {
            $query->orderByRaw("
                CASE urgency
                    WHEN 'urgent' THEN 1
                    WHEN 'time_based' THEN 2
                    WHEN 'flexible' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('created_at', 'desc'); // newest inside urgency
        }

        $helps = $query->skip($offset)->take($limit)->get();

        $helpIds = $helps->pluck('id')->toArray();

        $favoriteHelpIds = $user
            ? HelpLike::where('user_id', $userId)
                ->whereIn('help_id', $helpIds)
                ->pluck('help_id')
                ->toArray()
            : [];

        $bookmarkedHelpIds = $user
            ? BookmarkHelp::where('user_id', $userId)
                ->whereIn('help_id', $helpIds)
                ->pluck('help_id')
                ->toArray()
            : [];

        $filterHelps = $helps->map(function ($help) use ($favoriteHelpIds, $bookmarkedHelpIds, $user, $userId) {
            $thread = null;
            if ($userId) {
                $thread = Thread::where(function ($q) use ($help) {
                        $q->where('user_id', $help->user_id)
                        ->orWhere('friend_id', $help->user_id);
                    })
                    ->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)
                        ->orWhere('friend_id', $userId);
                    })
                    ->first();
            }
            return [
                'user' => [
                    'id' => $help->user->id,
                    'name' => $help->user->name,
                    'profile_image' => $help->user->profile_image ? url($help->user->profile_image) : null,
                    'is_verified' => $help->user->is_verified,
                    'is_chat' => $thread ? true : false,
                    'thread_type' => $thread ? $thread->type : null,
                    'thread_id' => $thread ? $thread->id : null,
                ],
                'id' => $help->id,
                'title' => $help->title,
                'description' => $help->description,
                'location' => $help->location,
                'latitude' => $help->latitude,
                'longitude' => $help->longitude,
                'urgency' => $help->urgency,
                'date' => $help->date,
                'time' => $help->time,
                'status' => $help->status,
                'is_bookmark' => $user ? in_array($help->id, $bookmarkedHelpIds) : false,
                'is_like' => $user ? in_array($help->id, $favoriteHelpIds) : false,
                'is_owner' => $userId ? ($help->user_id === $userId) : false,
                'helpers_count' => $help->helpers_count,
                'like_count' => $help->like_count,
                'comment_count' => $help->comment_count,
                'i_can_help' => $help->i_can_help,
                'distance' => isset($help->distance) ? round($help->distance, 2) : null,
                'help_images' => $help->helpImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_path' => url($image->image_path),
                        'thumbnail_path' => $image->thumbnail_path ? url($image->thumbnail_path) : null,
                        'type' => $image->type,
                    ];
                }),
                'created_at' => $help->created_at,
            ];
        });

        return ResponseHelper::send(
            200,
            'Help list retrieved successfully',
            $filterHelps
        );
    }

    public function getMyhelpRequests(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $status = $request->status ?? 'pending';

        $helps = Help::with(['helpImages', 'user', 'helpers.user:id,name,profile_image'])
            ->withCount(['helpers' => function ($q) {
                $q->where('status', 'accepted');
            }])
            ->where('user_id', $userId)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        $helpIds = $helps->pluck('id')->toArray();

        $favoriteHelpIds = $user
        ? HelpLike::where('user_id', $userId)
            ->whereIn('help_id', $helpIds)
            ->pluck('help_id')
            ->toArray()
        : [];

        $bookmarkedHelpIds = $user
            ? BookmarkHelp::where('user_id', $userId)
                ->whereIn('help_id', $helpIds)
                ->pluck('help_id')
                ->toArray()
            : [];

        $filterHelps = $helps->map(function ($help) use ($favoriteHelpIds, $bookmarkedHelpIds, $user, $userId) {
            return [
                'user' => [
                    'id' => $help->user->id,
                    'name' => $help->user->name,
                    'profile_image' => $help->user->profile_image ? url($help->user->profile_image) : null,
                    'is_verified' => $help->user->is_verified,
                ],
                'id' => $help->id,
                'title' => $help->title,
                'description' => $help->description,
                'location' => $help->location,
                'latitude' => $help->latitude,
                'longitude' => $help->longitude,
                'urgency' => $help->urgency,
                'date' => $help->date,
                'time' => $help->time,
                'status' => $help->status,
                'is_bookmark' => $user ? in_array($help->id, $bookmarkedHelpIds) : false,
                'is_like' => $user ? in_array($help->id, $favoriteHelpIds) : false,
                'is_owner' => true,
                'helpers_count' => $help->helpers_count,
                'like_count' => $help->like_count,
                'comment_count' => $help->comment_count,
                'helpers' => $help->helpers->map(function ($helper) use ($userId) {
                    $thread = null;
                    if ($userId) {
                        $thread = Thread::where(function ($q) use ($helper, $userId) {
                            $q->where('user_id', $userId)->where('friend_id', $helper->user_id);
                        })->orWhere(function ($q) use ($helper, $userId) {
                            $q->where('user_id', $helper->user_id)->where('friend_id', $userId);
                        })->first();
                    }

                    return [
                        'id' => $helper->user->id ?? null,
                        'name' => $helper->user->name ?? null,
                        'profile_image' => $helper->user && $helper->user->profile_image ? url($helper->user->profile_image) : null,
                        'is_chat' => $thread ? true : false,
                        'thread_id' => $thread ? $thread->id : null,
                        'status' => $helper->status ?? 'accepted',
                    ];
                })->filter()->values(),
                'help_images' => $help->helpImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_path' => url($image->image_path),
                        'thumbnail_path' => $image->thumbnail_path ? url($image->thumbnail_path) : null,
                        'type' => $image->type,
                    ];
                }),
                'created_at' => $help->created_at,
            ];
        });

        return ResponseHelper::send(200, 'My help requests retrieved.', $filterHelps);
    }

    public function getReceivedHelpRequests(Request $request)
    {
        $this->directValidation([
            'help_id' => ['nullable', 'exists:helps,id'],
        ]);

        $user = $request->user();
        $userId = $user->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        // Get help requests created by user that have at least one pending helper offer
        $query = Help::where('user_id', $userId)
            ->whereHas('helpers', function ($q) {
                $q->where('status', 'pending');
            })
            ->with([
                'helpImages',
                'user',
                'helpers' => function ($q) {
                    $q->where('status', 'pending')->with('user');
                }
            ]);

        if ($request->filled('help_id')) {
            $query->where('id', $request->help_id);
        }

        $helps = $query->latest()
            ->skip($offset)
            ->take($limit)
            ->get();

        $response = $helps->map(function ($help) use ($userId) {
            return [
                'user' => [
                    'id' => $help->user->id ?? null,
                    'name' => $help->user->name ?? null,
                    'profile_image' => $help->user && $help->user->profile_image ? url($help->user->profile_image) : null,
                    'is_verified' => $help->user->is_verified ?? false,
                ],
                'id' => $help->id,
                'title' => $help->title,
                'description' => $help->description,
                'location' => $help->location,
                'latitude' => $help->latitude,
                'longitude' => $help->longitude,
                'urgency' => $help->urgency,
                'date' => $help->date,
                'time' => $help->time,
                'status' => $help->status,
                'is_owner' => true,
                'like_count' => $help->like_count,
                'comment_count' => $help->comment_count,
                'helpers' => $help->helpers->map(function ($helper) use ($userId) {
                    $thread = Thread::where(function ($q) use ($helper, $userId) {
                        $q->where('user_id', $userId)->where('friend_id', $helper->user_id);
                    })->orWhere(function ($q) use ($helper, $userId) {
                        $q->where('user_id', $helper->user_id)->where('friend_id', $userId);
                    })->first();

                    return [
                        'id' => $helper->user->id ?? null,
                        'name' => $helper->user->name ?? null,
                        'profile_image' => $helper->user && $helper->user->profile_image ? url($helper->user->profile_image) : null,
                        'is_chat' => $thread ? true : false,
                        'thread_id' => $thread ? $thread->id : null,
                        'status' => $helper->status ?? 'pending',
                    ];
                })->filter()->values(),
                'help_images' => $help->helpImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_path' => url($image->image_path),
                        'thumbnail_path' => $image->thumbnail_path ? url($image->thumbnail_path) : null,
                        'type' => $image->type,
                    ];
                }),
                'created_at' => $help->created_at,
            ];
        });

        return ResponseHelper::send(200, 'Received help requests retrieved.', $response);
    }

    public function getHelpDetail(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
        ]);
        $user = $request->user();
        $userId = $user ? $user->id : null;

        $help = Help::with([
            'helpers' => function ($q) {
                $q->where('status', 'accepted')->with('user:id,name,profile_image');
            }
        ])
        ->withCount(['helpers' => function ($q) {
            $q->where('status', 'accepted');
        }])
        ->findOrFail($request->help_id);

        $isLike = $user
        ? HelpLike::where('user_id', $userId)
            ->where('help_id', $help->id)
            ->exists()
        : false;

        $bookmarkedHelp = $user
            ? BookmarkHelp::where('user_id', $userId)
                ->where('help_id', $help->id)
                ->exists()
            : false;

        $threadForCreator = null;
        if ($userId) {
            $threadForCreator = Thread::where(function ($q) use ($help, $userId) {
                $q->where('user_id', $userId)->where('friend_id', $help->user_id);
            })->orWhere(function ($q) use ($help, $userId) {
                $q->where('user_id', $help->user_id)->where('friend_id', $userId);
            })->first();
        }

        $helpFilter = [
            'user' => [
                'id' => $help->user->id,
                'name' => $help->user->name,
                'profile_image' => $help->user->profile_image ? url($help->user->profile_image) : null,
                'is_verified' => $help->user->is_verified,
                'is_chat' => $threadForCreator ? true : false,
                'thread_id' => $threadForCreator ? $threadForCreator->id : null,
            ],
            'id' => $help->id,
            'title' => $help->title,
            'description' => $help->description,
            'location' => $help->location,
            'latitude' => $help->latitude,
            'longitude' => $help->longitude,
            'urgency' => $help->urgency,
            'date' => $help->date,
            'time' => $help->time,
            'status' => $help->status,
            'is_bookmark' => $bookmarkedHelp,
            'is_like' => $isLike,
            'is_owner' => $userId ? ($help->user_id === $userId) : false,
            'helpers_count' => $help->helpers_count,
            'like_count' => $help->like_count,
            'comment_count' => $help->comment_count,
            'helpers' => $help->helpers->map(function ($helper) use ($userId) {
                $thread = null;
                if ($userId) {
                    $thread = Thread::where(function ($q) use ($helper, $userId) {
                        $q->where('user_id', $userId)->where('friend_id', $helper->user_id);
                    })->orWhere(function ($q) use ($helper, $userId) {
                        $q->where('user_id', $helper->user_id)->where('friend_id', $userId);
                    })->first();
                }

                return [
                    'id' => $helper->user->id ?? null,
                    'name' => $helper->user->name ?? null,
                    'profile_image' => $helper->user && $helper->user->profile_image ? url($helper->user->profile_image) : null,
                    'is_chat' => $thread ? true : false,
                    'thread_id' => $thread ? $thread->id : null,
                    'status' => $helper->status ?? 'accepted',
                ];
            })->filter()->values(),
            'created_at' => $help->created_at,
        ];

        return ResponseHelper::send(
            200,
            'Help detail retrieved',
            $helpFilter
        );
    }

    public function oldICanHelp(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
            'message' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $userId = $user->id;

        $help = Help::with('user')->findOrFail($request->help_id);

        $messageText = $request->message ?? __('api.default_help_offer_message');

        HelpUser::updateOrCreate([
            'help_id' => $request->help_id,
            'user_id' => $userId,
        ], [
            'status' => 'accepted', // Auto-accepted for backward compatibility
            'message' => $messageText,
        ]);

        // Ensure chat thread exists
        $thread = Thread::where(function ($q) use ($help, $userId) {
            $q->where('user_id', $userId)->where('friend_id', $help->user_id);
        })->orWhere(function ($q) use ($help, $userId) {
            $q->where('user_id', $help->user_id)->where('friend_id', $userId);
        })->first();

        if (!$thread) {
            $thread = Thread::create([
                'user_id' => $userId,
                'friend_id' => $help->user_id,
                'type' => 'approved',
                'last_msg_update_time' => now(),
            ]);
        } else {
            $thread->update(['type' => 'approved']);
        }

        // Auto-create initial message in thread
        ThreadMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $userId,
            'receiver_id' => $help->user_id,
            'message' => $messageText,
            'type' => 'message',
            'status' => 'unread',
            'delivery_status' => 'sent',
        ]);

        $thread->update([
            'last_msg_update_time' => now(),
        ]);

        // Send Push Notification
        $payload = [
            'from_user_id' => $userId,
            'push_type' => 9, // Chat/Message type
            'push_message' => $user->name . ' wants to help you.',
            'push_title' => 'Offer to Help',
            'object_id' => $thread ? $thread->id : null,
            'object_type' => 'chat',
            'extra' => [
                'thread_id' => $thread ? $thread->id : null,
                'user_id' => $userId,
                'help_id' => $help->id,
                'sender_name' => $user->name,
                'profile_image' => $user->profile_image ? url($user->profile_image) : null,
            ],
        ];

        send_push($help->user_id, $payload, true);

        return ResponseHelper::send(200, 'You can help request sent');
    }

    public function iCanHelp(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
            'message' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $userId = $user->id;

        $help = Help::with('user')->findOrFail($request->help_id);

        if ($help->user_id === $userId) {
            return ResponseHelper::send(400, 'You cannot offer help for your own help request.');
        }

        // Add helper request with status 'pending'
        HelpUser::updateOrCreate([
            'help_id' => $request->help_id,
            'user_id' => $userId,
        ], [
            'status' => 'pending',
        ]);

        // Ensure chat thread exists between help creator and helper, and is pending
        $thread = Thread::where(function ($q) use ($help, $userId) {
            $q->where('user_id', $userId)->where('friend_id', $help->user_id);
        })->orWhere(function ($q) use ($help, $userId) {
            $q->where('user_id', $help->user_id)->where('friend_id', $userId);
        })->first();

        if (!$thread) {
            $thread = Thread::create([
                'user_id' => $userId,
                'friend_id' => $help->user_id,
                'type' => 'pending',
                'last_msg_update_time' => now(),
            ]);
        } else {
            if ($thread->type === 'rejected') {
                $thread->update(['type' => 'pending']);
            }
        }

        // Send Push Notification (Offer to Help)
        $payload = [
            'from_user_id' => $userId,
            'push_type' => 9,
            'push_message' => $user->name . ' wants to help you.',
            'push_title' => 'Offer to Help',
            'object_id' => $help->id,
            'object_type' => 'help',
            'extra' => [
                'thread_id' => $thread->id,
                'user_id' => $userId,
                'help_id' => $help->id,
                'sender_name' => $user->name,
                'profile_image' => $user->profile_image ? url($user->profile_image) : null,
            ],
        ];

        send_push($help->user_id, $payload, false);

        return ResponseHelper::send(200, 'You can help request sent');
    }

    public function acceptDeclineHelpRequest(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
            'helper_id' => ['required', 'exists:users,id'],
            'action' => ['required', 'in:accept,decline'],
        ]);

        $user = $request->user();
        $help = Help::findOrFail($request->help_id);

        // Ensure the viewer is the creator of the help request
        if ($help->user_id !== $user->id) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        $helpUser = HelpUser::with('user')
            ->where('help_id', $help->id)
            ->where('user_id', $request->helper_id)
            ->first();

        if (!$helpUser) {
            return ResponseHelper::send(404, 'Help request from this user not found.');
        }

        if ($request->action === 'accept') {
            $helpUser->update([
                'status' => 'accepted',
            ]);

            // Ensure chat thread exists between help creator and helper, and is approved
            $thread = Thread::where(function ($q) use ($help, $request) {
                $q->where('user_id', $request->helper_id)->where('friend_id', $help->user_id);
            })->orWhere(function ($q) use ($help, $request) {
                $q->where('user_id', $help->user_id)->where('friend_id', $request->helper_id);
            })->first();

            if (!$thread) {
                $thread = Thread::create([
                    'user_id' => $help->user_id,
                    'friend_id' => $request->helper_id,
                    'type' => 'approved',
                    'last_msg_update_time' => now(),
                ]);
            } else {
                $thread->update([
                    'type' => 'approved',
                ]);
            }

            // Auto-create initial message in thread representing the volunteer's offer text
            $messageExists = ThreadMessage::where('thread_id', $thread->id)
                ->where('sender_id', $request->helper_id)
                ->where('message', $helpUser->message ?? __('api.default_help_offer_message'))
                ->exists();

            if (!$messageExists) {
                ThreadMessage::create([
                    'thread_id' => $thread->id,
                    'sender_id' => $request->helper_id,
                    'receiver_id' => $help->user_id,
                    'message' => $helpUser->message ?? __('api.default_help_offer_message'),
                    'type' => 'message',
                    'status' => 'unread',
                    'delivery_status' => 'sent',
                ]);

                $thread->update([
                    'last_msg_update_time' => now(),
                ]);
            }

            // Send push notification to helper notifying they've been accepted
            $payload = [
                'from_user_id' => $user->id,
                'push_type' => 10,
                'push_message' => $user->name . ' accepted your offer to help.',
                'push_title' => 'Offer Accepted',
                'object_id' => $thread->id,
                'object_type' => 'chat',
                'extra' => [
                    'thread_id' => $thread->id,
                    'user_id' => $user->id,
                    'help_id' => $help->id,
                    'sender_name' => $user->name,
                    'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                ],
            ];
            send_push($request->helper_id, $payload, false);

            $helperUser = $helpUser->user;

            return ResponseHelper::send(200, 'Help request accepted successfully.', [
                'id' => $helperUser->id ?? null,
                'name' => $helperUser->name ?? null,
                'profile_image' => $helperUser && $helperUser->profile_image ? url($helperUser->profile_image) : null,
                'is_verified' => $helperUser->is_verified ?? false,
                'is_chat' => true,
                'thread_type' => $thread->type,
                'thread_id' => $thread->id,
            ]);
        } else {
            // decline -> delete the entry
            $helpUser->delete();

            // Find existing thread and reject/decline it
            $thread = Thread::where(function ($q) use ($help, $request) {
                $q->where('user_id', $request->helper_id)->where('friend_id', $help->user_id);
            })->orWhere(function ($q) use ($help, $request) {
                $q->where('user_id', $help->user_id)->where('friend_id', $request->helper_id);
            })->first();

            // if ($thread && $thread->type === 'pending') {
            //     $thread->update([
            //         'type' => 'rejected',
            //     ]);
            // }

            // Send push notification to helper notifying they've been declined
            $payload = [
                'from_user_id' => $user->id,
                'push_type' => 9,
                'push_message' => $user->name . ' declined your offer to help.',
                'push_title' => 'Offer Declined',
                'object_id' => $help->id,
                'object_type' => 'help',
                'extra' => [
                    'user_id' => $user->id,
                    'help_id' => $help->id,
                    'sender_name' => $user->name,
                    'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                ],
            ];
            send_push($request->helper_id, $payload, false);

            return ResponseHelper::send(200, 'Help request declined and entry removed.');
        }
    }

    public function deleteHelp(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
        ]);

        $help = Help::findOrFail($request->help_id);
        if ($help->user_id !== $request->user()->id) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        foreach ($help->helpImages as $image) {
            un_link_file($image->image_path);
            un_link_file($image->thumbnail_path);
            $image->delete();
        }

        $help->delete();

        return ResponseHelper::send(200, 'Help request deleted successfully');
    }

    public function markHelpAsCompleted(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        DB::transaction(function () use ($request) {

            $help = Help::findOrFail($request->help_id);
            $help->update([
                'status' => 'completed',
            ]);

            User::whereIn('id', $request->user_ids)
                ->increment('nowis_points', 20);

        });

        return ResponseHelper::send(
            200,
            'Help marked as completed and points added successfully.'
        );
    }

    public function likeHelp(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
        ]);

        $help = Help::findOrFail($request->help_id);
        $userId = $request->user()->id;
        $user = $request->user();

        if ($help->likes()->where('user_id', $userId)->exists()) {
            // Unlike the help
            $help->likes()->where('user_id', $userId)->delete();
            $help->decrement('like_count');
            if ($help->like_count < 0) {
                $help->like_count = 0;
            }
            $message = __('api.success_help_unliked');
        } else {
            // Like the help
            $help->likes()->create(['user_id' => $userId, 'help_id' => $help->id]);
            $help->increment('like_count');
            $message = __('api.success_help_liked');
            
            $payload = array(
                'from_user_id' =>$userId,
                'push_type' => 6,
                'push_message' => $user->name . ' ' . 'liked your help request.',
                'push_title' => 'New Like',
                'object_id' => $help->id,
                'object_type' => null,
                'extra' => [
                    'help_id' => $help->id,
                    'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                ],
            );

            send_push($help->user_id, $payload, true);
        }

        return ResponseHelper::send(200, $message, [
            'likes_count' => $help->like_count,
        ]);
    }

    public function likeList(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
            'search' => ['nullable', 'string'],
        ]);

        $help = Help::findOrFail($request->help_id);

        $query = $help->likes()->with('user');

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
            'like_count' => $help->like_count,
            'likes' => $likes
        ]);
    }

    public function addComment(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
            'comment' => ['required', 'string'],
            'parent_id' => ['nullable', 'exists:help_comments,id'],
        ]);

        $help = Help::findOrFail($request->help_id);
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

        if ($isFlagged) {
            return ResponseHelper::send(422, __('We detected inappropriate language in your comment, Please try again.'));
        }

        $comment = $help->comments()->create(array_merge([
            'user_id' => $userId,
            'help_id' => $help->id,
            'comment' => $request->comment,
            'parent_id' => $request->parent_id,
        ], $moderationData));

        $user = $request->user();
        if ($help->user_id !== $userId) {
            $payload = array(
                'from_user_id' => $userId,
                'push_type' => 7,
                'push_message' => $user->name . ' commented on your help request.',
                'push_title' => 'New Comment',
                'object_id' => $help->id,
                'object_type' => null,
                'extra' => [
                    'help_id' => $help->id,
                    'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                ],
            );
            send_push($help->user_id, $payload, true);
        }

        $help->increment('comment_count');

        return ResponseHelper::send(200, __('api.success_comment_added'), [
            'comment' => $comment,
        ]);
    }

    public function commentList(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
            'filter' => ['nullable', 'in:for_you,newest,all'],
        ]);

        $help = Help::findOrFail($request->help_id);
        $query = $help->comments()
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

    public function bookmarkHelp(Request $request)
    {
        $this->directValidation([
            'help_id' => ['required', 'exists:helps,id'],
        ]);

        $help = Help::findOrFail($request->help_id);
        $userId = $request->user()->id;

        if ($help->bookmarks()->where('user_id', $userId)->exists()) {
            // Unlike the help
            $help->bookmarks()->where('user_id', $userId)->delete();

            $message = __('api.success_help_unbookmarked');
        } else {
            // Like the help
            $help->bookmarks()->create(['user_id' => $userId, 'help_id' => $help->id]);

            $message = __('api.success_help_bookmarked');
        }

        return ResponseHelper::send(200, $message);
    }

    public function bookmarkList(Request $request)
    {
        $user = $request->user();
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $userLat = $request->header('latitude') ?? $user->latitude ?? null;
        $userLng = $request->header('longitude') ?? $user->longitude ?? null;

        $query = BookmarkHelp::with([
            'help' => function ($q) {
                $q->withCount('helpers');
            },
            'help.user',
            'help.helpImages',
        ])
        ->where('bookmark_helps.user_id', $userId)
        ->whereHas('help.user');

        // 📍 Distance calculation (if location available)
            if ($userLat && $userLng) {
                $query->join('helps', 'helps.id', '=', 'bookmark_helps.help_id')
                    ->selectRaw("
                        bookmark_helps.*,
                        (6371 * acos(
                            cos(radians(?)) * cos(radians(helps.latitude)) *
                            cos(radians(helps.longitude) - radians(?)) +
                            sin(radians(?)) * sin(radians(helps.latitude))
                        )) AS distance
                    ", [$userLat, $userLng, $userLat])
                    ->orderBy('distance', 'asc');
            } else {
                $query->latest();
            }

        $bookmarkedHelps = $query
            ->skip($offset)
            ->take($limit)
            ->get();

        $helpIds = $bookmarkedHelps->pluck('help.id')->filter()->toArray();

        $favoriteHelpIds = HelpLike::where('user_id', $userId)
            ->whereIn('help_id', $helpIds)
            ->pluck('help_id')
            ->toArray();

        $response = $bookmarkedHelps->map(function ($bookmark) use ($favoriteHelpIds, $userId) {
            $help = $bookmark->help;

            if (!$help || !$help->user) {
                return null;
            }

            $thread = null;
            if ($userId) {
                $thread = Thread::where(function ($q) use ($help) {
                        $q->where('user_id', $help->user_id)
                        ->orWhere('friend_id', $help->user_id);
                    })
                    ->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)
                        ->orWhere('friend_id', $userId);
                    })
                    ->first();
            }

            return [
                'user' => [
                    'id' => $help->user->id ?? null,
                    'name' => $help->user->name ?? null,
                    'profile_image' => $help->user->profile_image ? url($help->user->profile_image) : null,
                    'is_verified' => $help->user->is_verified ?? false,
                    'is_chat' => $thread ? true : false,
                    'thread_type' => $thread ? $thread->type : null,
                    'thread_id' => $thread ? $thread->id : null,
                ],
                'id' => $help->id,
                'title' => $help->title,
                'description' => $help->description,
                'location' => $help->location,
                'latitude' => $help->latitude,
                'longitude' => $help->longitude,
                'urgency' => $help->urgency,
                'helpers_count' => $help->helpers_count ?? 0,
                'is_bookmark' => true,
                'is_like' => in_array($help->id, $favoriteHelpIds),
                'likes_count' => $help->like_count ?? 0,
                'comments_count' => $help->comment_count ?? 0,
                'distance' => isset($bookmark->distance)
                    ? round($bookmark->distance, 2)
                    : null,
                'post_images' => $help->helpImages->map(function ($img) {
                    return [
                        'id' => $img->id,
                        'image_path' => $img->image_path ? url($img->image_path) : null,
                        'thumbnail_path' => $img->thumbnail_path ? url($img->thumbnail_path) : null,
                    ];
                })->toArray(),
                'created_at' => $help->created_at,
                'updated_at' => $help->updated_at,
            ];
        })->filter()->values();

        return ResponseHelper::send(200, __('api.success'), $response);
    }

    public function deleteComment(Request $request)
    {
        $this->directValidation([
            'comment_id' => ['required', 'exists:help_comments,id'],
        ]);

        $comment = HelpComment::findOrFail($request->comment_id);
        $userId = $request->user()->id;

        if ($comment->user_id !== $userId) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        DB::transaction(function () use ($comment) {
            // Decrement comment count for the help
            $help = Help::find($comment->help_id);

            $comment->delete();

            // Decrease comment count safely
            if ($help && $help->comment_count > 0) {
                $help->decrement('comment_count');
            }
        });

        return ResponseHelper::send(200, __('api.success_comment_deleted'));
    }


    // public function getCommonFeed(Request $request)
    // {
    //     $user = $request->user();
    //     $userId = $user?->id;

    //     $limit  = (int) ($request->limit ?? 10);
    //     $offset = (int) ($request->offset ?? 0);
    //     $search = $request->search;
    //     $type   = $request->type;

    //     /*
    //     |--------------------------------------------------------------------------
    //     | POSTS QUERY
    //     |--------------------------------------------------------------------------
    //     */
    //     if (!$type || $type === 'post') {
    //         $postsQuery = Post::with(['postImages', 'user'])
    //             ->where('status', 'active')
    //             ->whereNotIn('moderation_status', ['flagged', 'rejected'])
    //             ->whereHas('user');

    //         // 🔍 Post Search
    //         if (!empty($search)) {
    //             $postsQuery->where(function ($q) use ($search) {
    //                 $q->where('title', 'like', "%{$search}%")
    //                 ->orWhere('description', 'like', "%{$search}%")
    //                 ->orWhere('city', 'like', "%{$search}%");
    //             });
    //         }

    //         $posts = $postsQuery->get();

    //         $postIds = $posts->pluck('id')->toArray();

    //         $favoritePostsIds = $user
    //             ? PostLike::where('user_id', $userId)
    //                 ->whereIn('post_id', $postIds)
    //                 ->pluck('post_id')
    //                 ->toArray()
    //             : [];

    //         $bookmarkedPostIds = $user
    //             ? BookmarkPost::where('user_id', $userId)
    //                 ->whereIn('post_id', $postIds)
    //                 ->pluck('post_id')
    //                 ->toArray()
    //             : [];

    //         $postData = $posts->map(function ($post) use ($favoritePostsIds, $bookmarkedPostIds, $user) {

    //             return [
    //                 'type' => 'post',
    //                 'user' => [
    //                     'id' => $post->user->id,
    //                     'name' => $post->user->name,
    //                     'profile_image' => $post->user->profile_image ? url($post->user->profile_image) : null,
    //                     'is_verified' => $post->user->is_verified,
    //                 ],
    //                 'id' => $post->id,
    //                 'title' => $post->title,
    //                 'description' => $post->description,
    //                 'city' => $post->city,
    //                 'is_bookmark' => $user ? in_array($post->id, $bookmarkedPostIds) : false,
    //                 'is_like' => $user ? in_array($post->id, $favoritePostsIds) : false,
    //                 'likes_count' => $post->like_count,
    //                 'comments_count' => $post->comment_count,
    //                 'post_images' => $post->postImages->map(function ($img) {
    //                     return [
    //                         'id' => $img->id,
    //                         'image_path' => $img->image_path ? url($img->image_path) : null,
    //                         'thumbnail_path' => $img->thumbnail_path ? url($img->thumbnail_path) : null,
    //                     ];
    //                 }),
    //                 'is_my_post' => $user ? ($post->user_id === $user->id) : false,
    //                 'created_at' => $post->created_at,
    //                 'updated_at' => $post->updated_at,
    //             ];
    //         });
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | HELPS QUERY
    //     |--------------------------------------------------------------------------
    //     */
    //     $userLat = $request->header('latitude') ?? ($user ? $user->latitude : null);
    //     $userLng = $request->header('longitude') ?? ($user ? $user->longitude : null);
    //     if (!$type || $type === 'help') {
    //         $helpsQuery = Help::with(['helpImages', 'user'])
    //             ->where('status', 'pending')
    //             ->whereHas('user')
    //             ->withCount('helpers');

    //         // 🔍 Help Search
    //         if (!empty($search)) {
    //             $helpsQuery->where(function ($q) use ($search) {
    //                 $q->where('title', 'like', "%{$search}%")
    //                 ->orWhere('description', 'like', "%{$search}%")
    //                 ->orWhere('location', 'like', "%{$search}%");
    //             });
    //         }
    //         if ($userId) {
    //             $helpsQuery->where('user_id', '!=', $userId);
    //         }

    //         if ($userLat && $userLng) {
    //             $helpsQuery->selectRaw("
    //                 helps.*,
    //                 (6371 * acos(
    //                     cos(radians(?)) * cos(radians(latitude)) *
    //                     cos(radians(longitude) - radians(?)) +
    //                     sin(radians(?)) * sin(radians(latitude))
    //                 )) AS distance
    //             ", [$userLat, $userLng, $userLat])
    //             ->orderByRaw("
    //                 CASE urgency
    //                     WHEN 'urgent' THEN 1
    //                     WHEN 'time_based' THEN 2
    //                     WHEN 'flexible' THEN 3
    //                     ELSE 4
    //                 END
    //             ")
    //             ->orderBy('distance', 'asc'); // nearest first
    //         }

    //         $helps = $helpsQuery->get();

    //         $helpIds = $helps->pluck('id')->toArray();

    //         $favoriteHelpIds = $user
    //             ? HelpLike::where('user_id', $userId)
    //                 ->whereIn('help_id', $helpIds)
    //                 ->pluck('help_id')
    //                 ->toArray()
    //             : [];

    //         $bookmarkedHelpIds = $user
    //             ? BookmarkHelp::where('user_id', $userId)
    //                 ->whereIn('help_id', $helpIds)
    //                 ->pluck('help_id')
    //                 ->toArray()
    //             : [];

    //         $helpData = $helps->map(function ($help) use ($favoriteHelpIds, $bookmarkedHelpIds, $user, $userId) {

    //             $thread = null;

    //             if ($userId) {
    //                 $thread = Thread::where(function ($q) use ($help) {
    //                         $q->where('user_id', $help->user_id)
    //                         ->orWhere('friend_id', $help->user_id);
    //                     })
    //                     ->where(function ($q) use ($userId) {
    //                         $q->where('user_id', $userId)
    //                         ->orWhere('friend_id', $userId);
    //                     })
    //                     ->first();
    //             }

    //             return [
    //                 'type' => 'help',
    //                 'user' => [
    //                     'id' => $help->user->id,
    //                     'name' => $help->user->name,
    //                     'profile_image' => $help->user->profile_image ? url($help->user->profile_image) : null,
    //                     'is_verified' => $help->user->is_verified,
    //                     'is_chat' => $thread ? true : false,
    //                     'thread_type' => $thread ? $thread->type : null,
    //                     'thread_id' => $thread ? $thread->id : null,
    //                 ],
    //                 'id' => $help->id,
    //                 'title' => $help->title,
    //                 'description' => $help->description,
    //                 'location' => $help->location,
    //                 'latitude' => $help->latitude,
    //                 'longitude' => $help->longitude,
    //                 'urgency' => $help->urgency,
    //                 'date' => $help->date,
    //                 'time' => $help->time,
    //                 'status' => $help->status,
    //                 'is_bookmark' => $user ? in_array($help->id, $bookmarkedHelpIds) : false,
    //                 'is_like' => $user ? in_array($help->id, $favoriteHelpIds) : false,
    //                 'helpers_count' => $help->helpers_count,
    //                 'like_count' => $help->like_count,
    //                 'comment_count' => $help->comment_count,
    //                 'i_can_help' => isset($help->i_can_help) ? $help->i_can_help : false,
    //                 'distance' =>  isset($help->distance) ? round($help->distance, 2) : null,
    //                 'help_images' => $help->helpImages->map(function ($image) {
    //                     return [
    //                         'id' => $image->id,
    //                         'image_path' => url($image->image_path),
    //                         'thumbnail_path' => $image->thumbnail_path ? url($image->thumbnail_path) : null,
    //                         'type' => $image->type,
    //                     ];
    //                 }),
    //                 'created_at' => $help->created_at,
    //             ];
    //         });
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | MERGE + SORT + PAGINATE
    //     |--------------------------------------------------------------------------
    //     */
    //     $feed = collect()
    //     ->merge($postData ?? [])
    //     ->merge($helpData ?? [])
    //     ->sortByDesc('created_at')
    //     ->values();

    //     $total = $feed->count();
    //     $feed  = $feed->slice($offset, $limit)->values();

    //     return ResponseHelper::send(200, 'Feed retrieved successfully', $feed);
    // }



    public function getCommonFeed(Request $request)
    {
        $user = $request->user();
        $userId = $user?->id;

        $limit  = (int) ($request->limit ?? 10);
        $offset = (int) ($request->offset ?? 0);
        $search = $request->search;
        $type   = $request->type;

        $isRelevant = $type === 'relevant';

        $userLat = $request->header('latitude') ?? ($user?->latitude);
        $userLng = $request->header('longitude') ?? ($user?->longitude);

        $postData = collect();
        $helpData = collect();

        /*
        |--------------------------------------------------------------------------
        | POSTS
        |--------------------------------------------------------------------------
        */
        if (!$type || $type === 'post' || $isRelevant) {

            $postsQuery = Post::with(['postImages', 'user'])
                ->where('status', 'active')
                ->whereNotIn('moderation_status', ['flagged', 'rejected'])
                ->whereHas('user');

            if (!empty($search)) {
                $postsQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
                });
            }

            $posts = $postsQuery->get();

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

            $postData = $posts->map(function ($post) use ($isRelevant, $favoritePostsIds, $bookmarkedPostIds, $user) {

                // 🔥 Balanced scoring
                $recency = max(0, 72 - now()->diffInHours($post->created_at)); // 3 day window
                $engagement = ($post->like_count * 2) + ($post->comment_count * 3);

                $relevanceScore = ($recency * 1.2) + ($engagement * 1.5);

                return [
                    'type' => 'post',
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
                    'relevance_score' => $isRelevant ? $relevanceScore : null,
                ];
            });
        }

        /*
        |--------------------------------------------------------------------------
        | HELPS
        |--------------------------------------------------------------------------
        */
        if (!$type || $type === 'help' || $isRelevant) {

            $helpsQuery = Help::with(['helpImages', 'user'])
                ->where('status', 'pending')
                ->whereHas('user')
                ->withCount('helpers')
                ->withExists([
                'helpers as i_can_help' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ]);

            if (!empty($search)) {
                $helpsQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
                });
            }

            // if ($userId) {
            //     $helpsQuery->where('user_id', '!=', $userId);
            // }

            // Distance only for relevant
            if ($userLat && $userLng) {
                $helpsQuery->selectRaw("
                    helps.*,
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )) AS distance
                ", [$userLat, $userLng, $userLat]);
            }

            $helps = $helpsQuery->get();

            $helpIds = $helps->pluck('id')->toArray();

            $favoriteHelpIds = $user
                ? HelpLike::where('user_id', $userId)
                    ->whereIn('help_id', $helpIds)
                    ->pluck('help_id')
                    ->toArray()
                : [];

            $bookmarkedHelpIds = $user
                ? BookmarkHelp::where('user_id', $userId)
                    ->whereIn('help_id', $helpIds)
                    ->pluck('help_id')
                    ->toArray()
                : [];


            $helpData = $helps->map(function ($help) use ($isRelevant, $favoriteHelpIds, $bookmarkedHelpIds, $user, $userId) {


                    $thread = null;

                    if ($userId) {
                        $thread = Thread::where(function ($q) use ($help) {
                                $q->where('user_id', $help->user_id)
                                ->orWhere('friend_id', $help->user_id);
                            })
                            ->where(function ($q) use ($userId) {
                                $q->where('user_id', $userId)
                                ->orWhere('friend_id', $userId);
                            })
                            ->first();
                    }

                $distance = isset($help->distance)
                    ? max(0, 30 - $help->distance)  // LOW weight
                    : 0;

                $recency = max(0, 72 - now()->diffInHours($help->created_at));
                $engagement = ($help->like_count * 2) + ($help->comment_count * 3);

                $relevanceScore =
                    ($distance * 0.6) +      // low priority
                    ($recency * 1.2) +
                    ($engagement * 1.5);

                return [
                    'type' => 'help',
                    'user' => [
                        'id' => $help->user->id,
                        'name' => $help->user->name,
                        'profile_image' => $help->user->profile_image ? url($help->user->profile_image) : null,
                        'is_verified' => $help->user->is_verified,
                        'is_chat' => $thread ? true : false,
                        'thread_type' => $thread ? $thread->type : null,
                        'thread_id' => $thread ? $thread->id : null,
                    ],
                    'id' => $help->id,
                    'title' => $help->title,
                    'description' => $help->description,
                    'location' => $help->location,
                    'latitude' => $help->latitude,
                    'longitude' => $help->longitude,
                    'urgency' => $help->urgency,
                    'date' => $help->date,
                    'time' => $help->time,
                    'status' => $help->status,
                    'is_bookmark' => $user ? in_array($help->id, $bookmarkedHelpIds) : false,
                    'is_like' => $user ? in_array($help->id, $favoriteHelpIds) : false,
                    'is_owner' => $userId ? ($help->user_id === $userId) : false,
                    'helpers_count' => $help->helpers_count,
                    'like_count' => $help->like_count,
                    'comment_count' => $help->comment_count,
                    'i_can_help' => $help->i_can_help,
                    'distance' => isset($help->distance) ? round($help->distance, 2) : null,
                    'help_images' => $help->helpImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => $image->image_path ? url($image->image_path) : null,
                            'thumbnail_path' => $image->thumbnail_path ? url($image->thumbnail_path) : null,
                            'type' => $image->type,
                        ];
                    }),
                    'created_at' => $help->created_at,
                    'relevance_score' => $isRelevant ? $relevanceScore : null,
                ];
            });
        }

        /*
        |--------------------------------------------------------------------------
        | MERGE + SORT
        |--------------------------------------------------------------------------
        */
        $feed = collect()
            ->merge($postData)
            ->merge($helpData);

        if ($isRelevant) {
            $feed = $feed->sortByDesc('relevance_score');
        } else {
            $feed = $feed->sortByDesc('created_at');
        }

        $newHelpCount = 0;
        if ($userId) {
            $newHelpCountQuery = Help::where('status', 'pending')
                ->where('user_id', '!=', $userId);

            if ($user->help_last_seen_at) {
                $newHelpCountQuery->where('created_at', '>', $user->help_last_seen_at);
            }

            $newHelpCount = $newHelpCountQuery->count();

            if ($type === 'help') {
                $user->update(['help_last_seen_at' => now()]);
            }
        }

        $feed = $feed->values();
        $feed = $feed->slice($offset, $limit)->values();

        return ResponseHelper::send(200, 'Feed retrieved successfully', $feed, ['new_help_count' => $newHelpCount]);
    }
}

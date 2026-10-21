<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryQuestionResponse;
use App\Models\StoryReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StoryController extends ResponseController
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Fetch active public stories, including only the authenticated user's views for efficiency
        $stories = Story::active()
            ->with([
                'user:id,name,username,profile_image',
                'views' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'pollOptions.votes',
                'pollVotes' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }
            ])
            ->where('privacy', 'public')
            ->orderBy('created_at', 'asc') // Get oldest stories first so user plays them in chronological order
            ->get()
            ->groupBy('user_id');

        $data = [];

        foreach ($stories as $userId => $userStories) {
            $storyUser = $userStories->first()->user;
            
            // Current user has seen all stories from this user if every story has a view record
            $allSeen = $userStories->every(function ($story) {
                return $story->views->isNotEmpty();
            });

            // Since we sorted by created_at asc, the last story in the collection is the latest one
            $latestStoryTime = $userStories->last()->created_at;

            $storyList = $userStories->map(function ($story) use ($user) {
                return [
                    'id' => $story->id,
                    'type' => $story->type,
                    'media_path' => $story->media_path,
                    'hls_path' => $story->hls_path,
                    'hls_status' => $story->hls_status,
                    'thumbnail_path' => $story->thumbnail_path,
                    'text_content' => $story->text_content,
                    'background_color' => $story->background_color,
                    'text_color' => $story->text_color,
                    'metadata' => $story->metadata,
                    'location' => $story->location,
                    'latitude' => $story->latitude,
                    'longitude' => $story->longitude,
                    'count_down' => $story->count_down,
                    'link' => $story->link,
                    'question' => $story->question,
                    'poll_question' => $story->poll_question,
                    'created_at' => $story->created_at,
                    'expires_at' => $story->expires_at,
                    'is_seen' => $story->views->isNotEmpty(),
                    'is_owner' => $story->user_id === $user->id,
                    'poll' => $story->pollOptions->isNotEmpty() ? [
                        'options' => $story->pollOptions->map(function ($option) use ($user) {
                            return [
                                'id' => $option->id,
                                'option_text' => $option->option_text,
                                'votes_count' => $option->votes->count(),
                                'is_voted' => $option->votes->contains('user_id', $user->id),
                            ];
                        }),
                        'total_votes' => $story->pollOptions->sum(function ($option) {
                            return $option->votes->count();
                        }),
                        'voted_option_id' => $story->pollVotes->where('user_id', $user->id)->first()?->poll_option_id,
                    ] : null,
                ];
            })->values()->toArray();

            $data[] = [
                'user' => $storyUser,
                'is_owner' => $storyUser->id === $user->id,
                'all_seen' => $allSeen,
                'latest_story_time' => $latestStoryTime,
                'stories' => $storyList,
            ];
        }

        // Sort the data:
        // 1. Authenticated user's own stories first.
        // 2. Unseen stories next (sorted by latest story createdAt DESC).
        // 3. Seen stories last (sorted by latest story createdAt DESC).
        usort($data, function ($a, $b) {
            // Own stories have absolute priority
            if ($a['is_owner']) return -1;
            if ($b['is_owner']) return 1;

            // Unseen stories come before seen stories
            if (!$a['all_seen'] && $b['all_seen']) return -1;
            if ($a['all_seen'] && !$b['all_seen']) return 1;

            // Within the same group (both seen or both unseen), sort by latest story time DESC
            return $b['latest_story_time'] <=> $a['latest_story_time'];
        });

        // Clean up internal fields before sending response
        $finalData = array_map(function ($item) {
            unset($item['latest_story_time']);
            return $item;
        }, $data);

        return ResponseHelper::send(200, 'Stories fetched successfully', $finalData);
    }

    public function store(Request $request)
    {
        $this->directValidation([
            'type' => 'required|in:image,video,text',
            'media' => 'nullable|file',
            'thumbnail' => 'nullable|file',
            'text_content' => 'nullable|string',
            'background_color' => 'nullable|json',
            'text_color' => 'nullable|string',
            'metadata' => 'nullable|json',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'count_down' => 'nullable|json',
            'link' => 'nullable|string',
            'question' => 'nullable|string',
            'poll_question' => 'nullable|string',
            'poll_options' => 'nullable|array|min:2',
            'poll_options.*' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $story = new Story();
            $story->user_id = Auth::id();
            $story->type = $request->type;
            $story->text_content = $request->text_content;
            $story->background_color = $request->background_color ? json_decode($request->background_color, true) : null;
            $story->text_color = $request->text_color;
            $story->metadata = $request->metadata ? json_decode($request->metadata, true) : null;
            $story->location = $request->location;
            $story->latitude = $request->latitude;
            $story->longitude = $request->longitude;
            $story->count_down = $request->count_down ? json_decode($request->count_down, true) : null;
            $story->link = $request->link;
            $story->question = $request->question;
            $story->poll_question = $request->poll_question;
            $story->expires_at = Carbon::now()->addHours(24);
            $story->privacy = 'public';

            if ($request->hasFile('media')) {
                // $file = $request->file('media');
                $filePath = upload_file('media', 'story_images');
                $story->media_path = $filePath;
            }

            if ($request->hasFile('thumbnail')) {
                // $file = $request->file('thumbnail');
                $thumbnailPath = upload_file('thumbnail', 'story_images');
                $story->thumbnail_path = $thumbnailPath;
            }

            $story->save();

            if ($request->has('poll_options') && is_array($request->poll_options)) {
                foreach ($request->poll_options as $optionText) {
                    $story->pollOptions()->create([
                        'option_text' => $optionText
                    ]);
                }
            }

            DB::commit();

            return ResponseHelper::send(200,'Story uploaded successfully',$story);

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::send(500,'Failed to upload story: ' . $e->getMessage());
        }
    }

    public function show(User $user)
    {
         $stories = Story::active()
            ->where('user_id', $user->id)
            ->with('views')
            ->orderBy('created_at', 'asc')
            ->get();
         
        return ResponseHelper::send(200,'User stories fetched successfully',$stories);
    }

    public function destroy(Request $request)
    {
        $this->directValidation([
            'story_id' => 'required|integer',
        ]);

        $story = Story::where('user_id', Auth::id())->find($request->story_id);
        if (!$story) {
            return ResponseHelper::send(404,'Story not found');
        }
        
        $story->delete();
        return ResponseHelper::send(200,'Story deleted');
    }

    public function view(Request $request)
    {
        $user = Auth::user();
        $this->directValidation([
            'story_id' => 'required|integer',
        ]);
        $story = Story::find($request->story_id);

        if (!$story) {
            return ResponseHelper::send(404,'Story not found');
        }

        $existingView = \App\Models\StoryView::where('story_id', $story->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$existingView) {
            \App\Models\StoryView::create([
                'story_id' => $story->id,
                'user_id' => $user->id,
            ]);
        }

        return ResponseHelper::send(200,'Story marked as viewed');
    }

    public function react(Request $request, $id)
    {
        $request->validate([
            'reaction' => 'required|string',
        ]);

        $user = Auth::user();
        $story = Story::active()->find($id);

        if (!$story) {
            return ResponseHelper::send(404,'Story not found or expired');
        }

        \App\Models\StoryReaction::create([
            'story_id' => $story->id,
            'user_id' => $user->id,
            'reaction' => $request->reaction,
        ]);

        return ResponseHelper::send(200,'Reaction added');
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        $story = Story::active()->find($id);

        if (!$story) {
            return ResponseHelper::send(404,'Story not found or expired');
        }

        $receiverId = $story->user_id;

        // Check if thread exists
        $thread = \App\Models\Thread::where(function($q) use ($user, $receiverId) {
            $q->where('user_id', $user->id)->where('friend_id', $receiverId);
        })->orWhere(function($q) use ($user, $receiverId) {
            $q->where('user_id', $receiverId)->where('friend_id', $user->id);
        })->first();

        if (!$thread) {
            $thread = \App\Models\Thread::create([
                'user_id' => $user->id,
                'friend_id' => $receiverId,
                'last_msg_update_time' => Carbon::now(),
            ]);
        }

        $messageData = [
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'message' => $request->message,
            'type' => 'message',
            'status' => 'unread',
            'story_id' => $story->id,
        ];

        \App\Models\ThreadMessage::create($messageData);

        return ResponseHelper::send(200,'Reply sent');
    }

    public function storyDetails(Request $request)
    {
        $this->directValidation([
            'user_id' => 'required|integer',
        ]);

        $authenticatedUser = Auth::user();
        $targetUser = User::find($request->user_id);
        
        if (!$targetUser) {
            return ResponseHelper::send(404, 'User not found');
        }

        $stories = Story::active()
            ->where('user_id', $targetUser->id)
            ->with([
                'views', 
                'user:id,name,username,profile_image', 
                'pollOptions.votes',
                'pollVotes' => function ($query) use ($authenticatedUser) {
                    $query->where('user_id', $authenticatedUser->id);
                }
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($stories->isEmpty()) {
            return ResponseHelper::send(200, 'No active stories found for this user', [
                'user' => $targetUser->only(['id', 'name', 'username', 'profile_image']),
                'stories' => [],
                'all_seen' => true
            ]);
        }

        $storyList = $stories->map(function ($story) use ($authenticatedUser) {
            return [
                'id' => $story->id,
                'type' => $story->type,
                'media_path' => $story->media_path,
                'hls_path' => $story->hls_path,
                'hls_status' => $story->hls_status,
                'thumbnail_path' => $story->thumbnail_path,
                'text_content' => $story->text_content,
                'background_color' => $story->background_color,
                'text_color' => $story->text_color,
                'metadata' => $story->metadata,
                'location' => $story->location,
                'latitude' => $story->latitude,
                'longitude' => $story->longitude,
                'count_down' => $story->count_down,
                'link' => $story->link,
                'question' => $story->question,
                'poll_question' => $story->poll_question,
                'created_at' => $story->created_at,
                'expires_at' => $story->expires_at,
                'is_seen' => $story->views->contains('user_id', $authenticatedUser->id),
                'is_owner' => $story->user_id === $authenticatedUser->id,
                'poll' => $story->pollOptions->isNotEmpty() ? [
                    'options' => $story->pollOptions->map(function ($option) use ($authenticatedUser) {
                        return [
                            'id' => $option->id,
                            'option_text' => $option->option_text,
                            'votes_count' => $option->votes->count(),
                            'is_voted' => $option->votes->contains('user_id', $authenticatedUser->id),
                        ];
                    }),
                    'total_votes' => $story->pollOptions->sum(function ($option) {
                        return $option->votes->count();
                    }),
                    'voted_option_id' => $story->pollVotes->where('user_id', $authenticatedUser->id)->first()?->poll_option_id,
                ] : null,
            ];
        });

        $allSeen = $storyList->every('is_seen', true);

        $data = [
            'user' => $targetUser->only(['id', 'name', 'username', 'profile_image']),
            'stories' => $storyList,
            'all_seen' => $allSeen,
        ];

        return ResponseHelper::send(200, 'Story details fetched successfully', $data);
    }

    public function storyviewerList(Request $request)
    {
        $this->directValidation([
            'story_id' => 'required|integer',
        ]);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $story = Story::with(['pollOptions.votes'])->find($request->story_id);
        if (!$story) {
            return ResponseHelper::send(404, 'Story not found');
        }

        // Pre-map poll votes for quick lookup: [user_id => option_text]
        $votesMap = [];
        $pollSummaryData = [];
        if ($story->pollOptions->isNotEmpty()) {
            foreach ($story->pollOptions as $option) {
                $pollSummaryData[] = [
                    'option_text' => $option->option_text,
                    'count' => $option->votes->count(),
                ];
                foreach ($option->votes as $vote) {
                    $votesMap[$vote->user_id] = $option->option_text;
                }
            }
        }

        $viewers = $story->views()
            ->where('user_id', '!=', $story->user_id)
            ->with('user:id,name,profile_image')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($view) use ($votesMap) {
                return [
                    'id' => $view->user->id,
                    'name' => $view->user->name,
                    'profile_image' => $view->user->profile_image,
                    'viewed_at' => $view->created_at,
                    'voted_for' => $votesMap[$view->user->id] ?? null,
                ];
            });

        $questionResponses = $story->storyQuestionResponses()
            ->with('user:id,name,profile_image')
            ->get()
            ->map(function ($response) {
                return [
                    'id' => $response->id,
                    'user_id' => $response->user_id,
                    'name' => $response->user->name,
                    'user_profile_image' => $response->user->profile_image,
                    'response' => $response->response,
                    'responded_at' => $response->created_at,
                ];
            });

        $data = [
            'viewers' => $viewers,
            'story_viewers_count' => $story->views()->where('user_id', '!=', $story->user_id)->count(),
            'question_responses' => $questionResponses,
            'poll_summary' => !empty($pollSummaryData) ? $pollSummaryData : null,
        ];

        return ResponseHelper::send(200, 'Story viewers fetched successfully', $data);
    }

    public function reportStory(Request $request)
    {
        $this->directValidation([
            'story_id' => 'required|integer',
            'reason' => 'required|string',
        ]);

        $userId = Auth::user()->id;

        $story = Story::find($request->story_id);
        if (!$story) {
            return ResponseHelper::send(404,'Story not found');
        }

        $storyReport = StoryReport::create([
            'story_id' => $story->id,
            'user_id' => $userId,
            'reason' => $request->reason,
        ]);

        return ResponseHelper::send(200,'Story reported successfully',$storyReport);
    }

    public function respondToQuestion(Request $request)
    {
        $this->directValidation([
            'story_id' => 'required|integer',
            'response' => 'required|string',
        ]);

        $userId = Auth::user()->id;

        $story = Story::find($request->story_id);
        if (!$story) {
            return ResponseHelper::send(404,'Story not found');
        }

        if (!$story->question) {
            return ResponseHelper::send(400,'This story does not have a question to respond to');
        }

        $response = StoryQuestionResponse::create([
            'story_id' => $story->id,
            'user_id' => $userId,
            'response' => $request->response,
        ]);

        return ResponseHelper::send(200,'Response submitted successfully',$response);
    }

    public function voteToPoll(Request $request)
    {
        $this->directValidation([
            'story_id' => 'required|integer',
            'poll_option_id' => 'required|integer',
        ]);

        $user = Auth::user();
        $story = Story::active()->find($request->story_id);

        if (!$story) {
            return ResponseHelper::send(404, 'Story not found or expired');
        }

        $pollOption = \App\Models\StoryPollOption::where('story_id', $story->id)->find($request->poll_option_id);
        if (!$pollOption) {
            return ResponseHelper::send(404, 'Poll option not found for this story');
        }

        $existingVote = \App\Models\StoryPollVote::where('story_id', $story->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingVote) {
             return ResponseHelper::send(400, 'You have already voted on this poll');
        }

        \App\Models\StoryPollVote::create([
            'story_id' => $story->id,
            'poll_option_id' => $pollOption->id,
            'user_id' => $user->id,
        ]);

        return ResponseHelper::send(200, 'Vote recorded successfully');
    }

}

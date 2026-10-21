<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Models\BlockUser;
use App\Models\Message;
use App\Models\Thread;
use Illuminate\Http\Request;

class MessageController extends ResponseController
{
    // public function startChat(Request $request)
    // {
    //     $this->directValidation([
    //         'friend_id' => 'required|exists:users,id'
    //     ]);

    //     $userId = $request->user()->id;
    //     $friendId = $request->friend_id;

    //     if ($userId == $friendId) {
    //         return ResponseHelper::send(400, 'You cannot start a chat with yourself');
    //     }

    //     $thread = Thread::where(function ($q) use ($userId, $friendId) {
    //         $q->where('user_id', $userId)->where('friend_id', $friendId);
    //     })
    //         ->orWhere(function ($q) use ($userId, $friendId) {
    //             $q->where('user_id', $friendId)->where('friend_id', $userId);
    //         })
    //         ->first();

    //     if ($thread) {
    //         $thread->update([
    //             'last_msg_update_time' => now(),
    //             'is_active' => 'y'
    //         ]);
    //     } else {
    //         $thread = Thread::create([
    //             'user_id' => $userId,
    //             'friend_id' => $friendId,
    //             'is_active' => 'y',
    //             'last_msg_update_time' => now(),
    //             'user_status' => 'y',
    //             'friend_status' => 'y',
    //         ]);
    //     }

    //     return ResponseHelper::send(200, 'Chat started successfully', [
    //         'thread_id' => $thread->id
    //     ]);
    // }

    public function startChat(Request $request)
    {
        $this->directValidation([
            'friend_id' => 'required|exists:users,id'
        ]);

        $userId = $request->user()->id;
        $friendId = $request->friend_id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        if ($userId == $friendId) {
            return ResponseHelper::send(400, 'You cannot start a chat with yourself');
        }

        $isBlocked = BlockUser::where(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $userId)->where('block_user_id', $friendId);
        })->orWhere(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $friendId)->where('block_user_id', $userId);
        })->exists();

        $thread = Thread::where(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $userId)->where('friend_id', $friendId);
        })
            ->orWhere(function ($q) use ($userId, $friendId) {
                $q->where('user_id', $friendId)->where('friend_id', $userId);
            })
            ->first();

        if (!$thread) {
            $thread = Thread::create([
                'user_id' => $userId,
                'friend_id' => $friendId,
                'is_active' => 'y',
                'last_msg_update_time' => now(),
                'user_status' => 'y',
                'friend_status' => 'y',
                'type' => 'pending'
            ]);

            return ResponseHelper::send(200, 'Chat started', [
                'name' => $thread->friend->name ?? '',
                'thread_id' => $thread->id,
                'receiver_id' => $friendId,
                'profile_image' => $thread->friend->profile_image ? url($thread->friend->profile_image) : null,
                'is_blocked' => $isBlocked,
                'type' => $thread->type,
                'thread_messages' => []
            ]);
        }

        $messages = Message::where('thread_id', $thread->id)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q1) use ($userId) {
                    $q1->where('sender_id', $userId)->where('sender_status', 'n');
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('receiver_id', $userId)->where('receiver_status', 'n');
                });
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        Message::where('thread_id', $thread->id)
            ->where('receiver_id', $userId)
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        $groupedMessages = $messages->groupBy(function ($msg) {
            return $msg->created_at->format('Y-m-d');
        })->map(callback: function ($msgs, $date) {
            return [
                'created_at' => $date,
                'messages' => $msgs->map(function ($msg) {
                    return [
                        'message_id' => $msg->id,
                        'thread_id' => $msg->thread_id,
                        'sender_id' => $msg->sender_id,
                        'receiver_id' => $msg->receiver_id,
                        'message' => $msg->type === 'image' 
                                    ? url($msg->message) 
                                    : $msg->message,
                        'type' => $msg->type,
                        'created_at' => $msg->created_at->toDateTimeString(),
                    ];
                })->values()
            ];
        })->values();

        $receiver = $thread->user_id == $userId ? $thread->friend : $thread->user;

        return ResponseHelper::send(200, __('api.success_chat_exist'), [
            'name' => $receiver->name,
            'is_verified' => $receiver->is_verified ?? false,
            'thread_id' => $thread->id,
            'receiver_id' => $receiver->id,
            'profile_image' => $receiver->profile_image ? url($receiver->profile_image) : null,
            'is_blocked' => $isBlocked,
            'type' => $thread->type,
            'thread_messages' => $groupedMessages
        ]);
    }

    public function sendMessage(Request $request)
    {
        $this->directValidation([
            'thread_id' => 'required|exists:threads,id',
            'friend_id' => 'required|exists:users,id',
            'message' => 'required',
            'type' => 'required|in:text,image',
        ]);

        $userId = $request->user()->id;
        $user = $request->user();
        $friendId = $request->friend_id;
        $type     = $request->type;

        $thread = Thread::where('id', $request->thread_id)
            ->first();

        if (!$thread) {
            return ResponseHelper::send(404, 'Thread not found between these users');
        }

        $messageContent = $request->message;

        if($type == 'image'){
            $request->validate([
                'message' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($request->hasFile('message')) {
                $imagePath = upload_file('message', 'chat_images');
                $messageContent = $imagePath;
            }
        }

        $message = Message::create([
            'thread_id' => $thread->id,
            'sender_id' => $userId,
            'receiver_id' => $friendId,
            'message' => $messageContent,
            'type' => $request->type,
            'sender_status' => 'n',
            'receiver_status' => 'n',
        ]);

        $thread->update([
            'last_msg_update_time' => now(),
            'user_status' => 'n',
            'friend_status' => 'n',
        ]);

        // send_push($friendId, [
        //     'from_user_id' => $userId,
        //     'push_title' => 'New Message',
        //     'push_message' => $type === 'text' ? $request->message : 'Sent you an image',
        //     'push_type' => 5,
        // ], false);
        $senderName = $request->user()->name;
        $pushTitle = "{$senderName} sent you a message";
        $pushBody  = "";

        send_push($friendId, [
            'from_user_id' => $userId,
            'push_title' => $pushTitle,
            'push_message' => $messageContent,
            'message' => $messageContent,
            'push_type'    => 5,
            'extra' => [
                'profile_image' => $user->profile_image ? url($user->profile_image) : null,
            ]
        ], false);

        return ResponseHelper::send(200, __('api.success_message_sent'), [
            'id' => $message->id,
            'thread_id' => $message->thread_id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'message' => $message->type === 'image' 
                        ? url($message->message) 
                        : $message->message,
            'type' => $message->type,
            'created_at' => $message->created_at,
        ]);
    }

    public function chatList(Request $request)
    {
        $this->directValidation([
            'type' => ['required', 'in:approved,pending'],
        ]);
        $userId = $request->user()->id;
        $search = $request->search ?? '';
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $threadsQuery = Thread::with(['user', 'friend', 'lastMessage'])
            ->where('type', $request->type)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q1) use ($userId) {
                    $q1->where('user_id', $userId)
                        ->where('user_status', 'n');
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('friend_id', $userId)
                        ->where('friend_status', 'n');
                });
            });

        // if (!empty($search)) {
        //     $threadsQuery->whereHas('user', function ($q) use ($search) {
        //         $q->where('name', 'like', "%{$search}%");
        //     })->orWhereHas('friend', function ($q) use ($search) {
        //         $q->where('name', 'like', "%{$search}%");
        //     });
        // }
        if (!empty($search)) {
            $userId = auth()->id();

            $threadsQuery->where(function ($q) use ($search, $userId) {
                $q->whereHas('user', function ($q1) use ($search, $userId) {
                    $q1->where('name', 'like', "%{$search}%")
                    ->where('id', '!=', $userId);
                })
                ->orWhereHas('friend', function ($q2) use ($search, $userId) {
                    $q2->where('name', 'like', "%{$search}%")
                    ->where('id', '!=', $userId);
                });
            });
        }

        $threads = $threadsQuery
            ->latest('last_msg_update_time')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $chatList = $threads->map(function ($thread) use ($userId) {

            $chatUser = $thread->user_id == $userId ? $thread->friend : $thread->user;

            $unreadCount = Message::where('thread_id', $thread->id)
                ->where('receiver_id', $userId)
                ->where('status', 'unread')
                ->count();

            return [
                'thread_id' => $thread->id,
                'id' => $chatUser->id,
                'name' => $chatUser->name,
                'is_verified' => $chatUser->is_verified,
                'profile_image' => $chatUser->profile_image ? url($chatUser->profile_image) : null,
                'last_message' => $thread->lastMessage->message ?? '',
                'last_msg_update_time' => $thread->last_msg_update_time,
                'unread_count' => $unreadCount,
                'type' => $thread->lastMessage->type ?? null,
            ];
        });

        return ResponseHelper::send(200, __('api.success'), $chatList);
    }

    public function deleteChat(Request $request)
    {
        $this->directValidation([
            'thread_id' => 'required|exists:threads,id'
        ]);

        $userId = $request->user()->id;
        $thread = Thread::findOrFail($request->thread_id);

        if ($thread->user_id == $userId) {
            $thread->update(['user_status' => 'y']);

            Message::where('thread_id', $thread->id)
                ->where('sender_id', $userId)
                ->update(['sender_status' => 'y']);

            Message::where('thread_id', $thread->id)
                ->where('receiver_id', $userId)
                ->update(['receiver_status' => 'y']);

        } elseif ($thread->friend_id == $userId) {
            $thread->update(['friend_status' => 'y']);

            Message::where('thread_id', $thread->id)
                ->where('sender_id', $userId)
                ->update(['sender_status' => 'y']);

            Message::where('thread_id', $thread->id)
                ->where('receiver_id', $userId)
                ->update(['receiver_status' => 'y']);

        } else {
            return ResponseHelper::send(403, 'Unauthorized access to this thread.');
        }

        return ResponseHelper::send(200, __('api.success_message_delete'));
    }

    public function acceptRejectChat(Request $request)
    {
        $this->directValidation([
            'thread_id' => ['required', 'exists:threads,id'],
            'type' => ['required', 'in:approved,rejected'],
        ]);

        $userId = $request->user()->id;

        $thread = Thread::findOrFail($request->thread_id);

        if ($thread->user_id !== $userId && $thread->friend_id !== $userId) {
            return ResponseHelper::send(403, 'Unauthorized access to this thread.');
        }

        if ($thread->type !== 'pending') {
            return ResponseHelper::send(400, 'This chat request has already been processed.');
        }

        if ($request->type === 'rejected') {
            // $thread->delete();
            Message::where('thread_id', $thread->id)->delete();

            return ResponseHelper::send(200, __('api.success_chat_rejected'));
        }

        $thread->type = $request->type;
        $thread->save();

        return ResponseHelper::send(200, __('api.success'), [
            'thread_id' => $thread->id,
            'type' => $thread->type,
        ]);
    }
}

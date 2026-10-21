<?php

namespace App\Http\Controllers;

use App\Models\MeetupRequest;
use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\User as ModelsUser;
use App\Models\UserAlbumMedia;
use App\Product;
use App\Services\PushNotificationService;
use App\WalletPayments;
use Illuminate\Support\Facades\Log;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use stdClass;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class WebSocketController extends Controller implements MessageComponentInterface
{

    protected $clients;
    private $subscriptions;
    private $users;
    private $user_resources;
    private $connections = [];
    private $messageQueue = [];
    private $processing;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->users = [];
        $this->user_resources = [];
        $this->messageQueue = [];
        $this->processing = [];
    }
    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->users[$conn->resourceId] = $conn;
        $this->processing[$conn->resourceId] = false;
        // $this->connections[$conn->resourceId] = compact('conn') + ['user_id' => null];
        echo "new connection " . $conn->resourceId;
        Log::info('New Connection' . $conn->resourceId);
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        $disconnectedId = $conn->resourceId;

        Log::info('Close Connection =>' . $disconnectedId);
        $users =  $this->user_resources;
        Log::info('Close users Connection =>' . print_r($this->user_resources, true));
        if (count($users) > 0) {
            foreach ($users as $key => $user) {
                //   JoinActionUser::where('user_id', $key)->delete();
            }
        }
        if (isset($this->connections[$disconnectedId])) {
            unset($this->connections[$disconnectedId]);
            foreach ($this->connections as &$connection) {
                $connection['conn']->send(json_encode([
                    'offline_user' => $disconnectedId,
                    'from_user_id' => 'server control',
                    'from_resource_id' => null
                ]));
            }
        }
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        if (isset($this->connections[$conn->resourceId])) {
            if (isset($this->connections[$conn->resourceId]['user_id'])) {
                $userId = $this->connections[$conn->resourceId]['user_id'];
                echo "An error has occurred with user $userId: {$e->getMessage()}\n";
                unset($this->connections[$conn->resourceId]);
                $conn->close();
            }
        } else {
            Log::info('connection error' . $e->getMessage());
        }
    }

    /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $conn The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */

    function onMessage(ConnectionInterface $conn, $msg)
    {
        $data = \GuzzleHttp\json_decode($msg);
        Log::info('Step 1=>' . print_r($data->command, true));
        if (isset($data->command)) {
            switch ($data->command) {
                case "send_message":
                    Log::info('One To One Chat' . $msg);
                    // $file = "";
                    // if($data->type == "file" && !empty($data->image)){
                    //     $file = upload_base_64_img($data->image,"uploads/chat");   
                    // }
                    $return_data = \GuzzleHttp\json_decode($msg);
                    $return_data->created = Carbon::now()->format('Y-m-d H:i:s');
                    //$return_data->file = ($file) ? checkFileExist($file, 'no_user_image') : "";

                    $exist_thread = Thread::find($data->thread_id);
                    Log::info('Step 5');
                    if (isset($exist_thread) && !empty($exist_thread)) {
                        Log::info('Step 6');
                        $expired_time = null;
                        if ($data->type == "album") {
                            if (!empty($data->expired_time)) {
                                $expired_time = Carbon::now()->addMinutes($data->expired_time);
                            }
                        }
                        $thread_id = $exist_thread->id;
                        $message = new ThreadMessage();
                        $message->thread_id = $thread_id;
                        $message->sender_id = $data->from;
                        $message->receiver_id = $data->to;
                        $message->message = ($data->message) ?? "";
                        $message->latitude = ($data->latitude) ?? 0;
                        $message->longitude = ($data->longitude) ?? 0;
                        $message->object_id = ($data->object_id) ?? 0;
                        $message->file = ($data->image) ?? "";
                        $message->thumb_image = ($data->thumb_image) ?? "";
                        $message->file_type = ($data->file_type) ?? "";
                        $message->type = $data->type;
                        $message->reply_to_id = ($data->reply_to_id) ?? 0;
                        $message->story_id = ($data->story_id) ?? null;
                        $message->is_forward = ($data->is_forward) ?? false;
                        $message->view_once = $data->view_once ?? "no";
                        $message->status = 'unread';
                        $message->save();

                        $from_user = ModelsUser::find($data->from);
                        $push_message = ucwords(strtolower($from_user->name)) . " sent you a new message!";
                        $push_title = ucwords(strtolower($from_user->name)) . " sent you a new message!";
                        $push_type = 5;
                        if (isset($data->type) && $data->type == "multi_file") {
                            $push_message = ucwords(strtolower($from_user->name)) . " sent you a new photo!";
                            $push_title = ucwords(strtolower($from_user->name)) . " sent you a new photo!";
                            $push_type = 5;
                        }
                        $payload = array(
                            'from_user_id' => $data->from,
                            'push_type' => $push_type,
                            'push_message' => $data->message ?? "",
                            'push_title' => $push_title,
                            'object_id' => null,
                            'object_type' => null,
                            'image' => $message->file,
                            'message' => $data->message ?? "",
                            'extra' => [
                                'thread_id' => $thread_id,
                                'name' => $from_user->name,
                                'profile_image' => $from_user->profile_image,
                            ]
                        );
                        send_push($data->to, $payload, true);
                        $exist_thread->last_msg_update_time = date('Y-m-d H:i:s');
                        $exist_thread->save();
                        //$res = ['status' => 200, 'command' => 'message', 'msg' => __('api.succ'), 'data' => $message];

                        $message->sender_detail = ModelsUser::where("id", $message->sender_id)->first(['id', 'name', 'profile_image']);

                        $message->reply_message = null;
                        
                        if($message->reply_to_id > 0){
                            // $message->reply_message = ThreadMessage::find($message->reply_to_id);
                             $message->reply_message = ThreadMessage::with('story')->find($message->reply_to_id);
                        }
                        
                        $message->load('story');

                        $res = $message;

                        $res->command = "message";
                        Log::info('Step 7');
                        if (isset($this->user_resources[$data->to])) {
                            Log::info('Step 8');
                            foreach ($this->user_resources[$data->to] as $key => $resourceId) {
                                //if($key == 0){
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                                //}
                            }
                            $conn->send(\GuzzleHttp\json_encode($res));
                        }

                        if (isset($this->user_resources[$data->from])) {
                            foreach ($this->user_resources[$data->from] as $key => $resourceId) {
                                if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                            $conn->send(\GuzzleHttp\json_encode($res));
                        }
                    } else {
                        //$res = ['status' => 412, "command" => "message", 'msg' => "message not send", 'data' => []];
                        $res = ["command" => "message"];
                    }
                    $conn->send(\GuzzleHttp\json_encode($res));
                    break;
                case "edit_message":
                    Log::info('Edit One To One Chat' . $msg);
                    $return_data = \GuzzleHttp\json_decode($msg);

                    //$return_data->file = ($file) ? checkFileExist($file, 'no_user_image') : "";

                    $message = ThreadMessage::find($data->message_id);
                    Log::info('Step 5');
                    if (isset($message) && !empty($message)) {
                        $exist_thread = Thread::find($message->thread_id);
                        Log::info('Step 6');
                        $thread_id = $exist_thread->id;
                        $save_data = [
                            "status" => $data->status ?? "unread",
                            "is_viewed" => $data->is_viewed ?? false,
                            "viewed_at" => $data->viewed_at ?? $message->viewed_at,
                            "message" => $data->message ?? $message->message,
                            "delivery_status" => $data->delivery_status ?? "sent",
                            "is_edited" => true,
                        ];
                        $message->fill($save_data);
                        $message->save();

                        // $from_user = User::find($data->from);
                        // $payload = array(
                        //     'from_user_id' => $data->from,
                        //     'push_type' => 8,
                        //     'push_message' => ucwords(strtolower($from_user->name)) . " sent you a new message!",
                        //     'push_from' => ucwords(strtolower($from_user->name)) . " sent you a new message!",
                        //     'object_id' => $thread_id,
                        //     'extra' => [
                        //         'from_user_name' => $from_user->name
                        //     ]
                        // );
                        // send_push((int)$data->to, $payload);
                        $exist_thread->last_msg_update_time = date('Y-m-d H:i:s');
                        $exist_thread->save();
                        //$res = ['status' => 200, 'command' => 'message', 'msg' => __('api.succ'), 'data' => $message];


                        $message->sender_detail = ModelsUser::where("id", $message->sender_id)->first(['id', 'name', 'profile_image']);

                        $res = $message;

                        $res->command = "edit_message";
                        if (isset($this->user_resources[$data->to])) {
                            foreach ($this->user_resources[$data->to] as $key => $resourceId) {
                                //if($key == 0){
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                                // }
                            }
                            $conn->send(\GuzzleHttp\json_encode($res));
                        }

                        if (isset($this->user_resources[$data->from])) {
                            foreach ($this->user_resources[$data->from] as $key => $resourceId) {
                                if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                    } else {
                        //$res = ['status' => 412, "command" => "message", 'msg' => "message not send", 'data' => []];
                        $res = ["command" => "edit_message"];
                    }
                    $conn->send(\GuzzleHttp\json_encode($res));
                    break;
                case "delete_message":
                    Log::info('delete message One To One Chat' . $msg);
                    $return_data = \GuzzleHttp\json_decode($msg);

                    //$return_data->file = ($file) ? checkFileExist($file, 'no_user_image') : "";

                    $message = ThreadMessage::find($data->message_id);
                    Log::info('Step 5');
                    if (isset($message) && !empty($message)) {
                        $exist_thread = Thread::find($message->thread_id);

                        Log::info('Step 6');
                        $thread_id = $exist_thread->id;
                        $exist_thread->last_msg_update_time = date('Y-m-d H:i:s');
                        $exist_thread->save();
                        //$res = ['status' => 200, 'command' => 'message', 'msg' => __('api.succ'), 'data' => $message];

                        if ($data->type == "delete") {
                            $message->sender_msg_status = "n";
                            $message->save();
                        }


                        // $message->sender_detail = ModelsUser::where("id", $message->sender_id)->first(['id', 'name', 'profile_image']);

                        $res = $message;
                        if ($data->type == "withdraw") {
                            $message->sender_msg_status = "n";
                            $message->receiver_msg_status = "n";
                            $message->save();
                            // $message->delete();
                        }

                        $res->command = "delete_message";
                        if (isset($this->user_resources[$data->to])) {
                            foreach ($this->user_resources[$data->to] as $key => $resourceId) {
                                //if($key == 0){
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                                //}
                            }
                            $conn->send(\GuzzleHttp\json_encode($res));
                        }

                        if (isset($this->user_resources[$data->from])) {
                            foreach ($this->user_resources[$data->from] as $key => $resourceId) {
                                if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                    } else {
                        //$res = ['status' => 412, "command" => "message", 'msg' => "message not send", 'data' => []];
                        $res = ["command" => "delete_message"];
                    }
                    $conn->send(\GuzzleHttp\json_encode($res));
                    break;
                case "register":
                    $return_data = new stdClass();
                    $return_data->status = 200;
                    $return_data->command = "register";
                    $return_data->message = __('api.succ');
                    $res = $return_data;

                    unset($this->user_resources[$data->user_id]);

                    if (isset($data->user_id)) {
                        if (isset($this->user_resources[$data->user_id])) {
                            if (!in_array($conn->resourceId, $this->user_resources[$data->user_id])) {
                                $this->user_resources[$data->user_id][] = $conn->resourceId;
                            }
                        } else {
                            $this->user_resources[$data->user_id] = [];
                            $this->user_resources[$data->user_id][] = $conn->resourceId;
                        }
                    }
                    $conn->send(\GuzzleHttp\json_encode($this->users));
                    $conn->send(\GuzzleHttp\json_encode($this->user_resources));
                    break;
                case "unregister":

                    $return_data = new stdClass();
                    $return_data->status = 200;
                    $return_data->command = "unregister";
                    $return_data->message = __('api.succ');
                    $res = $return_data;
                    unset($this->user_resources[$data->user_id]);
                    $conn->send(\GuzzleHttp\json_encode($this->user_resources));
                    break;
                case "online":

                    $threads = Thread::where(function ($thread_user) use ($data) {
                        $thread_user->where('threads.user_id', '=', $data->user_id)->orWhere('threads.friend_id', '=', $data->user_id);
                    })->get();

                    $return_data = new stdClass();
                    $return_data->status = 200;
                    $return_data->command = "online";
                    $return_data->user_id = $data->user_id;
                    $return_data->message = __('api.succ');
                    $res = $return_data;

                    foreach ($threads as $value) {
                        if ($value->user_id == $data->user_id) {
                            $user_id = $value->friend_id;
                        } else {
                            $user_id = $value->user_id;
                        }
                        if (!empty($value) && $value->user_id == $data->user_id) {
                            DB::transaction(function () use ($value, $data) {
                                ThreadMessage::where('thread_id', '=', $value->id)
                                    ->where('receiver_id', '=', $data->user_id)
                                    ->where('sender_status', '=', 'y')
                                    ->update(['delivery_status' => 'delivered']);
                            }, 3);
                        } else {
                            DB::transaction(function () use ($value, $data) {
                                ThreadMessage::where('thread_id', '=', $value->id)
                                    ->where('receiver_id', '=', $data->user_id)
                                    ->where('receiver_status', '=', 'y')
                                    ->update(['delivery_status' => 'delivered']);
                            }, 3);
                        }

                        if (isset($this->user_resources[$user_id])) {
                            Log::info('Inside User ID => ' . print_r($this->user_resources[$user_id], true));
                            foreach ($this->user_resources[$user_id] as $key => $resourceId) {
                                if ($key == 0) {
                                    if (isset($this->users[$resourceId])) {
                                        $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                    }
                                }
                            }
                        } else {
                            // $this->user_resources[$live_user->user_id] = [];
                            // $this->user_resources[$live_user->user_id][] = $conn->resourceId;
                        }
                    }
                    break;
                case "typing":
                    $thread = Thread::find($data->thread_id);

                    $return_data = new stdClass();
                    $return_data->status = 200;
                    $return_data->command = "typing";
                    $return_data->user_id = $data->user_id;
                    $return_data->thread_id = $data->thread_id;
                    $return_data->message = __('api.succ');
                    $res = $return_data;

                    if ($thread->user_id == $data->user_id) {
                        $user_id = $thread->friend_id;
                    } else {
                        $user_id = $thread->user_id;
                    }
                    //ThreadMessage::where('thread_id', '=', $thread->id)->update(['status' => 'read', "delivery_status" => "seen"]);


                    if (isset($this->user_resources[$user_id])) {
                        Log::info('Inside User ID => ' . print_r($this->user_resources[$user_id], true));
                        foreach ($this->user_resources[$user_id] as $key => $resourceId) {
                            if ($key == 0) {
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                    } else {
                        // $this->user_resources[$live_user->user_id] = [];
                        // $this->user_resources[$live_user->user_id][] = $conn->resourceId;
                    }
                    break;
                case "notyping":
                    $thread = Thread::find($data->thread_id);

                    $return_data = new stdClass();
                    $return_data->status = 200;
                    $return_data->command = "notyping";
                    $return_data->user_id = $data->user_id;
                    $return_data->thread_id = $data->thread_id;
                    $return_data->message = __('api.succ');
                    $res = $return_data;

                    if ($thread->user_id == $data->user_id) {
                        $user_id = $thread->friend_id;
                    } else {
                        $user_id = $thread->user_id;
                    }
                    //ThreadMessage::where('thread_id', '=', $thread->id)->update(['status' => 'read', "delivery_status" => "seen"]);


                    if (isset($this->user_resources[$user_id])) {
                        Log::info('Inside User ID => ' . print_r($this->user_resources[$user_id], true));
                        foreach ($this->user_resources[$user_id] as $key => $resourceId) {
                            if ($key == 0) {
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                    } else {
                        // $this->user_resources[$live_user->user_id] = [];
                        // $this->user_resources[$live_user->user_id][] = $conn->resourceId;
                    }
                    break;
                case "offline":
                    $threads = Thread::where(function ($thread_user) use ($data) {
                        $thread_user->where('threads.user_id', '=', $data->user_id)->orWhere('threads.friend_id', '=', $data->user_id);
                    })->get();

                    $return_data = new stdClass();
                    $return_data->status = 200;
                    $return_data->command = "offline";
                    $return_data->user_id = $data->user_id;
                    $return_data->message = __('api.succ');
                    $res = $return_data;

                    foreach ($threads as $value) {
                        if ($value->user_id == $data->user_id) {
                            $user_id = $value->friend_id;
                        } else {
                            $user_id = $value->user_id;
                        }
                        if (isset($this->user_resources[$user_id])) {
                            Log::info('Inside User ID => ' . print_r($this->user_resources[$user_id], true));
                            foreach ($this->user_resources[$user_id] as $key => $resourceId) {
                                if ($key == 0) {
                                    if (isset($this->users[$resourceId])) {
                                        $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                    }
                                }
                            }
                        } else {
                            // $this->user_resources[$live_user->user_id] = [];
                            // $this->user_resources[$live_user->user_id][] = $conn->resourceId;
                        }
                    }
                    break;
                case "seen":

                    $thread = Thread::find($data->thread_id);

                    $return_data = new stdClass();
                    $return_data->status = 200;
                    $return_data->command = "seen";
                    $return_data->user_id = $data->user_id;
                    $return_data->thread_id = $data->thread_id;
                    $return_data->message = __('api.succ');
                    $res = $return_data;

                    if ($thread->user_id == $data->user_id) {
                        $user_id = $thread->friend_id;
                    } else {
                        $user_id = $thread->user_id;
                    }
                    DB::transaction(function () use ($thread, $data) {
                        ThreadMessage::where('thread_id', '=', $thread->id)->where('receiver_id', $data->user_id)->update(['status' => 'read', "delivery_status" => "seen"]);
                    }, 3);


                    if (isset($this->user_resources[$user_id])) {
                        Log::info('Inside User ID => ' . print_r($this->user_resources[$user_id], true));
                        foreach ($this->user_resources[$user_id] as $key => $resourceId) {
                            if ($key == 0) {
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                    } else {
                        // $this->user_resources[$live_user->user_id] = [];
                        // $this->user_resources[$live_user->user_id][] = $conn->resourceId;
                    }
                    break;
                // ─────────────────────────────────────────────
                // SEND CURRENT LOCATION (static one-time pin)
                // Client sends: { command, from, to, thread_id, latitude, longitude }
                // ─────────────────────────────────────────────
                case "send_location":
                    Log::info('Send Location: ' . $msg);

                    $exist_thread = Thread::find($data->thread_id);

                    if (isset($exist_thread) && !empty($exist_thread)) {
                        $message = new ThreadMessage();
                        $message->thread_id   = $exist_thread->id;
                        $message->sender_id   = $data->from;
                        $message->receiver_id = $data->to;
                        $message->message     = !empty($data->message) ? $data->message : 'Location';
                        $message->latitude    = $data->latitude  ?? 0;
                        $message->longitude   = $data->longitude ?? 0;
                        $message->type        = 'location';
                        $message->status      = 'unread';
                        $message->save();

                        $from_user = ModelsUser::find($data->from);

                        // Push notification
                        $payload = [
                            'from_user_id' => $data->from,
                            'push_type'    => 5,
                            'push_message' => ucwords(strtolower($from_user->name)) . ' shared a location!',
                            'push_title'   => ucwords(strtolower($from_user->name)) . ' shared a location!',
                            'object_id'    => null,
                            'object_type'  => null,
                            'image'        => '',
                            'extra'        => [
                                'thread_id'     => $exist_thread->id,
                                'name'          => $from_user->name,
                                'profile_image' => $from_user->profile_image,
                            ],
                        ];
                        send_push($data->to, $payload, true);

                        $exist_thread->last_msg_update_time = date('Y-m-d H:i:s');
                        $exist_thread->save();

                        $message->sender_detail = ModelsUser::where('id', $message->sender_id)
                            ->first(['id', 'name', 'profile_image']);
                        $res = $message->toArray();
                        $res['command'] = 'location';
                        $res['sender_detail'] = $message->sender_detail;

                        // Broadcast to receiver
                        if (isset($this->user_resources[$data->to])) {
                            foreach ($this->user_resources[$data->to] as $resourceId) {
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                        // Echo back to sender (and other sender devices)
                        if (isset($this->user_resources[$data->from])) {
                            foreach ($this->user_resources[$data->from] as $resourceId) {
                                if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                        $conn->send(\GuzzleHttp\json_encode($res));
                    } else {
                        $conn->send(\GuzzleHttp\json_encode(['command' => 'location_message', 'error' => 'Thread not found']));
                    }
                    break;

                // ─────────────────────────────────────────────
                // LIVE LOCATION — START
                // Client sends: { command, from, to, thread_id, latitude, longitude, duration_minutes }
                // Creates a DB row with type=live_location and an expiry time.
                // ─────────────────────────────────────────────
                case "live_location_start":
                    Log::info('Live Location Start: ' . $msg);

                    $exist_thread = Thread::find($data->thread_id);

                    if (isset($exist_thread) && !empty($exist_thread)) {
                        $duration = isset($data->duration_minutes) ? (int)$data->duration_minutes : 15;

                        $message = new ThreadMessage();
                        $message->thread_id                = $exist_thread->id;
                        $message->sender_id                = $data->from;
                        $message->receiver_id              = $data->to;
                        $message->message                  = !empty($data->message) ? $data->message : 'Live Location';
                        $message->latitude                 = $data->latitude  ?? 0;
                        $message->longitude                = $data->longitude ?? 0;
                        $message->type                     = 'live_location';
                        $message->live_location_expires_at = Carbon::now()->addMinutes($duration);
                        $message->status                   = 'unread';
                        $message->save();

                        $from_user = ModelsUser::find($data->from);

                        // Push notification
                        $payload = [
                            'from_user_id' => $data->from,
                            'push_type'    => 5,
                            'push_message' => ucwords(strtolower($from_user->name)) . ' is sharing live location!',
                            'push_title'   => ucwords(strtolower($from_user->name)) . ' is sharing live location!',
                            'object_id'    => null,
                            'object_type'  => null,
                            'image'        => '',
                            'extra'        => [
                                'thread_id'     => $exist_thread->id,
                                'name'          => $from_user->name,
                                'profile_image' => $from_user->profile_image,
                            ],
                        ];
                        send_push($data->to, $payload, true);

                        $exist_thread->last_msg_update_time = date('Y-m-d H:i:s');
                        $exist_thread->save();

                        $message->sender_detail = ModelsUser::where('id', $message->sender_id)
                            ->first(['id', 'name', 'profile_image']);
                        $res = $message->toArray();
                        $res['command'] = 'live_location_started';
                        $res['sender_detail'] = $message->sender_detail;

                        // Broadcast to receiver
                        if (isset($this->user_resources[$data->to])) {
                            foreach ($this->user_resources[$data->to] as $resourceId) {
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                        // Echo back to sender (other sender devices)
                        if (isset($this->user_resources[$data->from])) {
                            foreach ($this->user_resources[$data->from] as $resourceId) {
                                if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                        $conn->send(\GuzzleHttp\json_encode($res));
                    } else {
                        $conn->send(\GuzzleHttp\json_encode(['command' => 'live_location_started', 'error' => 'Thread not found']));
                    }
                    break;

                // ─────────────────────────────────────────────
                // LIVE LOCATION — UPDATE (called repeatedly by sender's GPS)
                // Client sends: { command, from, to, message_id, latitude, longitude }
                // Updates lat/lng on the existing message row and broadcasts new coords — NO new DB row.
                // ─────────────────────────────────────────────
                case "live_location_update":
                    Log::info('Live Location Update: ' . $msg);

                    $message = ThreadMessage::find($data->message_id ?? $data->id ?? null);

                    if (isset($message) && !empty($message)) {
                        // Only allow update if not expired and not stopped
                        $isExpired = $message->live_location_expires_at
                            && Carbon::now()->gt(Carbon::parse($message->live_location_expires_at));
                        $isStopped = !empty($message->live_location_stopped_at);

                        if ($isExpired || $isStopped) {
                            $conn->send(\GuzzleHttp\json_encode([
                                'command'    => 'live_location_updated',
                                'message_id' => $message->id,
                                'error'      => 'Live location session has ended',
                            ]));
                            break;
                        }

                        $message->latitude  = $data->latitude  ?? $message->latitude;
                        $message->longitude = $data->longitude ?? $message->longitude;
                        $message->save();

                        $res = new \stdClass();
                        $res->command    = 'live_location_updated';
                        $res->message_id = $message->id;
                        $res->thread_id  = $message->thread_id;
                        $res->from       = $data->from;
                        $res->to         = $data->to;
                        $res->latitude   = $message->latitude;
                        $res->longitude  = $message->longitude;
                        $res->live_location_expires_at = $message->live_location_expires_at;

                        // Broadcast to receiver
                        if (isset($this->user_resources[$data->to])) {
                            foreach ($this->user_resources[$data->to] as $resourceId) {
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                        // Echo back to other sender devices
                        if (isset($this->user_resources[$data->from])) {
                            foreach ($this->user_resources[$data->from] as $resourceId) {
                                if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                        $conn->send(\GuzzleHttp\json_encode($res));
                    } else {
                        $conn->send(\GuzzleHttp\json_encode(['command' => 'live_location_updated', 'error' => 'Message not found']));
                    }
                    break;

                // ─────────────────────────────────────────────
                // LIVE LOCATION — STOP
                // Client sends: { command, from, to, message_id }
                // Marks the session as stopped and notifies receiver.
                // ─────────────────────────────────────────────
                case "live_location_stop":
                    Log::info('Live Location Stop: ' . $msg);

                    $message = ThreadMessage::find($data->message_id ?? $data->id ?? null);

                    if (isset($message) && !empty($message)) {
                        $message->live_location_stopped_at = Carbon::now();
                        $message->save();

                        $res = new \stdClass();
                        $res->command    = 'live_location_stopped';
                        $res->message_id = $message->id;
                        $res->thread_id  = $message->thread_id;
                        $res->from       = $data->from;
                        $res->to         = $data->to;
                        $res->live_location_stopped_at = $message->live_location_stopped_at;

                        // Broadcast to receiver
                        if (isset($this->user_resources[$data->to])) {
                            foreach ($this->user_resources[$data->to] as $resourceId) {
                                if (isset($this->users[$resourceId])) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                        // Echo back to other sender devices
                        if (isset($this->user_resources[$data->from])) {
                            foreach ($this->user_resources[$data->from] as $resourceId) {
                                if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
                                    $this->users[$resourceId]->send(\GuzzleHttp\json_encode($res));
                                }
                            }
                        }
                        $conn->send(\GuzzleHttp\json_encode($res));
                    } else {
                        $conn->send(\GuzzleHttp\json_encode(['command' => 'live_location_stopped', 'error' => 'Message not found']));
                    }
                    break;

                default:
                    Log::info('default call' . $msg);
                    $example = array(
                        'methods' => [
                            "register"             => '{"command": "register", "user_id": 22}',
                            "unregister"           => '{"command": "unregister", "user_id": 22}',
                            "send_message"         => '{"command": "send_message", "from": 1, "to": 2, "thread_id": 10, "message": "Hello"}',
                            "send_location"        => '{"command": "send_location", "from": 1, "to": 2, "thread_id": 10, "latitude": "23.0225", "longitude": "72.5714"}',
                            "live_location_start"  => '{"command": "live_location_start", "from": 1, "to": 2, "thread_id": 10, "latitude": "23.0225", "longitude": "72.5714", "duration_minutes": 15}',
                            "live_location_update" => '{"command": "live_location_update", "from": 1, "to": 2, "message_id": 55, "latitude": "23.0300", "longitude": "72.5800"}',
                            "live_location_stop"   => '{"command": "live_location_stop", "from": 1, "to": 2, "message_id": 55}',
                        ],
                    );
                    $conn->send(json_encode($example));
                    break;
            }
        }
    }
}

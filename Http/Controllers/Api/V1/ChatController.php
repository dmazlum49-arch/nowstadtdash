<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Models\BlockUser;
use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\User;
use App\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Help;
use App\Models\HelpUser;

class ChatController extends ResponseController
{
    protected $validationService;
    public $thread_obj;
    public $thread_message_obj;
    public $user_obj;
    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
        $this->thread_obj = new Thread();
        $this->thread_message_obj = new ThreadMessage();
        $this->user_obj = new User();
    }

    
    public function sendRequest(Request $request)
    {
        $valid = $this->directValidation([
            'friend_id' => ['required', 'exists:users,id'],
            'reply_to_id' => ['nullable', 'exists:thread_messages,id'],
        ]);

        $user = $request->user();
        $request_data = $request->all();
        unset($request_data['message']);
        $request_data['user_id'] = $user->id;
        $request_data['last_msg_update_time'] = Carbon::now();
        

        $thread = $this->thread_obj->where(function ($q) use ($request) {
                $q->where('user_id', '=', $request->friend_id)
                    ->orWhere('friend_id', '=', $request->friend_id);
            })->where(function ($q) use ($user) {
                $q->where('user_id', '=', $user->id)
                    ->orWhere('friend_id', '=', $user->id);
            })->first();
        
        if(empty($thread)){
            // $request_data['type'] = 'approved';
            $thread = $this->thread_obj->saveThread($request_data);
        }

        if($thread){
           ThreadMessage::create([
                "thread_id" => $thread->id,
                "sender_id" => $user->id,
                "receiver_id" => $request->friend_id,
                "message" => $request->message,
                "type" => "message",
                "reply_to_id" => $request->reply_to_id ?? 0,
                "is_forward" => $request->is_forward ?? false,
           ]);
            $payload = array(
                'from_user_id' =>$user->id,
                'push_type' => 5,
                'push_message' => $user->name.' Send New Message!',
                'push_title' => 'Send New Message',
                'object_id' => $thread->id,
                'object_type' => null,
            );
            send_push($request->friend_id, $payload, true);
        }
        
        return ResponseHelper::send(200, __('api.success'), $thread);
    }
    public function listing(Request $request){

        $valid = $this->directValidation([
           'type' => ['required', 'in:request,all,new,pinned'],
        ]);

        $limit = ($request->limit) ?? 10;
        $offset = ($request->offset) ?? 0;
        $user = $request->user();
        $user_id = $user->id;
        $userId = $user->id;
        
        $queryInstance = Thread::select('threads.*')->with('messageLatest')
            // ->where(function ($q) use ($user) {
            //     $q->where('user_id', '=', $user->id)
            //         ->orWhere('friend_id', '=', $user->id);
            // })
            ->leftJoin('users as u','u.id','=','threads.user_id')
            ->leftJoin('users as f','f.id','=','threads.friend_id')
           ->when($limit != "" && $offset != "", function ($q) use ($limit, $offset) {
                $q->limit($limit)->offset($offset);
            })->orderBy('last_msg_update_time', 'DESC');

         
        $search = ($request->search) ?? "";
        $filed_arr = ['u.name','f.name','u.username','f.username'];

        if(!empty($search)){
            $queryInstance->where(function($query) use ($search,$filed_arr) {
                foreach($filed_arr as $column){
                    $query->orWhere($column,'like', "%$search%");
                }
            });
        }
        if($request->type == "request"){
            $queryInstance->where(function ($thread_user) use ($user_id) {
                $thread_user->where('threads.friend_id', '=', $user_id);
            })->where("threads.type","pending");
        }
        else{
            $queryInstance->where(function ($thread_user) use ($user_id) {
                $thread_user->where('threads.user_id', '=', $user_id)->orWhere('threads.friend_id', '=', $user_id);
            })->where("threads.type","approved");
        }
        $queryInstance->where(function ($thread_user) use ($user_id) {
            $thread_user->where('threads.user_id', '=', $user_id)->orWhere('threads.friend_id', '=', $user_id);
        });
        $threads = $queryInstance->where("threads.is_active","y")->get();
        
        $count_unread = $this->thread_message_obj->select('thread_id', DB::raw('count(*) as total'))->groupBy('thread_id')->whereIn('thread_id', $threads->pluck('id')->toArray())->where('receiver_id', $user_id)->where('status', '=', 'unread')->get();

        $unread_messages = $count_unread->pluck("total","thread_id")->toArray();
        $thread_data = collect([]);
        if ($threads->count() > 0) {    
            foreach ($threads as $key => $value) {

                $message_latest = $this->thread_message_obj->where('thread_id', $value->id)->orderBy('created_at', 'DESC')->first();

                if(isset($unread_messages) && count($unread_messages) > 0){
                    $total_unread = ($unread_messages[$value->id]) ?? 0;
                }
                $latest_message_text = $message_latest->message ?? "";
                if(isset($message_latest->sender_id) && $user_id == $message_latest->sender_id){

                    
                    $is_read = "read";
                    if($message_latest->sender_msg_status == "n"){
                        $latest_message_text = "";
                    }
                }
                else{
                    $is_read = ($message_latest->status) ?? null;
                }

                $sender_detail = (object)array();
                if ($value->user_id == $user_id) {
                    $sender_detail = $this->user_obj->where(['id' => $value->friend_id])->first(['id', 'name', 'profile_image']);
                } else {
                    $sender_detail = $this->user_obj->where(['id' => $value->user_id])->first(['id', 'name', 'profile_image']);
                }


                $thread_data[] = [
                        "id" =>$value->id,
                        "user_id" =>$value->user_id,
                        "friend_id" =>$value->friend_id,
                        "message_latest" => ($latest_message_text) ?? null,
                        "message_type" => ($message_latest->type) ?? null,
                        "message_file" => ($message_latest->file) ?? null,
                        "message_file_type" => ($message_latest->file_type) ?? null,
                        "total_unread" => ($total_unread) ?? 0,
                        "message_id"=>($message_latest->id) ?? 0,
                        "last_msg_update_time"=>($message_latest->created_at) ?? Carbon::now(),
                        "message_created_at" => ($message_latest->created_at) ?? Carbon::now(),
                        "message_updated_at" => ($message_latest->updated_at) ?? Carbon::now(),
                        "thread_created_at" => ($value->created_at) ?? Carbon::now(),
                        "is_read" => $is_read,
                        "sender_detail" => $sender_detail,
                        "last_seen" => lastSeenShort($sender_detail->last_activity),
                        "sender_msg_status"     => optional($message_latest)->sender_msg_status,
                        "receiver_msg_status"   => optional($message_latest)->receiver_msg_status,
                ];
            }
        }
        $this->sendResponse(200, __('api.success'), $thread_data);
    } 
    public function messageSearch(Request $request){
        $auth_user = $request->User();

        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $threadIds = Thread::where(function ($q) use ($auth_user) {
            $q->where('user_id', $auth_user->id)
                ->orWhere('friend_id', $auth_user->id);
        })->pluck('id');

        $query = $request->get('search', '');

        // Search only inside these threads
        $results = ThreadMessage::with(['sender:id,name,profile_image,first_name'])
            ->whereIn('thread_id', $threadIds)
            ->where('message', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $this->sendResponse(200, __('api.success'), $results);
        
    }
    public function acceptRejectStatus(Request $request)
    {
        $valid = $this->directValidation([
            'thread_id' => ['required', 'exists:threads,id'],
            'status' => ['required', 'in:approved,rejected'],
            
        ]);

        $user = $request->user();
        
        $thread = $this->thread_obj->find($request->thread_id);

        if($thread){
            $thread->type = $request->status;
            if($request->status == "rejected"){
                 $thread->delete();
            }
            else{
                $thread->save();
            }
            // $thread->save();
           
        }
        
        return ResponseHelper::send(200, __('api.success'), $thread);
    }
    
    public function chatHistory(Request $request)
    {
        $limit = ($request->limit) ?? 10;
        $offset = ($request->offset) ?? 0;

        if(isset($request->is_guest) && $request->is_guest == "yes"){
            $user = User::find($request->user_id);
        }
        else{
            $user = $request->user();
        }
        $auth_user = $request->user();

        $this->directValidation([
            'thread_id' => ['required', 'exists:threads,id'],
            'help_id' => ['nullable', 'exists:helps,id'],
        ]);
        $isThreadExist = $this->thread_obj->find($request->thread_id);

       
        $sender_details = null;
        $extra_data = [];
        if($isThreadExist->user_id != $user->id ?? 0){
            $sender_details = User::select(['id', 'name', 'profile_image','last_activity'])->where("id", $isThreadExist->user_id)->first();
        } else {
            $sender_details = User::select(['id', 'name', 'profile_image','last_activity'])->where("id", $isThreadExist->friend_id)->first();
        }

        if(!empty($sender_details)){
            $sender_details->last_seen = lastSeenShort($sender_details->last_activity);
        }
        $extra_data['user_detail'] = $sender_details;

        if (!empty($isThreadExist) && $isThreadExist['user_id'] == $user->id) {
            DB::transaction(function () use ($isThreadExist, $user) {
                ThreadMessage::where('thread_id', '=', $isThreadExist->id)
                    ->where('receiver_id', '=', $user->id ?? 0)
                    ->where('sender_status', '=', 'y')
                    ->update(['status' => 'read']);
            }, 3);
        } else {
            DB::transaction(function () use ($request, $user) {
                ThreadMessage::where('thread_id', '=', $request->thread_id)
                    ->where('receiver_id', '=', $user->id ?? 0)
                    ->where('receiver_status', '=', 'y')
                    ->update(['status' => 'read']);
            }, 3);
        }
        $chatHistory = ThreadMessage::with('story')->where('thread_id', '=', $request->thread_id)
        ->where(function($query) use ($user) {
            $query->where(function($q) use ($user) {
                $q->where('sender_id', $user->id)
                ->whereIn('sender_msg_status', ['y', 'n']);
            })->orWhere(function($q) use ($user) {
                $q->where('receiver_id', $user->id)
                ->whereIn('receiver_msg_status', ['y', 'n']);
            });
        })
        ->when($limit != "" && $offset != "", function ($q) use ($limit, $offset) {
                $q->limit($limit)->offset($offset);
            })->latest()->get();

        $isBlocked = BlockUser::where('user_id', $user->id ?? 0)
        ->where('block_user_id', $sender_details->id ?? 0)->exists();

        // $isBlocked = ThreadUserMeta::where('thread_id', $isThreadExist->id)
        // ->where('user_id', $user->id ?? 0)->where('is_pinned', 'y')->exists();

        $extra_data['thread_details'] = [
            "thread_id" => $isThreadExist->id,
            "user_id" => $isThreadExist->user_id,
            "friend_id" => $isThreadExist->friend_id,
            "is_blocked" => $isBlocked,
            "is_pinned" => $isBlocked,
            "thread_type" => $isThreadExist->type,
        ];

        $message_arr = [];
        foreach($chatHistory as $chat){
            $chat->sender_detail = User::where("id", $chat->sender_id)->first(['id', 'name', 'profile_image']);

            $message_arr[] = $chat;
        }

        $helpDetails = null;
        $helpId = $request->help_id;
        $helpUser = null;

        // Determine friend ID (the other participant in the thread)
        $friendId = ($isThreadExist->user_id == $user->id)
            ? $isThreadExist->friend_id
            : $isThreadExist->user_id;

        if ($helpId) {
            // Check if friend offered help on the specific requested help_id created by current user
            $helpUser = HelpUser::where('help_id', $helpId)
                ->where('user_id', $friendId)
                ->whereHas('help', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();
        }

        if (!$helpUser) {
            // Step 1: Search for oldest pending help request created by current user where friend offered help
            $helpUser = HelpUser::where('user_id', $friendId)
                ->whereHas('help', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->where('status', '!=', 'completed');
                })
                ->orderBy('id', 'asc')
                ->first();
        }

        if (!$helpUser) {
            // Step 2: Fallback to latest completed help request created by current user ONLY if friend was an accepted helper
            $helpUser = HelpUser::where('user_id', $friendId)
                ->where('status', 'accepted')
                ->whereHas('help', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->where('status', 'completed');
                })
                ->latest('id')
                ->first();
        }

        if ($helpUser) {
            $help = Help::find($helpUser->help_id);
            if ($help && $help->user_id == $user->id) {
                $totalHelpers = HelpUser::where('help_id', $help->id)->count();
                $helpDetails = [
                    'help_id' => $help->id,
                    'help_creator_id' => $help->user_id,
                    'help_helper_id' => $helpUser->user_id,
                    'help_status' => $help->status,
                    'helper_status' => $helpUser->status,
                    'title' => $help->title,
                    'total_helpers' => $totalHelpers,
                ];
            }
        }
        $extra_data['help_details'] = $helpDetails;

        $this->sendResponse(200, __('api.success'), $chatHistory, $extra_data);
    }
    public function deleteChatDetail(Request $request)
    {
        $user = $request->user();
        $this->directValidation([
            'thread_id' => ['required', 'exists:threads,id'],
        ]);
        
        ThreadMessage::where('thread_id', '=', $request->thread_id)
            ->where('sender_id', '=', $request->user()->id)
            ->update(['sender_msg_status' => 'n']);
            
        ThreadMessage::where('thread_id', '=', $request->thread_id)
            ->where('receiver_id', '=', $request->user()->id)
            ->update(['receiver_msg_status' => 'n']);
                
        $this->sendResponse(200, __('api.success'));
    }
    public function deleteChat(Request $request)
    {
        $user = $request->user();
        $this->directValidation([
            'thread_id' => ['required', 'exists:threads,id'],
        ]);
        
        $Thread = Thread::find($request->thread_id);
        if(!empty($Thread)){
            $messages = $Thread->messages;
            if(isset($messages) && $messages->count() > 0){
                $messages->each->delete();
            }
            $Thread->delete();
        }
                
        $this->sendResponse(200, __('api.success'));
    }
    
    // public function uploadMedia(Request $request){
    //     $user = $request->user();
    //     $media = "";
    //     if ($request->hasFile('file')) {
    //         $media = upload_file('file', 'chat_upload');    
    //     }
    //     $this->sendResponse(200, __('api.success'), ["media_path" => $media]);
    // }

    public function uploadMedia(Request $request)
    {
        $user = $request->user();
        $media = "";
        $file_key = 'file';

        // Log request details for debugging
        

        if (!$request->hasFile($file_key)) {
            // Try to find any uploaded file if 'file' key is missing
            $files = $request->allFiles();
            if (!empty($files)) {
                $file_key = array_key_first($files);
            }
        }

        if ($request->hasFile($file_key)) {
            $media = upload_file($file_key, 'chat_upload');
        }

        if (empty($media)) {
            return $this->sendError(__('api.error_something_went_wrong'), false);
        }

        $this->sendResponse(200, __('api.success'), ["media_path" => $media]);
    }


    public function sendMessage(Request $request)
    {
        $valid = $this->directValidation([
            'friend_id' => ['required', 'exists:users,id'],
            'reply_to_id' => ['nullable', 'exists:thread_messages,id'],
        ]);

        $user = $request->user();
        $request_data = $request->all();
        unset($request_data['message']);
        $request_data['user_id'] = $user->id;
        $request_data['last_msg_update_time'] = Carbon::now();
        

        $thread = $this->thread_obj->where(function ($q) use ($request) {
                $q->where('user_id', '=', $request->friend_id)
                    ->orWhere('friend_id', '=', $request->friend_id);
            })->where(function ($q) use ($user) {
                $q->where('user_id', '=', $user->id)
                    ->orWhere('friend_id', '=', $user->id);
            })->first();
        
        if(empty($thread)){
            $request_data['type'] = 'approved';
            $thread = $this->thread_obj->saveThread($request_data);
        }

        if($thread){


           ThreadMessage::create([
                "thread_id" => $thread->id,
                "sender_id" => $user->id,
                "receiver_id" => $request->friend_id,
                "message" => $request->message,
                "type" => "message",
                "reply_to_id" => $request->reply_to_id ?? 0,
                "is_forward" => $request->is_forward ?? false,
           ]);
            
            $payload = array(
                'from_user_id' => $user->id,
                'push_type' => 5,
                'push_message' => $user->name.' sent you a new message!',
                'push_title' => $user->name.' sent you a new message!',
                'object_id' => $thread->id,
                'object_type' => null,
                'image' => '',
                'extra' => [
                    'thread_id' => $thread->id,
                    'name' => $user->name,
                    'profile_image' => $user->profile_image,
                ]
            );
            send_push($request->friend_id, $payload, true);
        }
        
        return ResponseHelper::send(200, __('api.success'), $thread);
    }
}

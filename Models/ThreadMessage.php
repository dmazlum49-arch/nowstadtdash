<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreadMessage extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getFileAttribute($val){
        if (!empty($this->hls_path)) {
            return $this->hls_path;
        }
        if(!empty($val)){
            return checkFileExist($val, 'no_user_image');
        }
        return "";
    }

    public function getThumbImageAttribute($val){
        if(!empty($val)){
            return checkFileExist($val, '');
        }
        return "";
    }
     public function getSenderIdAttribute($val){
        return (int) $val ?? 0;
    }
    public function getReceiverIdAttribute($val){
        return (int) $val ?? 0;
    }
    public function getThreadIdAttribute($val){
        return (int) $val ?? 0;
    }
    public function getImagesAttribute($val){
        if(!empty($val)){
            $return_arr = [];
            $explode_images = explode(",",$val);
            if(count($explode_images) > 0){
                foreach($explode_images as $explode_image){
                    $return_arr[] = checkFileExist($explode_image, 'no_user_image');
                }
            }
            return $return_arr;
        }
        return [];
    }
    public function getNsfwImagesAttribute($val){
        if(!empty($val)){
            $return_arr = [];
            $explode_images = explode(",",$val);
            if(count($explode_images) > 0){
                foreach($explode_images as $explode_image){
                    $return_arr[] = checkFileExist($explode_image, 'no_user_image');
                }
            }
            return $return_arr;
        }
        return [];
    }
    
    public function saveThreadMessage($data=[],$message=null,$message_id=0){

        if(!empty($message)){
            //do nothing
        }
        else if($message_id > 0){
            $message = $this->find($message_id);
        }
        else{
            $message = new ThreadMessage;
        }
        $message->fill($data);
        $message->save();

        return $message;
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class, 'thread_id');
    }
    public function replyMessage()
    {
        return $this->belongsTo(ThreadMessage::class, 'reply_to_id');
    }

    public function getIsEditedAttribute($val)
    {
        return (bool) $val;
    }
    
    public function getIsDeletedAttribute($val)
    {
        return (bool) $val;
    }

    public function getIsForwardAttribute($val)
    {
        return (bool) $val;
    }

    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

}

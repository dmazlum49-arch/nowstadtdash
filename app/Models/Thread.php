<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'thread_id')->latestOfMany();
    }
     public function getImageAttribute($val){
        return checkFileExist($val, 'no_user_image');
    }
    
    public function messageLatest(){
        return $this->hasOne(ThreadMessage::class,'thread_id')->latest();
    }
    public function messages(){
        return $this->hasMany(ThreadMessage::class,'thread_id','id');
    }
    public function saveThread($data=[],$thread=null,$thread_id=0){

        if(!empty($thread)){
            //do nothing
        }
        else if($thread_id > 0){
            $thread = $this->find($thread_id);
        }
        else{
            $thread = new Thread;
        }
        $thread->fill($data);
        $thread->save();

        return $thread;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryPollVote extends Model
{
    use HasFactory;

    protected $fillable = ['story_id', 'poll_option_id', 'user_id'];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function pollOption()
    {
        return $this->belongsTo(StoryPollOption::class, 'poll_option_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryPollOption extends Model
{
    use HasFactory;

    protected $fillable = ['story_id', 'option_text'];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function votes()
    {
        return $this->hasMany(StoryPollVote::class, 'poll_option_id');
    }
}

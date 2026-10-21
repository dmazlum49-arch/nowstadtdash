<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Story extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'count_down' => 'array',
        'background_color' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function views()
    {
        return $this->hasMany(StoryView::class);
    }

    public function reactions()
    {
        return $this->hasMany(StoryReaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    public function getMediaPathAttribute($val)
    {
        if (!empty($this->hls_path)) {
            return $this->hls_path;
        }
        return checkFileExist($val, '');
    }

    public function getThumbnailPathAttribute($val)
    {
        return checkFileExist($val, null);
    }

    public function storyQuestionResponses()
    {
        return $this->hasMany(StoryQuestionResponse::class);
    }

    public function pollOptions()
    {
        return $this->hasMany(StoryPollOption::class);
    }

    public function pollVotes()
    {
        return $this->hasMany(StoryPollVote::class);
    }
}

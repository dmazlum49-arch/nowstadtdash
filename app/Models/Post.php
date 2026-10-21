<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function postImages()
    {
        return $this->hasMany(PostImages::class, 'post_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class, 'post_id');
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(BookmarkPost::class, 'post_id');
    }

    public function reports()
    {
        return $this->hasMany(ReportPost::class, 'post_id');
    }
}

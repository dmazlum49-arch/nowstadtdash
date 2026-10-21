<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // public function replies()
    // {
    //     return $this->hasMany(PostComment::class, 'parent_id')
    //         ->with('user');
    // }

    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_id')
            ->where('status', 'active')
            ->with(['user', 'replies']); // recursion
    }

    public function reports()
    {
        return $this->hasMany(ReportComment::class, 'comment_id');
    }
}

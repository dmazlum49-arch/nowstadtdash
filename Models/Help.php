<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Help extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function helpers()
    {
        return $this->hasMany(HelpUser::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function helpImages()
    {
        return $this->hasMany(HelpImage::class, 'help_id');
    }

    public function likes()
    {
        return $this->hasMany(HelpLike::class, 'help_id');
    }

    public function comments()
    {
        return $this->hasMany(HelpComment::class, 'help_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(BookmarkHelp::class, 'help_id');
    }
    
}

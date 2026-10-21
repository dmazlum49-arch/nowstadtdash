<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getBannerImageAttribute($val)
    {
        return empty($val) ? null : checkFileExist($val, '');
    }

    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function eventImages()
    {
        return $this->hasMany(EventImages::class, 'event_id');
    }

    public function joinedUsers()
    {
        return $this->belongsToMany(User::class, 'event_user_joins', 'event_id', 'user_id');
    }
}

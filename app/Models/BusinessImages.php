<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessImages extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getImagePathAttribute($val)
    {
        return checkFileExist($val, 'no_user_image');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}

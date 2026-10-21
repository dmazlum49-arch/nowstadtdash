<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardStampCard extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function getLogoAttribute($val)
    {
        return checkFileExist($val, 'no_user_image');
    }
}

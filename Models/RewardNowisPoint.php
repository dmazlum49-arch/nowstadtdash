<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardNowisPoint extends Model
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

    public function getIsAvailableAttribute()
    {
        if (!$this->date) {
            return true;
        }

        $start = \Carbon\Carbon::parse($this->date . ' ' . ($this->start_time ?? '00:00:00'));
        $end = \Carbon\Carbon::parse($this->date . ' ' . ($this->end_time ?? '23:59:59'));

        return now()->between($start, $end);
    }
}

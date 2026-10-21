<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['badge_label'];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function claims()
    {
        return $this->hasMany(BoostClaim::class, 'boost_id');
    }

    public function getBadgeLabelAttribute()
    {
        return !empty($this->label) ? $this->label : (($this->multiplier ?? 2) . 'x Nowis');
    }

    public function scopeActive($query)
    {
        $now = Carbon::now('Europe/Vienna');
        $currentDateTime = $now->format('Y-m-d H:i:s');

        return $query->whereRaw("CONCAT(start_date, ' ', start_time) <= ?", [$currentDateTime])
                     ->whereRaw("CONCAT(end_date, ' ', end_time) >= ?", [$currentDateTime]);
    }

    public function isActive(): bool
    {
        $now = Carbon::now('Europe/Vienna');
        $start = Carbon::parse("{$this->start_date} {$this->start_time}", 'Europe/Vienna');
        $end = Carbon::parse("{$this->end_date} {$this->end_time}", 'Europe/Vienna');

        return $now->between($start, $end);
    }
}

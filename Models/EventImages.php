<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventImages extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        'event_id',
        'image_path',
    ];

    public function getImagePathAttribute($val)
    {
        return empty($val) ? null : checkFileExist($val, '');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}

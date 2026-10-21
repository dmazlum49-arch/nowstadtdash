<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostImages extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function getImagePathAttribute($val)
    {
        if (!empty($this->hls_path)) {
            return $this->hls_path;
        }
        return $val ? checkFileExist($val) : null;
    }

    public function toResponseArray()
    {
        return [
            'id' => $this->id,
            'image_path' => $this->image_path,
            'thumbnail_path' => $this->thumbnail_path,
            'type' => $this->type,
        ];
    }

    public function getThumbnailPathAttribute($val)
    {
        return $val ? checkFileExist($val) : null;
    }
}

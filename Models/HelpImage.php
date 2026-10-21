<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function help()
    {
        return $this->belongsTo(Help::class, 'help_id');
    }

    public function getImagePathAttribute($val)
    {
        if (!empty($this->hls_path)) {
            return $this->hls_path;
        }
        return $val ? checkFileExist($val) : null;
    }

    public function getThumbnailPathAttribute($val)
    {
        return $val ? checkFileExist($val) : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeBanner extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getImageAttribute($val)
    {
        return empty($val) ? null : checkFileExist($val, '');
    }
}

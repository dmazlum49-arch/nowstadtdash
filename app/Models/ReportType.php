<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getImageAttribute($val)
    {
        return checkFileExist($val, 'no_user_image');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $guarded = [];

    public function getValueAttribute($val)
    {
        return ($this->input_type == "file") ? checkFileExist($val) : $val;
    }
}

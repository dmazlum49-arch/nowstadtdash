<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $guarded = [];

    public static function get_user_tokens($user_id)
    {
        return self::where('user_id', $user_id)
            ->whereNotNull('device_token')
            ->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockUser extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function blockedUser()
    {
        return $this->belongsTo(User::class, 'block_user_id');
    }
}

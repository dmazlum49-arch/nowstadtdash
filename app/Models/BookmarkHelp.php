<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookmarkHelp extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function help()
    {
        return $this->belongsTo(Help::class, 'help_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

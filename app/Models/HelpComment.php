<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function help()
    {
        return $this->belongsTo(Help::class, 'help_id');
    }

    public function replies()
    {
        return $this->hasMany(HelpComment::class, 'parent_id')
            ->where('status', 'active')
            ->with(['user', 'replies']); // recursion
    }

    public function reports()
    {
        return $this->hasMany(ReportComment::class, 'comment_id');
    }
}

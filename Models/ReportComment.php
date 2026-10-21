<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportComment extends Model
{
    use HasFactory;

    protected $guarded = [];

     public function comment()
     {
         return $this->belongsTo(PostComment::class);
     }
     
     public function user()
    {
        return $this->belongsTo(User::class);
    }
}

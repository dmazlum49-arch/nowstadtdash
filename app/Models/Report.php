<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $guarded = [];

    public function reportType()
    {
        return $this->belongsTo(ReportType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

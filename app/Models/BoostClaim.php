<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoostClaim extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function boost()
    {
        return $this->belongsTo(Boost::class, 'boost_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function qrScanLog()
    {
        return $this->belongsTo(QrScanLog::class, 'qr_scan_log_id');
    }
}

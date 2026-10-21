<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getLogoAttribute($val)
    {
        return checkFileExist($val, 'no_user_image');
    }

    public function getBannerImageAttribute($val)
    {
        return empty($val) ? null : checkFileExist($val, '');
    }
    
    public function getCompanyRegistrationAttribute($val)
    {
        return empty($val) ? null : checkFileExist($val, '');
    }

    public function category()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function timings()
    {
        return $this->hasMany(BusinessTiming::class);
    }

    public function businessImages()
    {
        return $this->hasMany(BusinessImages::class, 'business_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function changeBusinessQrCode()
    {
        return $this->hasMany(ChangeBusinessQrCode::class, 'business_id');
    }
    public function boost()
    {
        return $this->hasMany(Boost::class, 'business_id');
    }

    public function activeBoost()
    {
        $now = \Carbon\Carbon::now('Europe/Vienna');
        $currentDateTime = $now->format('Y-m-d H:i:s');

        return $this->hasOne(Boost::class, 'business_id')
            ->whereRaw("CONCAT(start_date, ' ', start_time) <= ?", [$currentDateTime])
            ->whereRaw("CONCAT(end_date, ' ', end_time) >= ?", [$currentDateTime])
            ->latest('id');
    }

    public function getBoostData($userId = null)
    {
        $activeBoost = $this->relationLoaded('activeBoost') ? $this->activeBoost : $this->activeBoost()->first();

        if (!$activeBoost) {
            return [
                'is_boost' => false,
                'boost_badge' => null,
                'boost_multiplier' => null,
                'is_boost_claimed' => false,
            ];
        }

        $isClaimed = false;
        if ($userId) {
            $isClaimed = BoostClaim::where('boost_id', $activeBoost->id)
                ->where('user_id', $userId)
                ->exists();
        }

        return [
            'is_boost' => true,
            'boost_badge' => $activeBoost->badge_label,
            'boost_multiplier' => (int) ($activeBoost->multiplier ?? 2),
            'is_boost_claimed' => $isClaimed,
        ];
    }


    public function stampCards()
    {
        return $this->hasMany(RewardStampCard::class, 'business_id');
    }

    public function nowisPoints()
    {
        return $this->hasMany(RewardNowisPoint::class, 'business_id');
    }

    public function ratings()
    {
        return $this->hasMany(BusinessRating::class);
    }

    public function getDailyCode($force = false)
    {
        $today = now()->startOfDay();

        if ($force || !$this->daily_code || !$this->daily_code_updated_at || $this->daily_code_updated_at < $today) {
            $newCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $this->update([
                'daily_code' => $newCode,
                'daily_code_updated_at' => now(),
            ]);
            return $newCode;
        }

        return $this->daily_code;
    }
}

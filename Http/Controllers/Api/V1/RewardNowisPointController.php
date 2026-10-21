<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Mail\NowisPointQrMail;
use App\Models\RewardNowisPoint;
use App\Models\RewardNowisPointRedeem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RewardNowisPointController extends ResponseController
{
    public function createEditNowisPoint(Request $request)
    {
        $userId = $request->user()->id;
        $this->directValidation([
            'business_id' => 'required|exists:businesses,id',
            // 'logo' => 'nullable|image',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nowis_points' => 'nullable',
            'date' => 'nullable',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'valid_title' => 'nullable',
            'nowis_point_id' => ['nullable', 'exists:reward_nowis_points,id'],
        ]);

        if ($request->filled('nowis_point_id')) {
            $nowisPoint = RewardNowisPoint::findOrFail($request->nowis_point_id);
            $nowisPoint->update([
                'business_id' => $request->business_id,
                'title' => $request->title,
                'description' => $request->description,
                'nowis_points' => $request->nowis_points,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'valid_title' => $request->valid_title
            ]);

            $message = __('api.success_nowis_point_updated');
        } else {
            $nowisPoint = RewardNowisPoint::create([
                'user_id' => $userId,
                'business_id' => $request->business_id,
                'title' => $request->title,
                'description' => $request->description,
                'nowis_points' => $request->nowis_points,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'valid_title' => $request->valid_title
            ]);

            $message = __('api.success_nowis_point_created');
        }

        // if ($request->hasFile('logo')) {
        //     $nowisPointImagePath = upload_file('logo', 'nowis_point_images');

        //     if ($nowisPoint->logo) {
        //         un_link_file($nowisPoint->logo);
        //     }

        //     $nowisPoint->update(['logo' => $nowisPointImagePath]);
        // }

        return ResponseHelper::send(200, $message, [
            'id' => $nowisPoint->id,
            'title' => $nowisPoint->title,
            'description' => $nowisPoint->description,
            'nowis_points' => $nowisPoint->nowis_points,
            'business_name' => $nowisPoint->business->business_name ?? null,
            // 'logo' => $nowisPoint->logo ? url($nowisPoint->logo) : null,
            'logo' => $nowisPoint->business->logo ? url($nowisPoint->business->logo) : null,

            'date' => $nowisPoint->date,
            'start_time' => $nowisPoint->start_time,
            'end_time' => $nowisPoint->end_time,
            'is_available' => $nowisPoint->is_available,
            'valid_title' => $nowisPoint->valid_title,
            'created_at' => $nowisPoint->created_at,
            'updated_at' => $nowisPoint->updated_at,
        ]);
    }

    public function mynowisPointList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $business_id = $request->business_id;

        $nowisPoint = RewardNowisPoint::with('business')
            ->where('user_id', $userId)
            ->where('business_id', $business_id)
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();
        $filterNowisPoint = $nowisPoint->map(function ($nowisPoint) {
            return [
                'id' => $nowisPoint->id,
                'title' => $nowisPoint->title,
                'description' => $nowisPoint->description,
                'nowis_points' => $nowisPoint->nowis_points,
                'business_name' => $nowisPoint->business->business_name ?? null,
                // 'logo' => $nowisPoint->logo ? url($nowisPoint->logo) : null,
                'logo' => $nowisPoint->business->logo ? url($nowisPoint->business->logo) : null,
                'date' => $nowisPoint->date,
                'start_time' => $nowisPoint->start_time,
                'end_time' => $nowisPoint->end_time,
                'is_available' => $nowisPoint->is_available,
                'valid_title' => $nowisPoint->valid_title,
                'created_at' => $nowisPoint->created_at,
                'updated_at' => $nowisPoint->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success_nowis_point_retrieved'), $filterNowisPoint);
    }

    public function nowisPointDetails(Request $request)
    {
        $this->directValidation([
            'nowis_point_id' => 'required|exists:reward_nowis_points,id',
        ]);

        $nowisPoint = RewardNowisPoint::with('business')->findOrFail($request->nowis_point_id);
        $user = $request->user();

        $redeems = RewardNowisPointRedeem::where('user_id', $user->id)
            ->where('nowis_point_id', $nowisPoint->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseHelper::send(200, __('api.success_nowis_point_retrieved'), [
            'id' => $nowisPoint->id,
            'title' => $nowisPoint->title,
            'description' => $nowisPoint->description,
            'nowis_points' => $nowisPoint->nowis_points,
            'business_name' => $nowisPoint->business->business_name ?? null,
            // 'logo' => $nowisPoint->logo ? url($nowisPoint->logo) : null,
            'logo' => $nowisPoint->business->logo ? url($nowisPoint->business->logo) : null,
            'date' => $nowisPoint->date,
            'start_time' => $nowisPoint->start_time,
            'end_time' => $nowisPoint->end_time,
            'is_available' => $nowisPoint->is_available,
            'is_redeem' => $redeems->isNotEmpty(),
            'valid_title' => $nowisPoint->valid_title,
            'created_at' => $nowisPoint->created_at,
            'updated_at' => $nowisPoint->updated_at,
        ]);
    }

    public function deleteNowisPoint(Request $request)
    {
        $this->directValidation([
            'nowis_point_id' => 'required|exists:reward_nowis_points,id',
        ]);

        $nowisPoint = RewardNowisPoint::findOrFail($request->nowis_point_id);
        if ($nowisPoint->user_id !== $request->user()->id) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        if ($nowisPoint->logo) {
            un_link_file($nowisPoint->logo);
        }

        $nowisPoint->delete();

        return ResponseHelper::send(200, __('api.success_nowis_point_deleted'));
    }

// public function allNowisPointList(Request $request)
//     {
//         $user = $request->user();
//         $limit = $request->limit ?? 10;
//         $offset = $request->offset ?? 0;

//         $availablePoints = $user->nowis_points;

//         $redeems = RewardNowisPointRedeem::where('user_id', $user->id)
//             ->orderBy('created_at', 'desc')
//             ->get()
//             ->groupBy('nowis_point_id'); // group by voucher ID

//         $activeRedeemedIds = [];
//         foreach ($redeems as $voucherId => $redeemGroup) {
//             $latestRedeem = $redeemGroup->first(); // since sorted desc
//             if ($latestRedeem->created_at->greaterThanOrEqualTo(now()->subDays(7))) {
//                 $activeRedeemedIds[] = $voucherId;
//             }
//         }

//         $nowisPoints = RewardNowisPoint::with('business')
//             ->latest()
//             ->get();

//         $sortedNowisPoints = $nowisPoints->sortBy(function ($voucher) use ($activeRedeemedIds) {
//             return in_array($voucher->id, $activeRedeemedIds) ? 1 : 0;
//         })->values();

//         $paginatedNowisPoints = $sortedNowisPoints->slice($offset, $limit);

//         $filterNowisPoint = $paginatedNowisPoints->map(function ($nowisPoint) use ($activeRedeemedIds, $redeems) {
//             $latestRedeem = $redeems->has($nowisPoint->id) ? $redeems[$nowisPoint->id]->first() : null;
//             $nextRedeemAvailableAt = null;

//             if ($latestRedeem && $latestRedeem->created_at->greaterThanOrEqualTo(now()->subDays(7))) {
//                 $nextRedeemAvailableAt = $latestRedeem->created_at->addDays(7)->toDateTimeString();
//             }

//             return [
//                 'id' => $nowisPoint->id,
//                 'title' => $nowisPoint->title,
//                 'description' => $nowisPoint->description,
//                 'nowis_points' => $nowisPoint->nowis_points,
//                 'business_name' => $nowisPoint->business->business_name ?? null,
//                 'logo' => $nowisPoint->logo ? url($nowisPoint->logo) : null,
//                 'is_redeem' => in_array($nowisPoint->id, $activeRedeemedIds),
//                 'next_redeem_available_at' => $nextRedeemAvailableAt,
//                 'date' => $nowisPoint->date,
//                 'start_time' => $nowisPoint->start_time,
//                 'end_time' => $nowisPoint->end_time,
//                 'valid_title' => $nowisPoint->valid_title,
//                 'created_at' => $nowisPoint->created_at,
//                 'updated_at' => $nowisPoint->updated_at,
//             ];
//         });

//         return ResponseHelper::send(200, __('api.success_nowis_point_retrieved'), [
//             'available_nowis_points' => $availablePoints,
//             'rewards' => $filterNowisPoint,
//         ]);
//     }


public function allNowisPointList(Request $request)
    {
        $user = $request->user();
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $availablePoints = $user->nowis_points;

        $redeemedIds = RewardNowisPointRedeem::where('user_id', $user->id)
            ->pluck('nowis_point_id')
            ->toArray();

        $nowisPoints = RewardNowisPoint::with('business')
            ->whereNotIn('id', $redeemedIds)
            ->latest()
            ->get();

        $paginatedNowisPoints = $nowisPoints->slice($offset, $limit);

        $filterNowisPoint = $paginatedNowisPoints->map(function ($nowisPoint) {
            return [
                'id' => $nowisPoint->id,
                'title' => $nowisPoint->title,
                'description' => $nowisPoint->description,
                'nowis_points' => $nowisPoint->nowis_points,
                'business_name' => $nowisPoint->business->business_name ?? null,
                // 'logo' => $nowisPoint->logo ? url($nowisPoint->logo) : null,
                'logo' => $nowisPoint->business->logo ? url($nowisPoint->business->logo) : null,
                'is_redeem' => false,
                'next_redeem_available_at' => null,
                'date' => $nowisPoint->date,
                'start_time' => $nowisPoint->start_time,
                'end_time' => $nowisPoint->end_time,
                'is_available' => $nowisPoint->is_available,
                'valid_title' => $nowisPoint->valid_title,
                'created_at' => $nowisPoint->created_at,
                'updated_at' => $nowisPoint->updated_at,
            ];
        })->values();

        return ResponseHelper::send(200, __('api.success_nowis_point_retrieved'), [
            'available_nowis_points' => $availablePoints,
            'rewards' => $filterNowisPoint,
        ]);
    }



    // public function redeemNowisPoint(Request $request)
    // {
    //     $this->directValidation([
    //         'nowis_point_id' => ['required', 'exists:reward_nowis_points,id'],
    //     ]);

    //     $user = $request->user();
    //     $reward = RewardNowisPoint::findOrFail($request->nowis_point_id);

    //     if ($user->nowis_points < $reward->nowis_points) {
    //         return ResponseHelper::send(400, 'Insufficient points to redeem this reward.');
    //     }

    //     $user->nowis_points -= $reward->nowis_points;
    //     $user->save();

    //     $redeem = RewardNowisPointRedeem::create([
    //         'user_id' => $user->id,
    //         'nowis_point_id' => $reward->id,
    //         'expires_at' => now()->addMinutes(2),
    //     ]);

    //     return ResponseHelper::send(200, 'Reward redeem initiated.', [
    //         'id' => $reward->id,
    //         'title' => $reward->title,
    //         'description' => $reward->description,
    //         'logo' => $reward->logo ? url($reward->logo) : null,
    //         'expires_at' => $redeem->expires_at->toDateTimeString(),
    //     ]);
    // }

    public function redeemNowisPoint(Request $request)
    {
        $this->directValidation([
            'nowis_point_id' => ['required', 'exists:reward_nowis_points,id'],
        ]);

        $user = $request->user();
        $reward = RewardNowisPoint::findOrFail($request->nowis_point_id);
        
        if (!$reward->is_available) {
            return ResponseHelper::send(400, 'This reward is not available at the moment.');
        }

        $alreadyRedeemed = RewardNowisPointRedeem::where('user_id', $user->id)
            ->where('nowis_point_id', $reward->id)
            ->exists();

        if ($alreadyRedeemed) {
            return ResponseHelper::send(400, 'You have already redeemed this reward.');
        }

        if ($user->nowis_points < $reward->nowis_points) {
            return ResponseHelper::send(400, 'Insufficient points to redeem this reward.');
        }

        $user->nowis_points -= $reward->nowis_points;
        $user->save();

        $redeem = RewardNowisPointRedeem::create([
            'user_id' => $user->id,
            'nowis_point_id' => $reward->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        return ResponseHelper::send(200, 'Reward redeem initiated.', [
            'id' => $reward->id,
            'title' => $reward->title,
            'description' => $reward->description,
            // 'logo' => $reward->logo ? url($reward->logo) : null,
            'logo' => $reward->business->logo ? url($reward->business->logo) : null,
            'expires_at' => $redeem->expires_at->toDateTimeString(),
        ]);
    }

}

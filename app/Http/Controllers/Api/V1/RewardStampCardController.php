<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Models\BusinessStampCard;
use App\Models\RewardStampCard;
use App\Models\RewardStampCardRedeem;
use Illuminate\Http\Request;

class RewardStampCardController extends ResponseController
{
    public function createEditStampCard(Request $request)
    {
        $userId = $request->user()->id;
        $limits = getUserPlanLimits($userId);

// ❌ If stamp card is not allowed for this plan
if (empty($limits['stamp_card']) || $limits['stamp_card'] === false) {
    return ResponseHelper::send(403, 'Your subscription plan does not allow stamp card creation.');
}
        $this->directValidation([
            'business_id' => 'required|exists:businesses,id',
            'logo' => 'nullable|image',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stamp_card_id' => ['nullable', 'exists:reward_stamp_cards,id'],
        ]);

        if ($request->filled('stamp_card_id')) {
            $stampCard = RewardStampCard::findOrFail($request->stamp_card_id);
            $stampCard->update([
                'business_id' => $request->business_id,
                'title' => $request->title,
                'description' => $request->description,
            ]);

            $message = __('api.success_stamp_card_updated');
        } else {
            $stampCard = RewardStampCard::create([
                'user_id' => $userId,
                'business_id' => $request->business_id,
                'title' => $request->title,
                'description' => $request->description,
            ]);

            $message = __('api.success_stamp_card_created');
        }

        if ($request->hasFile('logo')) {
            $stampCardImagePath = upload_file('logo', 'stamp_card_images');

            if ($stampCard->logo) {
                un_link_file($stampCard->logo);
            }

            $stampCard->update(['logo' => $stampCardImagePath]);
        }

        return ResponseHelper::send(200, $message, [
            'id' => $stampCard->id,
            'title' => $stampCard->title,
            'description' => $stampCard->description,
            'business_name' => $stampCard->business->business_name ?? null,
            'logo' => $stampCard->logo ? url($stampCard->logo) : null,
            'created_at' => $stampCard->created_at,
            'updated_at' => $stampCard->updated_at,
        ]);
    }

    public function myStampCardList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $business_id = $request->business_id;

        $stampCard = RewardStampCard::with('business')
            ->where('user_id', $userId)
            ->where('business_id', $business_id)
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();
        $filterStampCard = $stampCard->map(function ($stampCard) {
            return [
                'id' => $stampCard->id,
                'title' => $stampCard->title,
                'description' => $stampCard->description,
                'business_name' => $stampCard->business->business_name ?? null,
                'logo' => $stampCard->logo ? url($stampCard->logo) : null,
                'created_at' => $stampCard->created_at,
                'updated_at' => $stampCard->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success_stamp_card_retrieved'), $filterStampCard);
    }

    public function stampCardDetails(Request $request)
    {
        $this->directValidation([
            'stamp_card_id' => 'required|exists:reward_stamp_cards,id',
        ]);

        $stampCard = RewardStampCard::with('business')->findOrFail($request->stamp_card_id);

        return ResponseHelper::send(200, __('api.success_stamp_card_retrieved'), [
            'id' => $stampCard->id,
            'title' => $stampCard->title,
            'description' => $stampCard->description,
            'business_name' => $stampCard->business->business_name ?? null,
            'logo' => $stampCard->logo ? url($stampCard->logo) : null,
            'created_at' => $stampCard->created_at,
            'updated_at' => $stampCard->updated_at,
        ]);
    }

    public function deleteStampCard(Request $request)
    {
        $this->directValidation([
            'stamp_card_id' => 'required|exists:reward_stamp_cards,id',
        ]);

        $stampCard = RewardStampCard::findOrFail($request->stamp_card_id);
        if ($stampCard->user_id !== $request->user()->id) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        if ($stampCard->logo) {
            un_link_file($stampCard->logo);
        }

        $stampCard->delete();

        return ResponseHelper::send(200, __('api.success_stamp_card_deleted'));
    }

    public function businessWithStampCard(Request $request)
    {
        $user = $request->user();
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $businessStampCards = BusinessStampCard::where('user_id', $user->id)
            ->with(['business'])
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();


        $data = $businessStampCards->map(function ($item) {
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'business_id' => $item->business_id,
                'stamp_cards' => $item->stamp_cards,
                'business_name' => $item->business->business_name ?? null,
                'business_category_name' => $item->business->category->business_category ?? null,
                'logo' => $item->business->logo ? url($item->business->logo) : null,
                'banner_image' => $item->business->banner_image ? url($item->business->banner_image) : null,
                'city' => $item->business->city,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success'), $data);
    }

    public function allStampCardList(Request $request)
    {
        $user = $request->user();
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $stampCard = RewardStampCard::with('business')
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();
        $redeemedIds = RewardStampCardRedeem::where('user_id', $user->id)
            ->pluck('stamp_card_id')
            ->toArray();
        $filterStampCard = $stampCard->map(function ($stampCard) use ($redeemedIds) {
            return [
                'id' => $stampCard->id,
                'title' => $stampCard->title,
                'description' => $stampCard->description,
                'business_name' => $stampCard->business->business_name ?? null,
                'business_id' => $stampCard->business->id ?? null,
                'logo' => $stampCard->logo ? url($stampCard->logo) : null,
                'is_redeem' => in_array($stampCard->id, $redeemedIds),
                'created_at' => $stampCard->created_at,
                'updated_at' => $stampCard->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success_stamp_card_retrieved'), $filterStampCard);
    }

    public function redeemStampCard(Request $request)
    {
        $this->directValidation([
            'stamp_card_id' => ['required', 'exists:reward_stamp_cards,id'],
            'business_id' => ['required', 'exists:businesses,id'],
        ]);

        $user = $request->user();
        $reward = RewardStampCard::findOrFail($request->stamp_card_id);

        $businessStampCard = BusinessStampCard::where('user_id', $user->id)
            ->where('business_id', $request->business_id)
            ->first();

        if (!$businessStampCard) {
            return ResponseHelper::send(404, 'No stamp cards found for this business.');
        }

        if ($businessStampCard->stamp_cards < 10) {
            return ResponseHelper::send(400, 'Insufficient stamp cards to redeem this reward.');
        }

        $businessStampCard->stamp_cards -= 10;
        $businessStampCard->save();

        $redeem = RewardStampCardRedeem::create([
            'user_id' => $user->id,
            'stamp_card_id' => $reward->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        return ResponseHelper::send(200, 'Reward redeem initiated.', [
            'id' => $reward->id,
            'title' => $reward->title,
            'description' => $reward->description,
            'logo' => $reward->logo ? url($reward->logo) : null,
            'expires_at' => $redeem->expires_at->toDateTimeString(),
            'remaining_stamp_cards' => $businessStampCard->stamp_cards,
        ]);
    }
}

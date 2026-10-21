<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Models\Business;
use App\Models\BusinessRating;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessRatingController extends ResponseController
{
    public function rateBusiness(Request $request)
    {
        $userId = $request->user()->id;
        
        $this->directValidation([
            'business_id' => 'required|exists:businesses,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        $businessId = $request->business_id;
        
        // Moderation check
        $moderationResult = ['flagged' => false];
        if ($request->filled('review')) {
            $moderationService = app(ContentModerationService::class);
            $moderationResult = $moderationService->checkContent($request->review);
        }

        $moderationData = [
            'moderation_status' => $moderationResult['flagged'] ? 'flagged' : 'approved',
            'moderation_details' => json_encode($moderationResult),
            'ai_flagged_at' => $moderationResult['flagged'] ? now() : null,
        ];

        // Check if user already rated
        $rating = BusinessRating::updateOrCreate(
            [
                'user_id' => $userId,
                'business_id' => $businessId,
            ],
            array_merge([
                'rating' => $request->rating,
                'review' => $request->review,
            ], $moderationData)
        );

        if ($moderationResult['flagged']) {
            return ResponseHelper::send(422, __('We detected inappropriate language in your review. It has been flagged for review.'));
        }

        return ResponseHelper::send(200, __('api.success_rating_submitted'), [
            'rating' => $rating
        ]);
    }

    public function deleteBusinessRating(Request $request)
    {
        $this->directValidation([
            'rating_id' => 'required|exists:business_ratings,id',
        ]);

        $rating = BusinessRating::where('id', $request->rating_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $rating->forceDelete();

        return ResponseHelper::send(200, __('api.success_rating_deleted'));
    }
}

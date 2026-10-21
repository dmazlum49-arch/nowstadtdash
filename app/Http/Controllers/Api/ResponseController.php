<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Business;
use App\Models\Event;
use App\Models\GeneralSetting;
use App\Models\Post;
use App\Models\Help;
use App\Models\Report;
use App\Models\RewardNowisPoint;
use App\Models\RewardStampCard;
use App\Models\ThreadMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ResponseController extends Controller
{
    public $errors;
    protected $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->errors = null;
        $this->validationService = $validationService;
    }
    public function directValidation(array $rules, array $messages = [], $data = null)
    {
        $data = $data ?? request()->all(); // Use null coalescing to get data.
        $validator = Validator::make($data, $rules, $messages);

        // Check if validation fails
        if ($validator->fails()) {
            $this->errors = $validator->errors(); // Capture all error messages
            return $this->sendError(); // Call sendError to handle the response
        }

        // Return validated data if successful.
        return $validator->validated();
    }

    public function sendError($message = null, $array = true)
    {
        // Use the captured errors if any
        $message = $this->errors ? $this->errors->first() : ($message ?: __('api.err_something_went_wrong'));

        // Send the response with the error messages
        return ResponseHelper::send(412, $message, ($array) ? [] : new \stdClass());
    }

    public function sendResponse($status, $message, $result = null, $extra = null)
    {
        return ResponseHelper::send($status, $message, $result, $extra);
    }

    public function get_user_data($token = null, $myBusinessCount = 0, $myPostCount = 0, $myEventCount = 0, $myNowisPoints = 0)
    {
        // Retrieve only the required fields (signup fields) from the user
        $support_email = GeneralSetting::where('unique_name', 'SUPPORT_EMAIL')->first()->value ?? '';
        $user_data = User::where('id', Auth::id())->Details()->first();
        $activeSubscription = Transaction::join('plans', 'transactions.plan_id', '=', 'plans.id')
            ->where('transactions.user_id', $user_data->id)
            ->where('transactions.status', 'active')
            ->latest('transactions.id')
            ->select('plans.id','plans.name as plan_name', 'plans.type', 'transactions.next_billing_date')
            ->first();

        $business_count = Business::where('user_id', $user_data->id)->count();
        $post_count = Post::where('user_id', $user_data->id)->count();
        $event_count = Event::where('user_id', $user_data->id)->count();
        $nowis_points = RewardNowisPoint::where('user_id', $user_data->id)->count();
        $stamp_count = RewardStampCard::where('user_id', $user_data->id)->count();

        $unread_message_count = ThreadMessage::where('receiver_id', $user_data->id)
            ->where('status', 'unread')
            ->count();

        $new_report_count = 0;
        if ($user_data) {
            $newReportCountQuery = Report::where('status', 'approved')
                ->where('user_id', '!=', $user_data->id);

            if ($user_data->report_last_seen_at) {
                $newReportCountQuery->where('created_at', '>', $user_data->report_last_seen_at);
            }

            $new_report_count = $newReportCountQuery->count();
        }

        return [
            'id' => $user_data->id,
            'name' => $user_data->name ?? '',
            'email' => $user_data->email ?? '',
            'country_code' => $user_data->country_code,
            'mobile' => $user_data->mobile,
            'profile_image' => $user_data->profile_image,
            'type' => $user_data->type,
            'location' => $user_data->location ?? '',
            'notification' => $user_data->notification,
            'referral_code'  => $user_data->referral_code,
            'last_activity' => $user_data->last_activity,
            "last_seen" => lastSeenShort($user_data->last_activity),
            'support_email' => $support_email,
            'token' => $token ?? '',
            'is_verified' => $user_data->is_verified,
            'dark_theme' => $user_data->dark_theme,
            'is_two_factor_authentication' => $user_data->is_two_factor_authentication,
            'stripe_customer_id' => $user_data->stripe_customer_id,
            'is_subscription_active' => $activeSubscription ? true : false,
            'unread_message_count' => $unread_message_count,
            'new_report_count' => $new_report_count,
            'is_community_guideline_accepted' => $user_data->is_community_guideline_accepted ?? '0',
            'counts' => [
                'my_business' => $myBusinessCount,
                'my_post' => $myPostCount,
                'my_event' => $event_count,
                'my_nowis' => $user_data->nowis_points ?? 0,
            ],
            'create' => [
                'business_count' => $business_count,
                'post_count' => $post_count,
                'event_count' => $event_count,
                'nowis_count' => $nowis_points,
                'stamp_count' => $stamp_count,
            ],
            'active_subscription' => $activeSubscription,
        ];
    }
}

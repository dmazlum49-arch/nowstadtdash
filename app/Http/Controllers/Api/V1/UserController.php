<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Mail\UpdateEmailSendOtpMail;
use App\Models\BlockUser;
use App\Models\City;
use App\Models\Device;
use App\Models\FamilyMember;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserEmailLog;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends ResponseController
{
    protected $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function getProfile(Request $request)
    {
        return ResponseHelper::send(200, __('api.success'), $this->get_user_data());
    }

    public function editProfile(Request $request)
    {
        $userId = $request->user()->id;
        $this->directValidation([
            'name' => ['required', 'string', 'max:100'],
            'country_code' => ['nullable', 'max:10'],
            'mobile' => ['nullable', 'max:15'],
            'profile_image' => ['nullable', 'image'],

        ]);
        $user = $request->user();
        if ($request->hasFile('profile_image')) {
            $imagePath = upload_file('profile_image', 'profile_images');

            if ($user->profile_image) {
                un_link_file($user->profile_image);
            }

            $user->profile_image = $imagePath;
        }
        $user->update([
            'name' => $request->input('name'),
            'country_code' => $request->input('country_code'),
            'mobile' => $request->input('mobile'),
        ]);
        return ResponseHelper::send(200, __('api.success_profile_update'), $this->get_user_data());
    }

    public function changePassword(Request $request)
    {
        $this->directValidation([
            'old_password' => $this->validationService->passwordRules(),
            'new_password' => $this->validationService->passwordRules(),
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        // Password change logic
        $user = $request->user();
        if (Hash::check($request->old_password, $user->password)) {
            $user->update(['password' => $request->new_password]);
            return ResponseHelper::send(200, __('api.success_change_password'), $this->get_user_data());
        } else {
            return ResponseHelper::send(400, __('api.error_invalid_password'));
        }
    }

    public function updateEmail(Request $request)
    {
        $userId = $request->user()->id;

        $this->directValidation([
            'email' => $this->validationService->emailUniqueRules($userId),
        ]);

        $email = $request->email;

        $otp = app()->environment('local') ? 123456 : rand(111111, 999999);

        $userEmailLog = UserEmailLog::updateOrCreate(
            ['email' => $email],
            [
                'email' => $email,
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ]
        );

        if ($userEmailLog) {
            Mail::to($email)->send(new UpdateEmailSendOtpMail($otp));

            return ResponseHelper::send(200, __('api.Otp_sent'), $this->get_user_data());
        }

        return ResponseHelper::send(500, __('api.Failed_to_process_request'));
    }

    public function verifyEmailOtp(Request $request)
    {
        $this->validate($request, [
            'email' => $this->validationService->emailLogsExistsRules(),
            'otp' => ['required', 'numeric'],
        ]);

        $emailLog = UserEmailLog::where('email', $request->email)->first();

        if (!$emailLog || $emailLog->otp != $request->otp) {
            return ResponseHelper::send(400, __('api.error_invalid_otp'));
        }

        if (now()->greaterThan($emailLog->otp_expires_at)) {
            return ResponseHelper::send(400, __('api.error_otp_expired'));
        }

        $user = $request->user();
        $user->email = $request->email;
        $user->save();

        // Optional: Invalidate OTP after use
        $emailLog->delete();

        return ResponseHelper::send(200, __('api.success_email_updated'), $this->get_user_data());
    }

    public function updateMobile(Request $request)
    {
        $userId = $request->user()->id;
        $this->directValidation([
            'country_code' => ['required', 'max:10'],
            'mobile' => $this->validationService->mobileRules($request, $userId),
        ]);

        $user = $request->user();
        $user->update(['country_code' => $request->country_code, 'mobile' => $request->mobile]);

        return ResponseHelper::send(200, __('api.success_profile_update'), $this->get_user_data());
    }

    public function notificationFlag(Request $request)
    {
        $this->directValidation([
            'notification_enabled' => ['required', 'in:yes,no'],
        ]);

        $user = $request->user();
        $user->update(['notification' => $request->notification_enabled]);

        return ResponseHelper::send(200, __('api.success_profile_update'), $this->get_user_data());
    }

    public function notificationList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $notifications = Notification::where(['user_id' => $userId])->latest()->limit($limit)->offset($offset)->get();
        Notification::where(['user_id' => $userId])->latest()->limit($limit)->offset($offset)->update(['read' => 1]);
        return ResponseHelper::send(200, __('api.success'), $notifications);
    }

    public function logout(Request $request)
    {
        Device::where('user_id', $request->user()->id)->delete();
        $request->user()->currentAccessToken()->delete();
        return ResponseHelper::send(200, __('api.success_logout'));
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        $user->posts()->delete();
        $user->businesses()->delete();
        $user->events()->delete();
        Device::where('user_id', $request->user()->id)->delete();
        $request->user()->currentAccessToken()->delete();
        $user->forceDelete();
        return ResponseHelper::send(200, __('api.success_delete_account'));
    }

    public function getUserList(Request $request)
    {
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $search = $request->input('search', '');
        $family_id = $request->input('family_id', '');
        $userId = $request->user()->id;

        $members = FamilyMember::where('family_id', $family_id)->get()->pluck('member_id')->toArray();
        $query = User::where('id', '!=', $userId)->Type()->Active();
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('country_code', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere(DB::raw('CONCAT_WS(" ", country_code, mobile)'), 'like', "%{$search}%");
            });
        }
        $data = $query->whereNotIn('id', $members)->List()
            ->limit($limit)
            ->offset($offset)
            ->latest()
            ->get();

        return ResponseHelper::send(200, __('api.success'), $data);
    }

    public function blockList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $blockedUsers = BlockUser::with('blockedUser')
            ->where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();

        $response = $blockedUsers->map(function ($block) {
            return [
                'id' => $block->block_user_id,
                'name' => $block->blockedUser->name ?? null,
                'profile_image' => $block->blockedUser->profile_image ? url($block->blockedUser->profile_image) : null,
            ];
        });

        return ResponseHelper::send(200, __('api.success'), $response);
    }

    public function changeLanguage(Request $request)
    {
        $this->directValidation([
            'language' => ['required', 'in:en,de']
        ]);

        $user = $request->user();
        $user->language = $request->language;
        $user->save();

        return ResponseHelper::send(200, __('api.success_language_changed'), [
            'language' => $user->language
        ]);
    }
    
    public function getCity(Request $request)
    {
        $language = $request->header('Accept-Language') ?? 'en';
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $query = City::where('status', 'active')
            ->where('language', $language);


        $cities = $query->select('id', 'city_name', 'latitude', 'longitude')->latest()->limit($limit)->offset($offset)->get();

        return ResponseHelper::send(200, __('api.success'), $cities);
    }

    public function twoFactorAuthenticationToggle(Request $request)
    {
        $this->directValidation([
            'is_two_factor_authentication' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->is_two_factor_authentication = $request->is_two_factor_authentication;
        $user->save();

        return ResponseHelper::send(200, __('api.success_profile_update'), $this->get_user_data());
    }

    public function darkThemeFlag(Request $request)
    {
        $this->directValidation([
            'dark_theme_enabled' => ['required', 'in:yes,no,system'],
        ]);

        $user = $request->user();
        $user->update(['dark_theme' => $request->dark_theme_enabled]);

        return ResponseHelper::send(200, __('api.success_profile_update'), $this->get_user_data());
    }

    public function communityGuidelineFlag(Request $request)
    {
        $user = $request->user();
        $user->update(['is_community_guideline_accepted' => '1']);

        return ResponseHelper::send(200, __('api.success_profile_update'), $this->get_user_data());
    }
}

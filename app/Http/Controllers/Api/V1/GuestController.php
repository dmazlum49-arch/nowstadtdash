<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Mail\User_Password_Send_OTP_Mail;
use App\Models\Business;
use App\Models\Content;
use App\Models\Event;
use App\Models\Post;
use App\Models\UserEmailLog;
use App\Models\VersionHistory;
use App\Services\TwilioService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Str;

class GuestController extends ResponseController
{
    protected $validationService;
    protected $twilioService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
        $this->twilioService = new TwilioService();
    }

    public function signup(Request $request)
    {
        // Validate request input
        if (
            $this->directValidation([
                'name' => ['required', 'max:100'],
                'email' => $this->validationService->emailUniqueRules(),
                'country_code' => ['nullable', 'max:10'],
                'mobile' => $this->validationService->mobileRules($request),
                'password' => $this->validationService->passwordRules(),
                'confirm_password' => ['required', 'same:password'],
                'location' => ['required', 'max:255'],
                'latitude' => ['required'],
                'longitude' => ['required'],
                'type' => ['required'],
                'referral_code' => ['nullable', 'max:50'],
                'device_id' => ['required', 'max:255'],
                'device_token' => ['required', 'max:255'],
                'device_type' => ['required', 'in:android,ios'],
                'otp' => ['required', 'numeric'],
            ], [
                'email.unique' => __('api.error_already_taken', ['attribute' => 'email']),
                'mobile.unique' => __('api.error_already_taken', ['attribute' => 'mobile']),
                'password.regex' => __('api.error_password'),
            ])
        )
            ;

        $userEmailLog = UserEmailLog::where('email', $request->email)->first();

        if (!$userEmailLog || $userEmailLog->otp != $request->otp || now()->greaterThan($userEmailLog->otp_expires_at)) {
            return ResponseHelper::send(400, __('api.error_invalid_otp'));
        }

        // If OTP is correct, delete it so it can't be reused
        $userEmailLog->delete();

        // generate stripe customer id
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $customer = $stripe->customers->create([
            'email' => $request->email,
            'name' => $request->name,
        ]);

        $referralCode = $this->generateUniqueReferralCode();
        // User creation and authentication logic
        $user = User::create([
            'name' => $request->name,
            'username' => generateUsername($request->name),
            'email' => $request->email,
            'password' => $request->password,
            'country_code' => $request->country_code,
            'mobile' => $request->mobile,
            'type' => $request->type,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'referral_code' => $referralCode,
            'stripe_customer_id' => $customer->id,
            'language' => $request->header('Accept-Language', 'de')
        ]);

        if ($request->referral_code) {
            $referrer = User::where('referral_code', $request->referral_code)->first();

            if ($referrer) {
                // Add 20 points to both users
                $referrer->increment('nowis_points', 20);
                $user->increment('nowis_points', 20);
            }
        }

        if ($user) {
            // Log in the user after signup (Authenticate user)
            Auth::login($user);
            User::AddTokenToUser();

            // Count
                $myBusinessCount = $user ? Business::where('user_id', $user->id)->count() : 0;
                $myPostCount = $user ? Post::where('user_id', $user->id)->count() : 0;
                $myEventCount = $user ? Event::where('user_id', $user->id)->count() : 0;
                $myNowisPoints = $user ? $user->nowis_points : 0;

            // Generate token
            $token = $user->createCustomToken('API Token', 60);
            return ResponseHelper::send(200, __('api.success_register'), $this->get_user_data($token, $myBusinessCount, $myPostCount, $myEventCount, $myNowisPoints));
        }
        return ResponseHelper::send(412, __('api.error_something_wrong'));
    }

    private function generateUniqueReferralCode()
    {
        do {
            $code = strtoupper(Str::random(7));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    // public function login(Request $request)
    // {
    //     $this->directValidation([
    //         'email' => $this->validationService->emailExistsRules(),
    //         'password' => $this->validationService->passwordRules(),
    //         'type' => ['required','in:user,businessuser']
    //     ], [
    //         'email.exists' => __('api.error_not_exists', ['attribute' => 'email']),
    //         'password.regex' => __('api.error_password'),
    //     ]);

    //     // Authentication logic
    //     $credentials = $request->only('email', 'password') + ['type' => $request->type];
    //     if (Auth::attempt($credentials)) {
    //         $user = Auth::user();

    //         // Check if the user is active
    //         if ($user->status === 'active') {

    //             $language = $request->header('Accept-Language', 'en');

    //             $user->update(['language' => $language]);
    //             User::AddTokenToUser();

    //             // Count
    //             $myBusinessCount = $user ? Business::where('user_id', $user->id)->count() : 0;
    //             $myPostCount = $user ? Post::where('user_id', $user->id)->count() : 0;
    //             $myEventCount = $user ? Event::where('user_id', $user->id)->count() : 0;
    //             $myNowisPoints = $user ? $user->nowis_points : 0;

    //             // Generate API token
    //             $token = $user->createCustomToken('API Token', 60);
    //             return ResponseHelper::send(200, __('api.success_login'), $this->get_user_data($token, $myBusinessCount, $myPostCount, $myEventCount, $myNowisPoints));
    //         }

    //         // If the user is inactive, log out and return error response
    //         Auth::logout();
    //         return ResponseHelper::send(412, __('api.error_account_inactive'));
    //     }

    //     // If authentication fails, return invalid credentials response
    //     return ResponseHelper::send(412, __('api.error_invalid_credentials'));
    // }

    public function login(Request $request)
    {
        $this->directValidation([
            'email' => $this->validationService->emailExistsRules(),
            'password' => $this->validationService->passwordRules(),
            'type' => ['required', 'in:user,businessuser']
        ], [
            'email.exists' => __('api.error_not_exists', ['attribute' => 'email']),
            'password.regex' => __('api.error_password'),
        ]);

        // Credentials
        $credentials = $request->only('email', 'password') + ['type' => $request->type];

        // STEP 1: Validate credentials (DO NOT LOGIN YET)
        if (!Auth::validate($credentials)) {
            return ResponseHelper::send(412, __('api.error_invalid_credentials'));
        }

        // Credentials are valid → Fetch user
        $user = User::where('email', $request->email)
                    ->where('type', $request->type)
                    ->first();

        if (!$user || $user->status !== 'active') {
            return ResponseHelper::send(412, __('api.error_account_inactive'));
        }

        // ⭐ STEP 2: If 2FA is ON → Ask for OTP
        if ($user->is_two_factor_authentication == true) {
            return ResponseHelper::send(200, 'OTP_REQUIRED', [
                'requires_2fa' => true,
                'user_id' => $user->id
            ]);
        }

        // ⭐ STEP 3: 2FA OFF → Login normally
        Auth::login($user);

        // Update language
        $language = $request->header('Accept-Language', 'en');
        $user->update(['language' => $language]);

        User::AddTokenToUser();

        // Counts
        $myBusinessCount = Business::where('user_id', $user->id)->count();
        $myPostCount     = Post::where('user_id', $user->id)->count();
        $myEventCount    = Event::where('user_id', $user->id)->count();
        $myNowisPoints   = $user->nowis_points;

        // Token
        $token = $user->createCustomToken('API Token', 60);

        return ResponseHelper::send(200, __('api.success_login'),
            $this->get_user_data($token, $myBusinessCount, $myPostCount, $myEventCount, $myNowisPoints)
        );
    }

    public function verify2FAOtp(Request $request)
    {
        $this->directValidation([
            'user_id' => 'required|integer',
            'otp' => 'required'
        ]);

        $user = User::find($request->user_id);

        if (!$user) {
            return ResponseHelper::send(412, 'User not found');
        }

        // Verify OTP
        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        if (!$google2fa->verifyKey($user->google2fa_secret, $request->otp)) {
            return ResponseHelper::send(412, 'Invalid OTP');
        }

        // OTP correct → login user
        Auth::login($user);

        // Language update
        $language = $request->header('Accept-Language', 'en');
        $user->update(['language' => $language]);

        User::AddTokenToUser();

        // Counts
        $myBusinessCount = Business::where('user_id', $user->id)->count();
        $myPostCount     = Post::where('user_id', $user->id)->count();
        $myEventCount    = Event::where('user_id', $user->id)->count();
        $myNowisPoints   = $user->nowis_points;

        // Token
        $token = $user->createCustomToken('API Token', 60);

        return ResponseHelper::send(200, __('api.success_login'),
            $this->get_user_data($token, $myBusinessCount, $myPostCount, $myEventCount, $myNowisPoints)
        );
    }

    public function forgotPassword(Request $request)
    {
        $this->directValidation([
            'email' => $this->validationService->emailExistsRules(),
        ]);

        if (register_user_email($request->email)) {
            return ResponseHelper::send(200, __('api.success_send_otp'));
        }
        return ResponseHelper::send(412, __('api.error_something_wrong'));
    }

    public function sendOtp(Request $request)
    {
        // // Apply rate limiting (throttle: 1 request per minute per email)
        // $this->directValidation([
        //     'email' => $this->validationService->emailExistsRules(),
        // ]);

        // if (RateLimiter::tooManyAttempts($request->email, 5)) {
        //     return ResponseHelper::send(429, __('api.error_many_request'));
        // }

        // RateLimiter::hit($request->email, 60); // 60 seconds window

        // // Proceed with OTP generation and sending
        // if (register_user_email($request->email)) {
        //     return ResponseHelper::send(200, __('api.success_send_otp'));
        // }
        // return ResponseHelper::send(400, __('api.error_something_wrong'));
        $this->directValidation([
            'email' => ['required', 'email', 'max:255'],
            'type' => ['required', 'in:register,login,forgot'],  // type must be one of these
        ]);

        // Handle type-based email existence rules
        if ($request->type == 'register') {
            // For registration, email must NOT exist
            if (User::where('email', $request->email)->exists()) {
                return ResponseHelper::send(400, __('api.error_already_taken', ['attribute' => 'email']));
            }
        } else {
            // For login or forgot password, email must exist
            if (!User::where('email', $request->email)->exists()) {
                return ResponseHelper::send(400, __('api.error_not_found', ['attribute' => 'email']));
            }
        }

        // Rate limiting
        if (RateLimiter::tooManyAttempts($request->email, 5)) {
            return ResponseHelper::send(429, __('api.error_many_request'));
        }

        RateLimiter::hit($request->email, 60); // 60 seconds window

        // Proceed with OTP generation and sending
        if (register_user_email($request->email)) {
            return ResponseHelper::send(200, __('api.success_send_otp'));
        }

        return ResponseHelper::send(400, __('api.error_something_wrong'));
    }

    public function verifyOtp(Request $request)
    {
        $this->validate($request, [
            'email' => $this->validationService->emailLogsExistsRules(),
            'otp' => ['required', 'numeric'],
        ]);

        $user = UserEmailLog::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            return ResponseHelper::send(400, __('api.error_invalid_otp'));
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return ResponseHelper::send(400, __('api.error_otp_expired'));
        }

        // OTP is valid, proceed with the password reset or other action
        return ResponseHelper::send(200, __('api.success_otp_valid'));
    }

    public function resetPassword(Request $request)
    {
        // Validate the incoming request
        $this->directValidation([
            'email' => $this->validationService->emailExistsRules(),
            'new_password' => $this->validationService->passwordRules(),
            'conf_new_password' => ['required', 'same:new_password'],
        ], [
            'conf_new_password.same' => __('api.error_password_mismatch'),
        ]);

        // Find the user by email
        $user = User::where(['email' => $request->email])->first();

        // Check if the user was found and proceed
        if ($user) {
            // Hash the new password before saving it
            $user->update(['password' => $request->new_password]);

            // Remove OTP logs after successful password reset
            UserEmailLog::where('email', $request->email)->delete();
            return ResponseHelper::send(200, __('api.success_reset_password'));
        }
        return ResponseHelper::send(412, __('api.error_something_went_wrong'));
    }

    public function getContent(Request $request, $slug)
    {

        $validSlugs = ['term-condition', 'privacy-policy', 'about-us'];

        if (!in_array($slug, $validSlugs)) {
            return ResponseHelper::send(404, __('api.error_invalid_content_type'));
        }

        $language = $request->header('Accept-Language') ?? 'en';
        // Assuming content is stored in the database
        $content = Content::where('slug', $slug)->where('language', $language)->first();

        if ($content) {
            return ResponseHelper::send(200, __('api.success_get_content'), $content);
        }

        return ResponseHelper::send(404, __('api.error_content_not_found'));
    }

    public function versionChecker(Request $request)
    {
        $this->directValidation([
            'type' => ['required', 'in:android,ios'],
            'version' => ['required'],
        ]);

        // Fetch the latest version from the database based on type
        $latestVersion = VersionHistory::where('type', $request->type)
            ->orderBy('created_at', 'desc') // Assuming there's a timestamp to determine the latest version
            ->value('version');

        // Check if there's a forced update
        $isForceUpdate = VersionHistory::where([
            'type' => $request->type,
            'is_force' => '1'
        ])->where('version', '>', $request->version)->exists();

        $responseData = [
            'is_force_update' => $isForceUpdate ? 1 : 0,
        ];

        // Compare versions and determine response
        if (version_compare($request->version, $latestVersion, '<')) {
            // New version available
            return ResponseHelper::send(200, __('api.success_new_version_available'), array_merge($responseData, ['latest_version' => $latestVersion]));
        }

        // Up-to-date response
        return ResponseHelper::send(200, __('api.success_version_up_to_date'), $responseData);
    }

    public function checkMobileNumberAndEmail(Request $request)
    {
        $this->directValidation([
            'email' => ['nullable', 'email'],
            'country_code' => ['nullable', 'max:10'],
            'mobile' => ['nullable', 'digits_between:4,15'],
            'referral_code' => ['nullable', 'string'],
        ]);

        if ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                return ResponseHelper::send(
                    412,
                    __('api.error_email_exists'),
                    ['exists' => true, 'field' => 'email']
                );
            }
        }

        if ($request->filled('country_code') && $request->filled('mobile')) {
            $user = User::where('country_code', $request->country_code)
                ->where('mobile', $request->mobile)
                ->first();

            if ($user) {
                return ResponseHelper::send(
                    412,
                    __('api.error_mobile_exists'),
                    ['exists' => true, 'field' => 'mobile']
                );
            }
        }

        if ($request->filled('referral_code')) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            if (!$referrer) {
                return ResponseHelper::send(
                    412,
                    __('api.error_invalid_referral_code'),
                    ['exists' => true, 'field' => 'referral_code']
                );
            }
        }

        return ResponseHelper::send(
            200,
            __('api.success_not_exists'),
            ['exists' => false, 'field' => null]
        );
    }
}
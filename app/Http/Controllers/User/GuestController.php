<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserEmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use PragmaRX\Google2FA\Google2FA;

use function PHPSTORM_META\type;

class GuestController extends Controller
{
    public function getSignup()
    {
        return view('user.guest.sign-up', ['title' => __('user.sign_up')]);
    }

    public function postSignup(Request $request)
    {
        // Validate request input
        $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'country_code' => 'nullable|string|max:10',
            'mobile' => 'nullable|unique:users,mobile',
            'password' => 'required|min:6',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Prepare user data (don't save yet, wait until OTP verified)
        $data = [
            'name' => $request->company_name,
            'email' => $request->email,
            'country_code' => $request->country_code,
            'mobile' => $request->mobile,
            'password' => $request->password,
            'location' => $request->location,
            'type' => 'businessuser',
            'status' => 'pending',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ];

        // Send OTP to email (your helper should handle sending + storing otp/expires_at)
        if (register_user_email($request->email)) {
            session([
                'otp_email' => $request->email,
                'otp_user_data' => $data,
            ]);

            flash_session('success', 'OTP sent to your email. Please verify to activate your account.');
            return redirect()->route('user.verify.otp');
        }

        flash_session('error', 'Failed to send OTP. Please try again.');
        return back();
    }

    public function getVerifyOtp()
    {
        if (!session()->has('otp_email')) {
            flash_session('error', 'Session expired. Please register again.');
            return redirect()->route('user.signup');
        }

        return view('user.guest.verify-otp', [
            'title' => __('user.verify_otp'),
        ]);
    }

    public function postVerifyOtp(Request $request)
    {
        $request->validate([
            'otp_no' => 'required|array|size:6',
            'otp_no.*' => 'required|numeric|digits:1',
        ]);

        $otp = implode('', $request->otp_no);

        if (!session()->has('otp_email') || !session()->has('otp_user_data')) {
            flash_session('error', 'Session expired. Please register again.');
            return redirect()->route('user.signup');
        }

        $email = session('otp_email');
        $userData = session('otp_user_data');

        $userEmailLog = UserEmailLog::where('email', $email)->first();

        if (!$userEmailLog) {
            flash_session('error', 'No OTP found. Please request again.');
            return back();
        }

        if ($userEmailLog->otp !== $otp) {
            flash_session('error', 'Invalid OTP.');
            return back();
        }

        if (now()->greaterThan($userEmailLog->otp_expires_at)) {
            flash_session('error', 'OTP has expired. Please request a new one.');
            return back();
        }

        // ✅ OTP valid — create user
        $user = new User();
        $userData['status'] = 'active';
        $user->fill($userData)->save();

        // Delete OTP log and clear session
        $userEmailLog->delete();
        session()->forget(['otp_email', 'otp_user_data']);

        flash_session('success', 'Registration successful. You can now log in.');
        return redirect()->route('user.login');
    }

    public function resendOtp()
    {
        if (!session()->has('otp_email')) {
            flash_session('error', 'Session expired. Please login again.');
            return redirect()->route('user.login');
        }

        $email = session('otp_email');

        if (register_user_email($email)) {
            flash_session('success', 'OTP resent successfully.');
        } else {
            flash_session('error', 'Failed to resend OTP.');
        }

        return redirect()->back();
    }
    public function getLogin()
    {
        return view('user.guest.login', ['title' => __('user.login')]);
    }

    public function postLogin(Request $request)
{
    $request->validate([
        'username' => 'required',
        'password' => 'required',
    ]);

    // Determine field
    $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    // Find the user first to check type and status
    $user = User::where($loginField, $request->username)
        ->whereIn('type', ['businessuser', 'sub-businessuser'])
        ->where('status', 'active')
        ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        flash_session('error', 'Invalid username/email or password');
        return back();
    }

    // STEP 3: If 2FA enabled, redirect to OTP
    if ($user->is_two_factor_authentication && $user->google2fa_secret) {
        session(['2fa:user:id' => $user->id]);
        session(['2fa:remember' => $request->boolean('remember')]);

        return redirect()->route('user.2fa.login');
    }

    // STEP 4: Normal login (no 2FA)
    Auth::login($user, $request->boolean('remember'));

    return redirect()->route(get_dashboard_route_name());
}

    // ---------------------------
    // SHOW LOGIN OTP PAGE
    // ---------------------------
    public function showLogin2FA()
    {
        if (!session()->has('2fa:user:id')) {
            return redirect()->route('user.login');
        }

        return view('user.guest.login_2fa');
    }

    // ---------------------------
    // VERIFY LOGIN OTP
    // ---------------------------
    public function verifyLogin2FA(Request $request)
    {
        $request->validate(['otp' => 'required']);

        $user = User::find(session('2fa:user:id'));

        if (!$user) {
            return redirect()->route('user.login')->with('error','Session expired.');
        }

        $google2fa = new Google2FA();

        // Verify OTP
        if (!$google2fa->verifyKey($user->google2fa_secret, $request->otp)) {
            flash_session('error', 'Invalid OTP');
            return back();
        }

        // OTP correct → LOGIN NOW
        Auth::login($user);
        session()->forget('2fa:user:id');

        flash_session('success', 'Login successful');
        return redirect()->route(get_dashboard_route_name());
    }

    public function showLinkRequestForm()
    {
        return view('user.guest.forgot-password', ['title' => __('user.forgot_password')]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        User::passwordReset($request->email);
        return redirect()->back();
    }

    public function showResetForm($token)
    {
        return view('user.guest.reset-password', ['title' => __('user.reset_password'), 'token' => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'reset_token' => ['required', Rule::exists('users', 'reset_token')->whereNull('deleted_at')],
            'password' => ['required'],
        ], [
            'reset_token.exists' => 'Invalid password token',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 6 characters long.',
        ]);
        $user = User::where('reset_token', $request->reset_token)->firstOrFail();
        $user->update([
            'password' => $request->password,
            'reset_token' => null,
        ]);
        flash_session('success', 'Password updated successfully');
        return redirect()->route('user.login');
    }

    public function checkEmail(Request $request)
    {
        $email = $request->email;

        $exists = User::where('email', $email)->exists();

        return response()->json(!$exists);
    }
}

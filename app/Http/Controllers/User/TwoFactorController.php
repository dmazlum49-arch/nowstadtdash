<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    public function toggle(Request $request)
    {
        $user = Auth::user();
        $google2fa = new Google2FA();

        // If turning ON
        if ($request->has('is_2fa')) {

            // Generate secret only once
            if (!$user->google2fa_secret) {
                $user->google2fa_secret = $google2fa->generateSecretKey();
                $user->is_two_factor_authentication = false; // not verified yet
                $user->save();
            }

            return redirect()->route('user.2fa.setup');
        }

        // Turning OFF
        return $this->disable($request);
    }

    public function disable(Request $request)
    {
        $user = Auth::user();

        $user->is_two_factor_authentication = false;
        $user->google2fa_secret = null;
        $user->save();

        flash_session('success', 'Two-Factor Authentication disabled successfully');
        return redirect()->back();
    }

    // ---------------------------
    // ENABLE 2FA PAGE (SHOW QR)
    // ---------------------------
    public function show2FASetup()
    {
        $user = Auth::user();

        // Generate QR
        $google2fa = new Google2FA();

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($qrCodeUrl);

        return view('user.twoFactor.enable_2fa', [
            'qrCode' => $qrSvg,
            'secret' => $user->google2fa_secret,
            'user' => $user
        ]);
    }


    public function verify2FASetup(Request $request)
    {
        $request->validate(['otp' => 'required']);

        $user = Auth::user();
        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($user->google2fa_secret, $request->otp)) {
            flash_session('error', 'Invalid OTP');
            return back();
        }

        $user->is_two_factor_authentication = true;
        $user->save();

        flash_session('success', 'Two-Factor Authentication enabled successfully');
        return redirect()->route('user.2fa.setup');
    }
}
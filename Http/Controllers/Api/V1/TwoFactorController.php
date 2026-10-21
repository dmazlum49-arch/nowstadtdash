<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\Api\ResponseController;
use Illuminate\Http\Request;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends ResponseController
{
    public function apiToggle2FA(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();

        $request->validate([
            'is_two_factor_authentication' => 'required|boolean'
        ]);

        if ($request->is_two_factor_authentication == true) {

            if (!$user->google2fa_secret) {
                $user->google2fa_secret = $google2fa->generateSecretKey();
                $user->save();
            }

            $user->is_two_factor_authentication = false;
            $user->save();

            return ResponseHelper::send(200, '2FA secret generated', [
                'secret' => $user->google2fa_secret,
                'otpauth_url' => $google2fa->getQRCodeUrl(
                    config('app.name'),
                    $user->email,
                    $user->google2fa_secret
                )
            ]);
        }

        $user->is_two_factor_authentication = false;
        $user->google2fa_secret = null;
        $user->save();

        return ResponseHelper::send(200, '2FA disabled');
    }

    public function apiVerify2FA(Request $request)
    {
        $request->validate([
            'otp' => 'required'
        ]);

        $user = $request->user();
        $google2fa = new Google2FA();

        // Verify OTP
        if (!$google2fa->verifyKey($user->google2fa_secret, $request->otp)) {
            return ResponseHelper::send(400, 'Invalid OTP');
        }

        // Activate 2FA
        $user->is_two_factor_authentication = true;
        $user->save();

        return ResponseHelper::send(200, '2FA enabled successfully');
    }

}
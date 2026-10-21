<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );
    }

    /**
     * Send SMS using Twilio.
     *
     * @param string $mobile
     * @param string $message
     * @return bool
     */
    public function sendSms(string $mobile, string $message): bool
    {
        try {
            $this->twilio->messages->create(
                $mobile, // To mobile number
                [
                    'from' => env('TWILIO_PHONE_NUMBER'), // From Twilio number
                    'body' => $message
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error("Twilio SMS failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send verification SMS using Twilio Verify API.
     *
     * @param string $mobile
     * @return bool
     */
    public function sendVerification(string $mobile, $live = false): bool
    {
        if (strtolower(request()->getHttpHost()) == 'localhost' || $live) {
            return true;
        }

        try {
            $verification = $this->twilio->verify
                ->v2
                ->services(env('TWILIO_VERIFY_SERVICE_SID'))
                ->verifications
                ->create($mobile, 'sms');

            return $verification->sid ? true : false;
        } catch (\Exception $e) {
            Log::error("Twilio Verification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify OTP code sent via SMS.
     *
     * @param string $mobile
     * @param string $code
     * @return bool
     */
    public function verifyOtp(string $mobile, string $code, $live = false): bool
    {
        if (strtolower(request()->getHttpHost()) == 'localhost' || $live) {
            return 'approved';
        }

        try {
            $verificationCheck = $this->twilio->verify
                ->v2
                ->services(env('TWILIO_VERIFY_SERVICE_SID'))
                ->verificationChecks
                ->create([
                    'to' => $mobile,
                    'code' => $code,
                ]);

            return $verificationCheck->status === 'approved';
        } catch (\Exception $e) {
            Log::error("Twilio OTP Verification failed: " . $e->getMessage());
            return false;
        }
    }
}

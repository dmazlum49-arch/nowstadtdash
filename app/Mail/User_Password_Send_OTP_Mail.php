<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class User_Password_Send_OTP_Mail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
        $this->locale = 'de';
    }

    public function build()
    {
        App::setLocale($this->locale);
        return $this->subject(__('mail.password_reset_otp_subject'))
            ->view('emails.otp')
            ->with(['otp' => $this->otp]);
    }
}

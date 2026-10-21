<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class UpdateEmailSendOtpMail extends Mailable
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
        return $this->subject(__('mail.update_email_otp_subject'))
            ->view('emails.update_email_otp')
            ->with(['otp' => $this->otp]);
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class NowisPointQrMail extends Mailable
{
    use Queueable, SerializesModels;

    public $business;
    public $qrFilePath;

    public function __construct($business, $qrFilePath)
    {
        $this->business = $business;
        $this->qrFilePath = $qrFilePath;

        $this->locale = $business->user->language ?? config('app.locale');
    }

    public function build()
    {
        App::setLocale($this->locale);
        return $this->subject(__('mail.welcome_to_nowstadt_your_qr_code_is_ready_to_scan'))
            ->view('emails.nowis-point-qr')
            ->attach($this->qrFilePath, [
                'as' => 'business_qr_code.png',
                'mime' => 'image/png',
            ])
            ->with([
                'business' => $this->business,
            ]);
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class User_Password_Reset_Mail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;

        $this->locale = $user->language ?? config('app.locale');
    }

    public function build()
    {
        App::setLocale($this->locale);
        return $this->subject(__('mail.password_reset_subject'))
            ->view('emails.password-reset', ['user' => $this->user]);
    }
}

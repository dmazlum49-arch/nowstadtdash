<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class BusinessCreateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $business;

    public function __construct($name, $business )
    {
        $this->name = $name;
        $this->business = $business;
        $this->locale = $business->user->language ?? config('app.locale');
    }

    public function build()
    {
        App::setLocale($this->locale);
        return $this->subject(__('mail.new_business_subject'))
                    ->view('emails.business-create')
                    ->with([
                        'name' => $this->name,
                        'business' => $this->business,
                    ]);
    }
}

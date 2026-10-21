<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class EventCreateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $event;

    public function __construct($user, $event)
    {
        $this->user = $user;
        $this->event = $event;

        $this->locale = $user->language ?? config('app.locale');
    }

    public function build()
    {
        App::setLocale($this->locale);
        return $this->subject(__('mail.new_event_submitted_for_approval'))
                    ->view('emails.event-create')
                    ->with([
                        'user' => $this->user,
                        'event' => $this->event,
                    ]);
    }
}

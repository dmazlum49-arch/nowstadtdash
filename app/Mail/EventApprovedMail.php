<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class EventApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $event;
    public $reason;

    public function __construct($user, $event, $reason = null)
    {
        $this->name = $user->name;
        $this->event = $event;
        $this->reason = $reason;

        $this->locale = $user->language ?? config('app.locale');
    }

    public function build()
    {
        App::setLocale($this->locale);
        return $this->subject(__('mail.your_event_submission_was_approved'))
                    ->view('emails.event-approved')
                    ->with([
                        'name' => $this->name,
                        'event' => $this->event,
                    ]);
    }
}

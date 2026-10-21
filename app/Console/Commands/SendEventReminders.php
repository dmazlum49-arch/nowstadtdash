<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\Favorite;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\App;

// class SendEventReminders extends Command
// {
//     protected $signature = 'events:send-reminders';
//     protected $description = 'Send reminders to users before events start';

//     public function handle()
// {
//     $now = Carbon::now();

//     $eventsTomorrow = Event::whereDate('start_date', $now->copy()->addDay()->toDateString())->get();

//     foreach ($eventsTomorrow as $event) {
//         $eventDateTime = Carbon::parse($event->start_date . ' ' . $event->start_time);

//         $this->sendReminder(
//             $event,
//             "Morning: {$event->event_name} at {$eventDateTime->format('H:i')}. Don't miss it!"
//         );
//     }

//     $eventsToday = Event::whereDate('start_date', $now->toDateString())->get();

//     foreach ($eventsToday as $event) {
//         $eventDateTime = Carbon::parse($event->start_date . ' ' . $event->start_time);

//         if ($eventDateTime->between($now->copy()->addHours(2), $now->copy()->addHours(3))) {
//             $this->sendReminder(
//                 $event,
//                 "Today: {$event->event_name} at {$eventDateTime->format('H:i')}. Be there!"
//             );
//         }
//     }

//     $this->info('Event reminders sent successfully.');
// }

// private function sendReminder($event, $message)
// {
//     $userIds = Favorite::where('event_id', $event->id)->pluck('user_id');

//     if ($userIds->isEmpty()) {
//         return;
//     }

//     $users = User::whereIn('id', $userIds)->get();

//     foreach ($users as $user) {
//         $locale = $user->language ?? 'en';
//         App::setLocale($locale);
//         send_push($user->id, [
//             'from_user_id' => 0,
//             'push_type'    => 2,
//             'push_title'   => __('admin.event_reminder_title'),
//             'push_message' => $message,
//             'extra' => [
//                 'event_id' => $event->id,
//             ],
//         ], true);

//         $this->line("Reminder sent to {$user->name}: {$message}");
//     }
// }
// }

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';
    protected $description = 'Send reminders to users before events start';

    public function handle()
    {
        $today = Carbon::today();

        // Get all event favorites with reminders
        $favorites = Favorite::where('type', 'event')
            ->whereNotNull('reminder_option')
            ->where('reminder_option', '!=', 'none')
            ->with('event', 'user')
            ->get();

        foreach ($favorites as $fav) {
            $event = $fav->event;
            
            if (!$event || !$event->start_date)
                continue;

            $eventDate = Carbon::parse($event->start_date);
            $shouldSend = false;
            $message = "";

            switch ($fav->reminder_option) {
                case 'same_day':
                    if ($today->isSameDay($eventDate)) {
                        $shouldSend = true;
                        $message = "Today: '{$event->event_name}' is happening today!";
                    }
                    break;
                
                case '1_day_before':
                    if ($today->copy()->addDay()->isSameDay($eventDate)) {
                         $shouldSend = true;
                         $message = "Reminder: '{$event->event_name}' is tomorrow!";
                    }
                    break;

                case '2_days_before':
                    if ($today->copy()->addDays(2)->isSameDay($eventDate)) {
                        $shouldSend = true;
                        $message = "Reminder: '{$event->event_name}' is in 2 days!";
                    }
                    break;
                
                case '5_days_before':
                    if ($today->copy()->addDays(5)->isSameDay($eventDate)) {
                        $shouldSend = true;
                        $message = "Reminder: '{$event->event_name}' is in 5 days!";
                    }
                    break;

                case '1_week_before':
                    if ($today->copy()->addWeek()->isSameDay($eventDate)) {
                        $shouldSend = true;
                        $message = "Reminder: '{$event->event_name}' is in 1 week!";
                    }
                    break;

                case 'custom':
                    if ($fav->reminder_date && $today->isSameDay(Carbon::parse($fav->reminder_date))) {
                        $shouldSend = true;
                        $message = "Custom Reminder: '{$event->event_name}' is coming up!";
                    }
                    break;
            }

            if ($shouldSend) {
                $this->sendReminder($fav->user, $event, $message);
            }
        }

        $this->info('Reminders sent successfully!');
    }

    private function sendReminder($user, $event, $message)
    {
        if (!$user) return;
        
        // Ensure language is set for translation
        $locale = $user->language ?? 'en';
        App::setLocale($locale);

        send_push($user->id, [
            'from_user_id' => 0,
            'push_type' => 2,
            'push_title' => __('admin.event_reminder_title'),
            'push_message' => $message,
            'extra' => [
                'event_id' => $event->id,
            ],
        ], true);
        
        $this->line("Reminder sent to {$user->name}: {$message}");
    }
}

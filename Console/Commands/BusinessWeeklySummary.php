<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class BusinessWeeklySummary extends Command
{
    protected $signature = 'business:weekly-summary';
    protected $description = 'Send weekly summary of new businesses to all users';

    public function handle()
    {
        $lastWeek = Carbon::now()->subWeek();
        $newBusinesses = Business::where('created_at', '>=', $lastWeek)->get();

        if ($newBusinesses->count() >= 0) {

            $users = User::where('type', 'user')->get();
            foreach ($users as $user) {
                $locale = $user->language ?? 'en';
                App::setLocale($locale);
                send_push($user->id, [
                    'from_user_id' => 0,
                    'push_type'    => 4,
                    'push_title'   => __('admin.business_created_title'),
                    'push_message' => __('admin.business_created_message'),
                    'extra' => [
                        'new_businesses' => $newBusinesses
                    ]
                ]);
            }
        }

        $this->info("Weekly summary sent.");
    }
}

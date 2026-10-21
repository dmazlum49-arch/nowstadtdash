<?php

use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\User\GeneralController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::redirect('/', destination: 'login')->name('dashboard');

Route::get('/phpinfo', function () {
    phpinfo();
});
//Route::get('/', [GeneralController::class, 'dashboard'])->name('user.login');

Route::get('/run-cron', function () {
    Artisan::call('events:send-reminders');
    return "Cron job executed successfully!";
});

Route::get('/run-weekly-summary', function () {
    Artisan::call('business:weekly-summary');
    return "Weekly summary job executed successfully!";
});

Route::get('/user', function() {
    if (auth()->check()) {
        return redirect()->route(get_dashboard_route_name());
    }
    return redirect()->route('user.login');
});

Route::post('subscription/webhook', [SubscriptionController::class, 'webhook']);

Route::post('mediaconvert/callback', [GuestController::class, 'mediaConvertWebhook']);

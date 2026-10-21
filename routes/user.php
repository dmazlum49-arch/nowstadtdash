<?php

use App\Http\Controllers\Admin\BoostController;
use App\Http\Controllers\Admin\BusinessCategoryController;
use App\Http\Controllers\User\BusinessController;
use App\Http\Controllers\User\EventController;
use App\Http\Controllers\User\GeneralController;
use App\Http\Controllers\User\GuestController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\PostController;
use App\Http\Controllers\User\RewardNowisPointController;
use App\Http\Controllers\User\RewardStampCardController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\ServiceController;
use App\Http\Controllers\User\SubscriptionController;
use App\Http\Controllers\User\TwoFactorController;
use App\Http\Controllers\User\SubBusinessUserController;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'guest'], function () {
    Route::get('sign-up', [GuestController::class, 'getSignup'])->name('signup');
    Route::post('sign-up', [GuestController::class, 'postSignup'])->name('signup.post');
    Route::get('verify-otp', [GuestController::class, 'getVerifyOtp'])->name('verify.otp');
    Route::post('verify-otp', [GuestController::class, 'postVerifyOtp'])->name('verify.otp.post');
    Route::post('resend-otp', [GuestController::class, 'resendOtp'])->name('resend.otp');

    // login
    Route::get('login', [GuestController::class, 'getLogin'])->name('login');
    Route::post('login', [GuestController::class, 'postLogin'])->name('login.post');

    // Login 2FA verify
    Route::get('/2fa/login', [GuestController::class, 'showLogin2FA'])->name('2fa.login');
    Route::post('/2fa/login', [GuestController::class, 'verifyLogin2FA'])->name('2fa.login.post');

    

    Route::get('forgot-password', [GuestController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [GuestController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [GuestController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [GuestController::class, 'reset'])->name('password.update');
});

Route::group(['middleware' => ['auth', 'is_businessuser']], function () {
    Route::get('/', [GeneralController::class, 'dashboard'])->name('dashboard');
    Route::get('/logout', [GeneralController::class, 'logout'])->name('logout');

    Route::group(['middleware' => ['permission:business-list']], function () {
        Route::post('business/verify-code', [BusinessController::class, 'verifyCode'])->name('business.verify-code');
        Route::post('business/regenerate-code', [BusinessController::class, 'regenerateCode'])->name('business.regenerate-code');
        Route::resource('business', BusinessController::class);
    });
    Route::group(['middleware' => ['permission:service-list']], function () {
        Route::resource('service', ServiceController::class);
    });
    Route::group(['middleware' => ['permission:nowis-point-list']], function () {
        Route::resource('nowis-point', RewardNowisPointController::class);
    });
    Route::group(['middleware' => ['permission:stamp-card-list']], function () {
        Route::resource('stamp-card', RewardStampCardController::class);
    });

    Route::group(['middleware' => ['permission:sub-user-list']], function () {
        Route::resource('sub-business-users', SubBusinessUserController::class);
        Route::get('sub-business-users/update-status/{id}', [SubBusinessUserController::class, 'updateStatus'])->name('sub-business-users.update-status');
    });
    Route::group(['middleware' => ['permission:role-list']], function () {
        Route::resource('roles', RoleController::class);
    });

    
    Route::group(['middleware' => ['permission:my-event-list']], function () {
        Route::resource('event', EventController::class);
    });
    Route::group(['middleware' => ['permission:my-post-list']], function () {
        Route::resource('post', PostController::class);
        Route::delete('user/post/image/{id}', [PostController::class, 'deleteImage'])->name('post.image.delete');
    });


    Route::get('change-password', [GeneralController::class, 'showChangePasswordForm'])->name('change-password');
    Route::post('change-password', [GeneralController::class, 'updatePassword'])->name('change-password.update');

    Route::get('profile', [GeneralController::class, 'getProfile'])->name('profile');
    Route::post('profile', [GeneralController::class, 'updateProfile'])->name('profile.update');

    Route::group(['middleware' => ['permission:notification-list']], function () {
        Route::get('notifications/create', [NotificationController::class, 'create'])->name('notification.create');
        Route::post('notifications', [NotificationController::class, 'store'])->name('notification.store');
    });

    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('subscriptions/create-checkout-session', [SubscriptionController::class, 'createCheckoutSession'])->name('createCheckoutSession');

    Route::post('/subscribe', [SubscriptionController::class, 'process'])->name('subscribe.process');


    Route::group(['middleware' => ['permission:two-factor-auth']], function () {
        Route::post('/2fa/toggle', [TwoFactorController::class, 'toggle'])->name('2fa.toggle');
        Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
        // Enable 2FA (scan QR)
        Route::get('/2fa/setup', [TwoFactorController::class, 'show2FASetup'])->name('2fa.setup');
        Route::post('/2fa/setup', [TwoFactorController::class, 'verify2FASetup'])->name('2fa.setup.verify');
    });

});

Route::get('availability-checker', [GeneralController::class, 'availabilityChecker'])->name('availability-checker');
Route::get('user-availability-checker', [GeneralController::class, 'availabilityCheckerUser'])->name('user-availability-checker');

Route::get('check-email', [GuestController::class, 'checkEmail'])->name('check.email');
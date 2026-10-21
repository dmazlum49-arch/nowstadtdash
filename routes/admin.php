<?php

use App\Http\Controllers\Admin\BoostController;
use App\Http\Controllers\Admin\BusinessRatingController;
use App\Http\Controllers\Admin\BusinessCategoryController;
use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\BusinessPostPermissionController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\EventCategoryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GeneralController;
use App\Http\Controllers\Admin\HomeBannerController;
use App\Http\Controllers\Admin\MyEventController;
use App\Http\Controllers\Admin\ReportCommentController;
use App\Http\Controllers\Admin\ReportPostController;
use App\Http\Controllers\Admin\ReportTypeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SubAdminController;
use App\Http\Controllers\Admin\StoryReportController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ChangeBusinessQrCodeController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ReportController;
use App\Models\HomeBanner;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::group(['middleware' => 'guest'], function () {
    Route::get('login', [GuestController::class, 'getLogin'])->name('login');
    Route::post('login', [GuestController::class, 'postLogin'])->name('login.post');
    Route::get('forgot-password', [GuestController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [GuestController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [GuestController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [GuestController::class, 'reset'])->name('password.update');
});

Route::group(['middleware' => ['auth', 'is_admin']], function () {
    Route::get('/', [GeneralController::class, 'dashboard'])->name('dashboard');
    Route::get('/logout', [GeneralController::class, 'logout'])->name('logout');
    // Profile
    Route::get('profile', [GeneralController::class, 'getProfile'])->name('profile');
    Route::post('profile', [GeneralController::class, 'updateProfile'])->name('profile.update');
    // Change Password
    Route::get('change-password', [GeneralController::class, 'showChangePasswordForm'])->name('change-password');
    Route::post('change-password', [GeneralController::class, 'updatePassword'])->name('change-password.update');
    // General Settings
    Route::group(['middleware' => ['permission:setting-list']], function () {
        Route::get('site-settings', [GeneralController::class, 'getSetting'])->name('site-settings');
        Route::post('site-settings', [GeneralController::class, 'settingUpdate'])->name('site-settings.update');
        Route::get('version-settings', [GeneralController::class, 'getVersionSetting'])->name('version-settings');
        Route::get('version-index', [GeneralController::class, 'VersionList'])->name('version.index');
        Route::get('credentials', [GeneralController::class, 'getCredentials'])->name('credentials');
        Route::post('credentials', [GeneralController::class, 'credentialsUpdate'])->name('credentials.update');
    });

    //Contents
    Route::group(['middleware' => ['permission:content-list']], function () {
        Route::resource('contents', ContentController::class);
    });

    //Users
    Route::group(['middleware' => ['permission:user-list']], function () {
        Route::resource('users', UserController::class);
        Route::get('users/update-status/{id}', [UserController::class, 'updateStatus'])->name('users.update-status');
    });

    Route::group(['middleware' => ['permission:business-user-list']], function () {
        Route::get('business-users', [UserController::class, 'businessUserIndex'])->name('business-users.index');
        Route::post('admin/users/verify/{id}', [UserController::class, 'verifyUser'])->name('users.verify');
    });

    // Sub Admins
    Route::group(['middleware' => ['permission:sub-admin-list']], function () {
        Route::resource('sub-admins', SubAdminController::class);
        Route::get('sub-admins/update-status/{id}', [SubAdminController::class, 'updateStatus'])->name('sub-admins.update-status');
    });
    //Business Categories
    Route::group(['middleware' => ['permission:business-category-list']], function () {
        Route::resource('business-category', BusinessCategoryController::class);
        Route::get('business-category/update-status/{id}', [BusinessCategoryController::class, 'updateStatus'])->name('business-category.update-status');
    });

    // Businesses
    Route::group(['middleware' => ['permission:business-list']], function () {
        Route::get('pending-business', [BusinessController::class, 'pending'])->name('pending.business');
        Route::post('business/accept/{id}', [BusinessController::class, 'accept'])->name('business.accept');
        Route::post('business/reject/{id}', [BusinessController::class, 'reject'])->name('business.reject');
        Route::get('approved-business', [BusinessController::class, 'approved'])->name('approved.business');
        Route::get('business/{id}', [BusinessController::class, 'show'])->name('business.show');
        Route::get('business/update-status/{id}', [BusinessController::class, 'updateStatus'])->name('business.update-status');
        Route::delete('business/{id}', [BusinessController::class, 'destroy'])->name('business.destroy');
        Route::get('business/{id}/edit', [BusinessController::class, 'edit'])->name('business.edit');
        Route::put('business/{id}', [BusinessController::class, 'update'])->name('business.update');
    });

    // Event Categories
    Route::group(['middleware' => ['permission:event-category-list']], function () {
        Route::resource('event-category', EventCategoryController::class);
        Route::get('event-category/update-status/{id}', [EventCategoryController::class, 'updateStatus'])->name('event-category.update-status');
    });

    // Events
    Route::group(['middleware' => ['permission:event-list']], function () {
        Route::get('pending-event', [EventController::class, 'pending'])->name('pending.event');
        Route::get('event/{id}', [EventController::class, 'show'])->name('event.show');
        Route::get('approved-event', [EventController::class, 'approved'])->name('approved.event');
        Route::get('event/update-status/{id}', [EventController::class, 'updateStatus'])->name('event.update-status');
        Route::post('event/accept/{id}', [EventController::class, 'accept'])->name('event.accept');
        Route::post('event/reject/{id}', [EventController::class, 'reject'])->name('event.reject');
        Route::delete('event/{id}', [EventController::class, 'destroy'])->name('event.destroy');
    });

    // Home Banner
    Route::group(['middleware' => ['permission:banner-list']], function () {
        Route::resource('home-banner', HomeBannerController::class);
        Route::get('home-banner/update-status/{id}', [HomeBannerController::class, 'updateStatus'])->name('home-banner.update-status');
    });

    // City
    Route::group(['middleware' => ['permission:city-list']], function () {
        Route::resource('city', CityController::class);
        Route::get('city/update-status/{id}', [CityController::class, 'updateStatus'])->name('city.update-status');
    });

    // Services
    Route::group(['middleware' => ['permission:service-list']], function () {
        Route::resource('service', ServiceController::class);
    });

    // Change Business QR Code
    Route::group(['middleware' => ['permission:qr-change-list']], function () {
        Route::get('business-qr-code-change', [ChangeBusinessQrCodeController::class, 'index'])->name('business.qr.change.index');
        Route::post('business-qr-change/accept/{id}', [ChangeBusinessQrCodeController::class, 'accept'])->name('admin.business.accept');
        Route::post('business-qr-change/reject/{id}', [ChangeBusinessQrCodeController::class, 'reject'])->name('admin.business.reject');
    });

    // Boosts
    Route::group(['middleware' => ['permission:boost-list']], function () {
        Route::resource('boost', BoostController::class);
    });

    // Posts
    Route::group(['middleware' => ['permission:my-post-list']], function () {
        Route::resource('post', PostController::class);
        Route::delete('admin/post/image/{id}', [PostController::class, 'deleteImage'])->name('post.image.delete');
    });

    // My Events
    Route::group(['middleware' => ['permission:my-event-list']], function () {
        Route::resource('my-event', MyEventController::class);
    });

    // Notifications
    Route::group(['middleware' => ['permission:notification-list']], function () {
        Route::get('notifications/create', [NotificationController::class, 'create'])->name('notification.create');
        Route::post('notifications', [NotificationController::class, 'store'])->name('notification.store');
    });

    // Reported
    Route::group(['middleware' => ['permission:reported-content-list']], function () {
        Route::resource('report-post', ReportPostController::class);
        Route::get('/delete-post/{id}', [ReportPostController::class, 'deletePost'])->name('delete-post');
        Route::get('/ban-post/{id}', [ReportPostController::class, 'banPost'])->name('ban-post');
        Route::get('/ignore/{id}', [ReportPostController::class, 'ignore'])->name('ignore');
        Route::resource('report-comment', ReportCommentController::class);
        Route::get('/delete-comment/{id}', [ReportCommentController::class, 'deleteComment'])->name('delete-comment');
        Route::get('/ban-comment/{id}', [ReportCommentController::class, 'banComment'])->name('ban-comment');
        Route::get('/ignore-comment/{id}', [ReportCommentController::class, 'ignore'])->name('ignore-comment');
        Route::resource('story-report', StoryReportController::class);
        Route::get('/delete-story/{id}', [StoryReportController::class, 'deleteStory'])->name('delete-story');
        Route::get('/ignore-story/{id}', [StoryReportController::class, 'ignore'])->name('ignore-story');
    });

    // Report Types
    Route::group(['middleware' => ['permission:report-type-list']], function () {
        Route::resource('report-type', ReportTypeController::class);
        Route::get('report-type/update-status/{id}', [ReportTypeController::class, 'updateStatus'])->name('report-type.update-status');
    });

    // Reported
    Route::group(['middleware' => ['permission:report-list']], function () {
        Route::resource('reports', ReportController::class);
        Route::post('report/accept/{id}', [ReportController::class, 'accept'])->name('reports.accept');
        Route::post('report/reject/{id}', [ReportController::class, 'reject'])->name('reports.reject');
    });


    // Moderation
    Route::group(['middleware' => ['permission:moderation-list']], function () {
        Route::group(['prefix' => 'moderation', 'as' => 'moderation.'], function () {
            Route::get('/', [\App\Http\Controllers\Admin\ModerationController::class, 'index'])->name('index');
            Route::get('/posts', [\App\Http\Controllers\Admin\ModerationController::class, 'posts'])->name('posts');
            Route::get('/comments', [\App\Http\Controllers\Admin\ModerationController::class, 'comments'])->name('comments');
            Route::get('/messages', [\App\Http\Controllers\Admin\ModerationController::class, 'messages'])->name('messages');
            Route::get('/approve-post/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'approvePost'])->name('approve-post');
            Route::get('/reject-post/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'rejectPost'])->name('reject-post');
            Route::get('/approve-comment/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'approveComment'])->name('approve-comment');
            Route::get('/reject-comment/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'rejectComment'])->name('reject-comment');
            Route::get('/approve-message/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'approveMessage'])->name('approve-message');
            Route::get('/reject-message/{id}', [\App\Http\Controllers\Admin\ModerationController::class, 'rejectMessage'])->name('reject-message');
        });
    });

    // Business Ratings
    Route::group(['middleware' => ['permission:business-rating-list']], function () {
        Route::get('business-ratings', [BusinessRatingController::class, 'index'])->name('business-ratings.index');
        Route::get('business-ratings/update-status/{id}', [BusinessRatingController::class, 'updateStatus'])->name('business-ratings.update-status');
        Route::get('business-ratings/approve/{id}', [BusinessRatingController::class, 'approve'])->name('business-ratings.approve');
        Route::delete('business-ratings/{id}', [BusinessRatingController::class, 'destroy'])->name('business-ratings.destroy');
    });

    // Business Post Permissions
    Route::group(['middleware' => ['permission:business-post-permission-list']], function () {
        Route::resource('business-post-permissions', BusinessPostPermissionController::class);
    });

    // Roles & Permissions
    Route::group(['middleware' => ['permission:role-list']], function () {
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
    });
});

Route::get('/check-business-category-name', [BusinessCategoryController::class, 'checkName'])->name('business-category.check-name');
Route::get('/check-business-category-name-edit', [BusinessCategoryController::class, 'checkNameInEdit'])->name('business-category.check-name-edit');

Route::get('check-event-category-name', [EventCategoryController::class, 'checkName'])->name('event-category.check-name');
Route::get('check-event-category-name-edit', [EventCategoryController::class, 'checkNameInEdit'])->name('event-category.check-name-edit');

Route::get('user-availability-checker', [GeneralController::class, 'availabilityCheckerUser'])->name('user-availability-checker');
Route::get('availability-checker', [GeneralController::class, 'availabilityChecker'])->name('availability-checker');

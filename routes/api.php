<?php

use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\BusinessRatingController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\HelpController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RewardNowisPointController;
use App\Http\Controllers\Api\V1\RewardStampCardController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\StoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::prefix('V1')->middleware(['set_locale'])->middleware('throttle:1000,1')->group(function () {
    Route::post('signup', [GuestController::class, 'signup']);
    Route::post('login', [GuestController::class, 'login']);
    Route::post('forgot-password', [GuestController::class, 'forgotPassword']);
    Route::post('reset-password', [GuestController::class, 'resetPassword']);
    Route::get('content/{slug}', [GuestController::class, 'getContent']);
    Route::post('version-checker', [GuestController::class, 'versionChecker']);
    Route::post('send-otp', [GuestController::class, 'sendOtp']);
    Route::post('verify-otp', [GuestController::class, 'verifyOtp']);

    Route::post('verify-login-otp', [GuestController::class, 'verify2FAOtp']);

    Route::post('check-availability', [GuestController::class, 'checkMobileNumberAndEmail']);

    // guest flow routes
    Route::post('business-category', [BusinessController::class, 'businessCategory']);
    Route::post('businesses', [BusinessController::class, 'getBusinessByCategory']);
    Route::post('business-details', [BusinessController::class, 'businessDetails'])->name('business.details');
    Route::post('approved-businesses', [BusinessController::class, 'getAllApprovedBusinesses']);
    Route::post('event-list', [EventController::class, 'myEventList']);
    Route::post('event-details', [EventController::class, 'eventDetails']);
    Route::post('near-by-business', [HomeController::class, 'nearByBusiness']);
    Route::post('open-now-business', [HomeController::class, 'openNowBusiness']);
    Route::post('top-business', [HomeController::class, 'topBusiness']);
    Route::post('banner-details', [HomeController::class, 'bannerDetails']);
    Route::post('home', [HomeController::class, 'home']);
    Route::post('all-posts', [PostController::class, 'getAllPostList']);
    Route::post('post-details', [PostController::class, 'postDetails']);
    Route::post('other-user-profile', [PostController::class, 'otherUserProfile']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('get-profile', [UserController::class, 'getProfile']);
        Route::post('edit-profile', [UserController::class, 'editProfile']);
        Route::post('change-password', [UserController::class, 'changePassword']);
        // Route::post('notification-flag', [UserController::class, 'notificationFlag']);
        Route::post('notifications', [UserController::class, 'notificationList']);
        Route::post('logout', [UserController::class, 'logout']);
        Route::delete('delete-account', [UserController::class, 'deleteAccount']);

        Route::post('dark-theme-flag', [UserController::class, 'darkThemeFlag']);
        Route::post('community-guideline-flag', [UserController::class, 'communityGuidelineFlag']);

        

        // Route::post('two-factor-authentication', [UserController::class, 'twoFactorAuthenticationToggle']);
        Route::post('two-factor-authentication', [TwoFactorController::class, 'apiToggle2FA']);
        Route::post('verify-2fa-otp', [TwoFactorController::class, 'apiVerify2FA']);

        // update email
        Route::post('update-email', [UserController::class, 'updateEmail']);
        Route::post('verify-email-otp', [UserController::class, 'verifyEmailOtp']);

        // events
        Route::post('create-event', [EventController::class, 'createEditEvent']);
        Route::post('event-list', [EventController::class, 'myEventList']);
        Route::post('event-details', [EventController::class, 'eventDetails']);
        Route::post('delete-event', [EventController::class, 'deleteEvent']);
        Route::post('delete-event-image', [EventController::class, 'deleteImage']);
        Route::post('all-events', [EventController::class, 'getAllEventList']);
        Route::post('join-event', [EventController::class, 'joinEvent']);
        Route::post('event-category', [EventController::class, 'eventCategory']);

        // posts
        Route::post('create-post', [PostController::class, 'createEditPost']);
        Route::post('post-list', [PostController::class, 'myPostList']);
        Route::post('delete-post', [PostController::class, 'deletePost']);
        Route::post('delete-post-image', [PostController::class, 'deleteImage']);
        Route::post('post-details', [PostController::class, 'postDetails']);
        Route::post('all-posts', [PostController::class, 'getAllPostList']);
        Route::post('other-user-profile', [PostController::class, 'otherUserProfile']);
        Route::post('like-post', [PostController::class, 'likePost']);
        Route::post('like-list', [PostController::class, 'likeList']);
        Route::post('comment-post', [PostController::class, 'addComment']);
        Route::post('comment-list', [PostController::class, 'commentList']);
        Route::post('delete-comment', [PostController::class, 'deleteComment']);

        Route::post('report-post', [PostController::class, 'reportPost']);
        Route::post('report-comment', [PostController::class, 'reportComment']);

        // bookmarks
        Route::post('bookmark-post', [PostController::class, 'bookmarkPost']);
        Route::post('my-bookmark-posts', [PostController::class, 'bookmarkList']);

        // services
        Route::post('create-service', [ServiceController::class, 'createEditService']);
        Route::post('service-list', [ServiceController::class, 'myServiceList']);
        Route::post('service-details', [ServiceController::class, 'serviceDetails']);
        Route::post('delete-service', [ServiceController::class, 'deleteService']);

        // businesses
        Route::post('create-business', [BusinessController::class, 'createOrUpdateBusiness']);
        Route::post('update-business', [BusinessController::class, 'updateBusiness']);
        Route::post('my-business', [BusinessController::class, 'myBusinessList']);
        Route::post('business-services', [BusinessController::class, 'businessServices']);
        Route::post('business-category', [BusinessController::class, 'businessCategory']);
        Route::post('businesses', [BusinessController::class, 'getBusinessByCategory']);
        Route::post('business-details', [BusinessController::class, 'businessDetails'])->name('business.details');
        Route::post('rate-business', [BusinessRatingController::class, 'rateBusiness']);
        Route::post('delete-business-rating', [BusinessRatingController::class, 'deleteBusinessRating']);

        // Rewards Nowis Points
        Route::post('create-nowis-point', [RewardNowisPointController::class, 'createEditNowisPoint']);
        Route::post('nowis-point-list', [RewardNowisPointController::class, 'mynowisPointList']);
        Route::post('nowis-point-details', [RewardNowisPointController::class, 'nowisPointDetails']);
        Route::post('delete-nowis-point', [RewardNowisPointController::class, 'deleteNowisPoint']);
        Route::post('my-nowis-point', [RewardNowisPointController::class, 'allNowisPointList']);
        Route::post('redeem-nowis', [RewardNowisPointController::class, 'redeemNowisPoint']);

        // Rewards Stamp Cards
        Route::post('create-stamp-card', [RewardStampCardController::class, 'createEditStampCard']);
        Route::post('stamp-card-list', [RewardStampCardController::class, 'myStampCardList']);
        Route::post('stamp-card-details', [RewardStampCardController::class, 'stampCardDetails']);
        Route::post('delete-stamp-card', [RewardStampCardController::class, 'deleteStampCard']);
        Route::post('my-stamp-cards', [RewardStampCardController::class, 'businessWithStampCard']);
        Route::post('stamp-cards', [RewardStampCardController::class, 'allStampCardList']);
        Route::post('redeem-stamp-card', [RewardStampCardController::class, 'redeemStampCard']);

        // block un block
        Route::post('block-un-block', [PostController::class, 'blockUnBlockUser']);
        Route::post('block-user-list', [UserController::class, 'blockList']);

        // change language
        Route::post('change-language', [UserController::class, 'changeLanguage']);

        // Favorite
        Route::post('add-to-favorite', [FavoriteController::class, 'favorite']);
        Route::post('my-favorite-list', [FavoriteController::class, 'myFavoriteList']);

        // Chat
        Route::post('start-chat', [MessageController::class, 'startChat']);
        Route::post('send-message', [MessageController::class, 'sendMessage']);
        Route::post('chat-list', [MessageController::class, 'chatList']);
        Route::post('delete-chat', [MessageController::class, 'deleteChat']);
        Route::post('accept-reject-chat', [MessageController::class, 'acceptRejectChat']);


        Route::post('post-help-list', [HelpController::class, 'getCommonFeed']);

        //New Chat
        
        Route::prefix('chat')->group(function () {
            Route::post('send_request', [ChatController::class, 'sendRequest']);
            Route::post('listing', [ChatController::class, 'listing']);
            Route::post('accept_reject', [ChatController::class, 'acceptRejectStatus']);
            Route::post('history', [ChatController::class, 'chatHistory']);
            Route::post('delete_chat_detail', [ChatController::class, 'deleteChatDetail']);
            Route::post('upload_media', [ChatController::class, 'uploadMedia']);
            Route::post('delete', [ChatController::class, 'deleteChat']);

            Route::post('send_message', [ChatController::class, 'sendMessage']);
        });

        Route::prefix('help')->group(function () {
            Route::post('create-or-update', [HelpController::class, 'createEditHelp']);
            Route::post('all-help-list', [HelpController::class, 'getAllHelpList']);
            Route::post('my-help-requests', [HelpController::class, 'getMyhelpRequests']);
            Route::post('received-help-list', [HelpController::class, 'getReceivedHelpRequests']);
            Route::post('help-details', [HelpController::class, 'getHelpDetail']);
            Route::post('offer-help', [HelpController::class, 'iCanHelp']);
            Route::post('old-offer-help', [HelpController::class, 'oldICanHelp']);
            Route::post('accept-decline-help', [HelpController::class, 'acceptDeclineHelpRequest']);
            Route::post('delete-help', [HelpController::class, 'deleteHelp']);
            Route::post('complete-help', [HelpController::class, 'markHelpAsCompleted']);
            Route::post('like', [HelpController::class, 'likeHelp']);
            Route::post('like-list', [HelpController::class, 'likeList']);
            Route::post('add-comment', [HelpController::class, 'addComment']);
            Route::post('comment-list', [HelpController::class, 'commentList']);
            Route::post('bookmark', [HelpController::class, 'bookmarkHelp']);
            Route::post('bookmark-list', [HelpController::class, 'bookmarkList']);
            Route::post('delete-comment', [HelpController::class, 'deleteComment']);
        });

        Route::prefix('report')->group(function () {
            Route::post('create-or-update', [ReportController::class, 'createEditReport']);
            Route::post('type', [ReportController::class, 'reportType']);
            Route::post('delete', [ReportController::class, 'deleteReport']);
            Route::post('my-report-list', [ReportController::class, 'myReportList']);
            Route::post('all-report-list', [ReportController::class, 'allReportList']);
        });
        // Stories
        Route::prefix('stories')->group(function () {
            Route::post('create', [StoryController::class, 'store']);
            Route::post('list', [StoryController::class, 'index']);
            Route::post('view', [StoryController::class, 'view']);
            Route::post('delete', [StoryController::class, 'destroy']);
            Route::post('react/{id}', [StoryController::class, 'react']);
            Route::post('reply/{id}', [StoryController::class, 'reply']);
            Route::post('details', [StoryController::class, 'storyDetails']);
            Route::post('viewer-list', [StoryController::class, 'storyviewerList']);
            Route::post('report', [StoryController::class, 'reportStory']);
            Route::post('respond-to-question', [StoryController::class, 'respondToQuestion']);
            Route::post('vote', [StoryController::class, 'voteToPoll']);
            
        });
        
        // home
        Route::post('home', [HomeController::class, 'home']);
        Route::post('near-by-business', [HomeController::class, 'nearByBusiness']);

        Route::post('open-now-business', [HomeController::class, 'openNowBusiness']);
        Route::post('top-business', [HomeController::class, 'topBusiness']);
        Route::post('banner-details', [HomeController::class, 'bannerDetails']);

        Route::get('scan/qr/{id}', [BusinessController::class, 'scanQr'])->name('business.qr.scan');
        Route::post('regenerate-daily-code', [BusinessController::class, 'regenerateDailyCode']);
        Route::post('generate-verification-code', [BusinessController::class, 'generateVerificationCode']);
        Route::post('verify-verification-code', [BusinessController::class, 'verifyVerificationCode']);

        Route::post('get-cities', [UserController::class, 'getCity']);

        Route::post('change-qr-code', [BusinessController::class, 'changeQrCode']);
        Route::post('map-businesses', [HomeController::class, 'mapBusinesses']);

        Route::post('subscription/plans', [SubscriptionController::class, 'getPlans']);

        Route::post('create-subscription', [SubscriptionController::class, 'createSubscription']);
        Route::post('cancel-subscription', [SubscriptionController::class, 'cancelSubscription']);
        Route::post('subscription/process', [SubscriptionController::class, 'process']);
        Route::post('subscription/webhook', [SubscriptionController::class, 'webhook']);
    });
    Route::post('subscription/webhook', [SubscriptionController::class, 'webhook']);

    Route::post('/test-push', function (\Illuminate\Http\Request $request) {
        send_push($request->id, [
            'from_user_id' => 1,
            'push_title' => 'Test Push',
            'push_message' => 'Hello, this is test push 🚀',
            'push_type' => 1,
        ], true);
        return ['status' => 'push_sent'];
    });
});

<?php

use App\Mail\User_Password_Send_OTP_Mail;
use App\Models\BusinessPostPermission;
use App\Models\User;
use App\Models\Device;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\PushLog;
use App\Models\Transaction;
use App\Models\UserEmailLog;
use Carbon\Carbon;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
// use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

if (!function_exists('get_constants')) {
    function get_constants($name)
    {
        return config('constants.' . $name);
    }
}

if (!function_exists('generateUsername')) {
    function generateUsername($name)
    {
        // Convert the name to a URL-friendly version
        $baseUsername = Str::slug($name);

        // Step 1: Check if username exists
        $username = $baseUsername;
        $counter = 1;

        // Step 2: Append a number to make it unique if needed
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }
}

if (!function_exists('get_header_auth_token')) {
    function get_header_auth_token()
    {
        $full_token = request()->header('VAuthorization');
        return (substr($full_token, 0, 7) === 'Bearer ') ? substr($full_token, 7) : null;
    }
}

if (!function_exists('checkFileExist')) {
    function checkFileExist($path = '', $no_image = 'no_image')
    {
        $disk = config('constants.upload_type', 'local');
        if (!empty($path)) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            // Avoid remote network exists checks for s3 disk to keep API response times fast
            if ($disk === 's3' || \Storage::disk($disk)->exists($path)) {
                return \Storage::disk($disk)->url($path);
            }
        }

        if ($no_image == 'no_user_image') {
            return url('default/no_user_image.png');
        }
        return url('default/' . $no_image . '.png');
    }
}

if (!function_exists('upload_file')) {
    function upload_file($file_name = "", $path = "")
    {
        $request = \request();

        if ($request->hasFile($file_name) && $path) {
            $resolvedPath = config('constants.upload_paths.' . $path, 'default/path');

            try {
                $disk = config('constants.upload_type', 'local');
                $file = $request->file($file_name)->store($resolvedPath, $disk);
                return $file;
            } catch (\Exception $e) {
                \Log::error('File upload failed: ' . $e->getMessage());
                \Log::error('Trace: ' . $e->getTraceAsString());
                if ($e->getPrevious()) {
                    \Log::error('Previous: ' . get_class($e->getPrevious()) . ' - ' . $e->getPrevious()->getMessage());
                }
                return false;
            }
        } else {
            echo 'Provide Valid Const from web controller';
            die();
        }
    }
}
if (!function_exists('un_link_file')) {
    function un_link_file($image_name = "")
    {
        $result = true; // Default to success
        if (!empty($image_name)) {
            try {
                // Get base URL and default images
                $default_url = URL::to('/');
                $default_images = config('constants.default');
                $file_name = str_replace($default_url . '/', '', $image_name);
                $default_image_list = is_array($default_images) ? str_replace($default_url, '', array_values($default_images)) : [];
                if (!in_array($file_name, $default_image_list)) {
                    Storage::disk(get_constants('upload_type'))->delete($file_name);
                }
            } catch (Exception $exception) {
                // Log the exception for debugging
                \Log::error('File Deletion Error: ' . $exception->getMessage());

                // Set result to false on failure
                $result = false;
            }
        } else {
            // If image name is empty, log and return a failure message
            \Log::warning('Empty file name provided to un_link_file function');
            $result = false;
        }

        return $result;
    }
}

if (!function_exists('get_dashboard_route_name')) {
    function get_dashboard_route_name()
    {
        $name = 'front.dashboard';
        $user_data = Auth::user();
        if ($user_data) {
            if (in_array($user_data->type, ["admin", "sub-admin"])) {
                $name = 'admin.dashboard';
            } else if (in_array($user_data->type, ["businessuser", "sub-businessuser"])) {
                $name = 'user.dashboard';
            }
        }
        return $name;
    }
}

if (!function_exists('genUniqueStr')) {
    function genUniqueStr($table, $field = null, $length = 10, $prefix = null, $isAlphaNum = false)
    {
        // Define character sets
        $numeric = range(0, 9);
        $alpha = array_merge(range('a', 'z'), range('A', 'Z'));

        // Create character pool
        $characters = $numeric;
        if ($isAlphaNum) {
            $characters = array_merge($characters, $alpha);
        }

        $maxLen = max($length - strlen($prefix), 0);
        $attempts = 0;
        $token = '';

        do {
            $token = $prefix;

            // Generate token
            for ($i = 0; $i < $maxLen; $i++) {
                $token .= $characters[array_rand($characters)];
            }

            $attempts++;
            // Add a limit to prevent infinite loop (e.g., 10 attempts)
            if ($attempts > 10) {
                throw new Exception("Unable to generate a unique string after multiple attempts.");
            }

        } while (isTokenExist($token, $table, $field));

        return $token;
    }
}

if (!function_exists('isTokenExist')) {
    function isTokenExist($token, $table, $field)
    {
        return !empty($token) && DB::table($table)->where($field, $token)->exists();
    }
}

if (!function_exists('flash_session')) {
    function flash_session($type = '', $value = "")
    {
        session()->flash('message', ['type' => $type, 'text' => $value]);
    }
}

if (!function_exists('get_error_html')) {
    function get_error_html($error)
    {
        $content = '';
        if ($error->any() !== null && $error->any()) {
            foreach ($error->all() as $value) {
                flash_session('error', $value);
            }
        }
        return $content;
    }
}

if (!function_exists('is_active_module')) {
    function is_active_module($routes)
    {
        return in_array(Route::currentRouteName(), $routes) ? 'mm-active' : '';
    }
}

if (!function_exists('echo_extra_for_site_setting')) {
    function echo_extra_for_site_setting($extra = "")
    {
        $attributes = '';

        // Decode the extra attributes if JSON is valid
        $extraData = json_decode($extra, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($extraData)) {
            foreach ($extraData as $key => $value) {
                // Escape attribute name and value to prevent XSS
                $attributes .= htmlspecialchars($key) . '="' . htmlspecialchars($value) . '" ';
            }
        }

        return $attributes;
    }
}

if (!function_exists('get_fancy_box_html')) {
    function get_fancy_box_html($imagePath, $class = "avatar-sm")
    {
        if (!$imagePath) {
            return '';
        }

        return '<a data-fancybox="" href="' . $imagePath . '">
                <img class="img-thumbnail ' . $class . '" src="' . $imagePath . '" alt="Profile Image">
            </a>';
    }
}

if (!function_exists('get_generate_switch')) {
    function get_generate_switch($status, $id, $route)
    {
        $checked = $status == 'active' ? 'checked' : '';
        return '<div class="form-check form-switch form-switch-md mb-3" dir="ltr">
                    <input class="form-check-input status-switch" type="checkbox" id="SwitchCheckSizemd_' . $id . '" data-id="' . $id . '" data-url="' . $route . '" ' . $checked . '>
                </div>';
    }
}

if (!function_exists('generate_actions_buttons')) {
    function generate_actions_buttons($params = [], $extra = [], $target = false)
    {
        $operation = '';
        $targetAttr = $target ? 'target="_blank"' : '';

        // Standard action buttons (view, edit, delete)
        $actions = [
            'edit' => [
                'icon' => 'mdi mdi-pencil',
                'class' => 'btn btn-sm btn-outline-success waves-effect waves-light btnEdit',
                'title' => 'Edit'
            ],
            'view' => [
                'icon' => 'mdi mdi-eye',
                'class' => 'btn btn-sm btn-outline-primary waves-effect waves-light btnView',
                'title' => 'View'
            ],
            'delete' => [
                'icon' => 'mdi mdi-trash-can',
                'class' => 'btn btn-sm btn-outline-danger waves-effect waves-light btnDelete',
                'title' => 'Delete',
                'data-confirm' => 'Are you sure you want to delete this item?',
            ]
        ];

        foreach ($actions as $action => $details) {
            if (isset($params['url'][$action])) {
                $operation .= sprintf(
                    '<a title="' . $details['title'] . '" href="' . $params['url'][$action] . '" data-id="%s" class="font-size-14 me-2 ' . $details['class'] . '">
                                    <i class="' . $details['icon'] . '"></i>
                                 </a>',
                    $details['title'],
                    $targetAttr,
                    $params['url'][$action],
                    $params['id'],
                    $details['class'],
                    $details['icon']
                );
            }
        }

        return $operation;
    }
}

if (!function_exists('get_badge_html')) {
    function get_badge_html($status)
    {
        if ($status == 'active') {
            $badge = '<span class="badge bg-success">' . ucfirst($status) . '</span>';
        } else if ($status == 'inactive') {
            $badge = '<span class="badge bg-danger">' . ucfirst($status) . '</span>';
        } else {
            $badge = '<span class="badge bg-primary">' . ucfirst($status) . '</span>';
        }
        return $badge;
    }
}

if (!function_exists('register_user_email')) {
    function register_user_email($email)
    {
        // Check if environment is local
        if (app()->environment('local')) {
            // Static OTP for local environment (development)
            $otp = 123456;
        } else {
            // Random OTP for production/live environment
            $otp = rand(111111, 999999);
        }

        // Update or create user email log entry
        $user = UserEmailLog::updateOrCreate(
            ['email' => $email],
            ['email' => $email]
        );

        if ($user) {
            // Update OTP and timestamp
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ]);

            // Send OTP email
            Mail::to($user->email)->send(new User_Password_Send_OTP_Mail($otp));

            return true;
        }
        return false;
    }
}

if (!function_exists('getSupportedLanguages')) {
    function getSupportedLanguages()
    {
        return [
            'en' => 'English',
            'de' => 'German',
        ];
    }
}

if (!function_exists('qrCode')) {
    function qrCode($url)
    {
        $qrCode = QrCode::format('svg')->size(300)
            ->generate($url);

        $base64 = base64_encode($qrCode);

        return 'data:image/svg+xml;base64,' . $base64;
    }
}

if (!function_exists('upload_base_64_img')) {
    function upload_base_64_img($base64 = "", $path = "")
    {
        $file = null;

        $disk = config('constants.upload_type', 'local');
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $data = substr($base64, strpos($base64, ',') + 1);
            $decoded = base64_decode($data, true);

            if ($decoded !== false) {
                $resolvedPath = $path ?: 'default/path';
                $up_file = rtrim($resolvedPath, '/') . '/' . md5(uniqid()) . '.' . $extension;

                $saved = Storage::disk($disk)->put($up_file, $decoded);

                if ($saved) {
                    $file = $up_file;
                }
            }
        }

        return $file;
    }
}


if (!function_exists('send_push')) {
    function send_push($user_id = 0, $data = [], $notification_entry = false)
    {
        $main_name = defined('site_name') ? site_name : env('APP_NAME');
        $push_data = [
            'user_id' => (string) $user_id,
            'from_user_id' => isset($data['from_user_id']) ? (string) $data['from_user_id'] : null,
            'sound' => 'default',
            'push_type' => isset($data['push_type']) ? (string) $data['push_type'] : '0',
            'push_title' => $data['push_title'] ?? $main_name,
            'push_message' => $data['push_message'] ?? "",
            'image' => $data['image'] ?? "",
            'message' => $data['message'] ?? "",
            'extra' => isset($data['extra']) ? json_encode($data['extra'], true) : null
        ];

        // access push subscription base
        $limits = getBusinessUserFinalPostLimits($user_id);
        $user = User::find($user_id);

        $maxPush = $limits['max_push'] ?? PHP_INT_MAX;

        // No push permission at all
        if ($maxPush === 0) {
            PushLog::add_log($user_id, $push_data['from_user_id'], $push_data['push_type'], "No push notification access");
            return;
        }

        // Limited (NOT unlimited)
        if ($user->push_used >= $maxPush) {
            PushLog::add_log($user_id, $push_data['from_user_id'], $push_data['push_type'], "Push notification limit exceeded");
            return;
        }

        $fcm_config_file = config('constants.firebase_credentials');
        if ($fcm_config_file && file_exists($fcm_config_file)) {
            $credentials = json_decode(file_get_contents($fcm_config_file), true);
            if ($push_data['user_id'] !== $push_data['from_user_id']) {
                $get_user_tokens = Device::get_user_tokens($user_id);
                if (count($get_user_tokens)) {
                    foreach ($get_user_tokens as $value) {
                        $push_status = "Sent";
                        // $value->update(['badge' => $value->badge + 1]);
                        try {
                            $device_token = $value['device_token'];
                            if ($device_token) {
                                $url = 'https://fcm.googleapis.com/v1/projects/' . $credentials['project_id'] . '/messages:send';
                                $token = getAccessToken($credentials);
                                $headers = [
                                    "Authorization: Bearer $token",
                                    'Content-Type: application/json'
                                ];
                                 $user_lang = $user->language ?? 'de';
                                 $lang = str_contains(strtolower($user_lang), 'en') ? 'en' : 'de';

                                 $translated_title = translate_push_text($push_data['push_title'], $lang);
                                 $translated_message = translate_push_text($push_data['push_message'], $lang);

                                 $local_push_data = $push_data;
                                 $local_push_data['push_title'] = $translated_title;
                                 $local_push_data['push_message'] = $translated_message;

                                 $payload = [
                                     'token' => $device_token,
                                     'data' => $local_push_data,
                                 ];
                                 if ($local_push_data['push_type'] != 5) {
                                     $payload['notification'] = [
                                         'title' => $local_push_data['push_title'],
                                         'body' => $local_push_data['push_message'],
                                     ];
                                 }
                                 if ($local_push_data['push_type'] == 5) {
 
                                     // iOS - Interactive reply (keyboard open)
                                     $payload['apns'] = [
                                         'payload' => [
                                             'aps' => [
                                                 'alert' => [
                                                     'title' => $local_push_data['push_title'],
                                                     'body'  => $local_push_data['push_message']
                                                 ],
                                                 'sound' => 'default',
                                                 'badge' => 0,
                                                 'category' => 'message' // enables reply action
                                             ]
                                         ]
                                     ];
 
                                     $payload['android'] = [
                                         'priority' => 'high'
                                     ];
                                     
                                 }

                                $ch = curl_init();
                                curl_setopt_array($ch, array(
                                    CURLOPT_URL => $url,
                                    CURLOPT_POST => true,
                                    CURLOPT_HTTPHEADER => $headers,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_SSL_VERIFYPEER => false,
                                    CURLOPT_POSTFIELDS => json_encode(['message' => $payload]),
                                ));
                                $result = curl_exec($ch);
                                if (curl_errno($ch)) {
                                    $push_status = 'Curl Error:' . curl_error($ch);
                                }
                                curl_close($ch);
                                if (config('constants.push_log')) {
                                    PushLog::add_log($user_id, $push_data['from_user_id'], $push_data['push_type'], $push_status, json_encode($push_data), $result);
                                }
                            } else {
                                PushLog::add_log($user_id, $push_data['from_user_id'], $push_data['push_type'], "Token Is empty");
                            }
                        } catch (Exception $e) {
                            if (config('constants.push_log')) {
                                PushLog::add_log($user_id, $push_data['from_user_id'], $push_data['push_type'], $e->getMessage());
                            }
                        }
                    }
                } else {
                    if (config('constants.push_log')) {
                        PushLog::add_log($user_id, $push_data['from_user_id'], $push_data['push_type'], "Users Is not A Login With app");
                    }
                }
                if ($notification_entry) {
                    Notification::create([
                        'push_type' => $push_data['push_type'],
                        'user_id' => $push_data['user_id'],
                        'from_user_id' => $push_data['from_user_id'],
                        'title' => $push_data['push_title'],
                        'message' => $push_data['push_message'],
                        'extra' => (isset($data['extra']) && !empty($data['extra'])) ? json_encode($data['extra']) : json_encode([]),
                    ]);
                }
            } else {
                if (config('constants.push_log')) {
                    PushLog::add_log($user_id, $push_data['from_user_id'], $push_data['push_type'], "User Cant Sent Push To Own Profile.");
                }
            }
        } else {
            if (config('constants.push_log')) {
                PushLog::add_log($user_id, $push_data['from_user_id'], $push_data['push_type'], "FCM config file not found");
            }
        }
    }
}

if (!function_exists('getAccessToken')) {
    function getAccessToken($serviceAccountPath)
    {
        $client = new Google\Client();
        $client->setAuthConfig($serviceAccountPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    }
}

if (!function_exists('generate_video_thumbnail')) {
    function generate_video_thumbnail($videoPath, $thumbnailFullPath)
    {
        try {
            $videoFullPath = public_path($videoPath);

            if (!file_exists($videoFullPath)) {
                return false;
            }
            $ffmpeg = FFMpeg::create([
                'timeout' => 3600,
                'threads' => 12,
            ]);
            $video = $ffmpeg->open($videoFullPath);

            // Ensure thumbnail directory exists
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0775, true);
            }

            $frame = $video->frame(TimeCode::fromSeconds(1));
            $frame->save($thumbnailFullPath);

            return true;

        } catch (\Exception $e) {
            \Log::error('Thumbnail generation failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getUserPlanLimits')) {

    function getUserPlanLimits($userId)
    {
        $user = User::find($userId);

        if (!$user || !in_array($user->type, ['businessuser', 'sub-businessuser'])) {

            return [
                'has_subscription' => true,
                'event_limit' => PHP_INT_MAX,
                'feed_post_limit' => PHP_INT_MAX,
                'push_notification_limit' => PHP_INT_MAX,
                'stamp_card' => true,
                'visibility' => 'normal',
            ];
        }

        $rules = config('plan_rules');

        $transaction = Transaction::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('status', 'active')
                    ->orWhere('payment_status', 'active')
                    ->orWhereNotNull('stripe_subscription_id');
            })
            ->latest()
            ->first();

        if (!$transaction || !$transaction->plan_id) {
            return [
                'has_subscription' => false,
                'message' => 'Please purchase a subscription.'
            ];
        }

        $plan = Plan::find($transaction->plan_id);

        if (!$plan) {
            return [
                'has_subscription' => false,
                'message' => 'Please purchase a subscription.'
            ];
        }

        $planName = $plan->name;
        $limits = $rules[$planName] ?? null;

        if (!$limits) {
            return [
                'has_subscription' => false,
                'message' => 'Please purchase a subscription.'
            ];
        }

        $limits['has_subscription'] = true;
        $limits['plan_name'] = $planName;

        return $limits;
    }
    function lastSeenShort($lastOnline): string
    {
        $last = $lastOnline instanceof Carbon ? $lastOnline : Carbon::parse($lastOnline);
        $now = Carbon::now();

        $mins = $last->diffInMinutes($now);
        if ($mins <= 59) {
            return $mins === 1 ? '1 min ago' : "{$mins} mins ago";
        }

        $hours = $last->diffInHours($now);
        if ($hours <= 23) {
            return $hours === 1 ? '1 hr ago' : "{$hours} hrs ago";
        }

        $days = $last->diffInDays($now);
        return $days === 1 ? '1 day ago' : "{$days} days ago";
    }
}

if (!function_exists('getBusinessUserFinalPostLimits')) {

    function getBusinessUserFinalPostLimits($userId)
    {
        $user = User::find($userId);
        if ($user && $user->type === 'sub-businessuser') {
            $userId = $user->parent_id;
            $user = User::find($userId);
        }

        // 1️⃣ NORMAL USER (not businessuser)
        if (!$user || !in_array($user->type, ["businessuser", "sub-businessuser"])) {

            return [
                'max_posts' => PHP_INT_MAX,
                'max_events' => PHP_INT_MAX,
                'max_push' => PHP_INT_MAX,

                'has_subscription' => true,
                'message' => null,
                'source' => 'free-user',
            ];
        }

        // 2️⃣ ADMIN CUSTOM LIMIT
        $admin = BusinessPostPermission::where('user_id', $userId)->first();

        // 3️⃣ PLAN LIMITS (your old system)
        $planLimits = getUserPlanLimits($userId);

        // 3A️⃣ No subscription found
        if (!$planLimits['has_subscription']) {

            return [
                'max_posts' => $admin->max_posts ?? 0,
                'max_events' => $admin->max_events ?? 0,
                'max_push' => $admin->max_push ?? 0,

                'has_subscription' => false,
                'message' => $planLimits['message'],
                'source' => 'plan',
            ];
        }

        // 4️⃣ FINAL MERGED LIMITS
        $finalMaxPosts = $admin && !is_null($admin->max_posts)
            ? $admin->max_posts
            : $planLimits['feed_post_limit'];

        $finalMaxEvents = $admin && !is_null($admin->max_events)
            ? $admin->max_events
            : $planLimits['event_limit'];

        $finalMaxPush = $admin && !is_null($admin->max_push)
            ? $admin->max_push
            : $planLimits['push_notification_limit'];

        return [
            'max_posts' => $finalMaxPosts,
            'max_events' => $finalMaxEvents,
            'max_push' => $finalMaxPush,

            'has_subscription' => true,
            'message' => null,
            'source' => $admin ? 'admin' : 'plan',
            'plan_name' => $planLimits['plan_name'] ?? null,
        ];
    }
}

if (!function_exists('s3ToCloudFrontUrl')) {
    function s3ToCloudFrontUrl($s3Path, $cloudFrontUrl = '')
    {
        if (empty($s3Path)) {
            return '';
        }
        if (empty($cloudFrontUrl)) {
            $cloudFrontUrl = rtrim((string) env('AWS_URL'), '/');
        }
        if (empty($cloudFrontUrl)) {
            $cloudFrontUrl = "https://d1iy16yl9m5xuf.cloudfront.net"; // Fallback as a last resort
        }
        // Remove s3://bucket-name/
        $path = preg_replace('#^s3://[^/]+/#', '', $s3Path);
        return rtrim($cloudFrontUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('translate_push_text')) {
    function translate_push_text($text, $lang)
    {
        if (empty($text)) {
            return $text;
        }

        if ($lang === 'en') {
            return $text;
        }

        // Dynamically set app locale to fetch translations for target language
        $originalLocale = app()->getLocale();
        app()->setLocale($lang);
        $translations = __('api.push_translations');
        app()->setLocale($originalLocale);

        if (!is_array($translations) || empty($translations)) {
            return $text;
        }

        // 1. Check exact match
        if (isset($translations[$text])) {
            return $translations[$text];
        }

        // 2. Check substring replacement
        $translatedText = $text;
        foreach ($translations as $englishSubstring => $translation) {
            if (strlen($englishSubstring) > 5 && str_contains(strtolower($text), strtolower($englishSubstring))) {
                $translatedText = str_ireplace($englishSubstring, $translation, $translatedText);
            }
        }

        return $translatedText;
    }
}
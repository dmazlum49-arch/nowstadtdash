<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    public function getLogin()
    {
        return view('admin.guest.login', ['title' => __('admin.login')]);
    }

    public function postLogin(Request $request)
    {
        // Validate the request input
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Determine if username is an email or username
        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Prepare credentials with 'active' status requirement
        $credentials = [
            $loginField => $request->username,
            'password' => $request->password,
            'status' => 'active',
        ];

        // Attempt authentication with the specified credentials and remember option
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            return redirect()->route(get_dashboard_route_name());
        }

        // Set error message based on login field type
        $errorMessage = $loginField === 'email'
            ? 'Please enter a valid email or password'
            : 'Please enter a valid username or password';

        // Store the error message in session and redirect back
        flash_session('error', $errorMessage);
        return redirect()->back();
    }

    public function showLinkRequestForm()
    {
        return view('admin.guest.forgot-password', ['title' => __('admin.forgot_password')]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        User::passwordReset($request->email);
        return redirect()->back();
    }

    public function showResetForm($token)
    {
        return view('admin.guest.reset-password', ['title' => __('admin.reset_password'), 'token' => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'reset_token' => ['required', Rule::exists('users', 'reset_token')->whereNull('deleted_at')],
            'password' => ['required'],
        ], [
            'reset_token.exists' => 'Invalid password token',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 6 characters long.',
        ]);
        $user = User::where('reset_token', $request->reset_token)->firstOrFail();
        $user->update([
            'password' => $request->password,
            'reset_token' => null,
        ]);
        flash_session('success', 'Password updated successfully');
        ;
        return redirect()->route('admin.login');
    }

    // Webhook for MediaConvert completion (called by SNS/EventBridge)
    public function mediaConvertWebhook(Request $request)
    {
        $cloudFrontUrl = rtrim((string) env('AWS_URL'), '/');
        \Log::info('MediaConvert Webhook Received', $request->all());

        // 📦 Extract detail safely
        $detail = $request->input('detail', []);

        $jobId   = $detail['jobId']   ?? null;
        $status  = $detail['status']  ?? null;
        $meta    = $detail['userMetadata'] ?? [];

        // 🔑 IDENTIFIER (FROM LAMBDA UserMetadata)
        $inputS3Key = $meta['input_s3_key'] ?? null;
        $hlsFolder  = $meta['hls_folder']   ?? null;

        if (!$jobId || !$status) {
            \Log::warning('Invalid MediaConvert payload', $detail);
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // 🔍 Find media model in DB
        $model = null;
        if ($inputS3Key) {
            // 1. Check Story
            $model = \App\Models\Story::where('media_path', $inputS3Key)->first();

            // 2. Check ThreadMessage
            if (!$model) {
                $model = \App\Models\ThreadMessage::where('file', $inputS3Key)->first();
            }

            // 3. Check PostImages
            if (!$model) {
                $model = \App\Models\PostImages::where('image_path', $inputS3Key)->first();
            }

        }

        if (!$model) {
            \Log::warning('MediaConvert media not found', [
                'job_id' => $jobId,
                's3_key' => $inputS3Key,
            ]);
        }
        else {
            \Log::info('MediaConvert media found', [
                'job_id' => $jobId,
                's3_key' => $inputS3Key,
                "status" => $status,
                'model' => get_class($model)
            ]);
        }

        // ✅ JOB COMPLETED
        if ($status === 'COMPLETE') {

            // Extract playlist path
            $playlistPath = $detail['outputGroupDetails'][0]['playlistFilePaths'][0] ?? null;

            \Log::info('MediaConvert job completed', [
                'job_id'   => $jobId,
                'playlist' => $playlistPath,
            ]);

            if ($model) {
                $updateData = [
                    'hls_path'   => s3ToCloudFrontUrl($playlistPath, $cloudFrontUrl),
                    'hls_status' => 'ready',
                ];

                // Set status to active only for legacy Product/Step models if applicable
                $modelClass = get_class($model);
                if ($modelClass === 'App\Models\Product' || $modelClass === 'App\Models\Step') {
                    $updateData['status'] = 'active';
                }

                $model->update($updateData);
            }

        }

        // ❌ JOB FAILED
        if ($status === 'ERROR') {
            \Log::error('MediaConvert job failed', [
                'job_id' => $jobId,
                'errors' => $detail['errorDetails'] ?? [],
            ]);

            if ($model) {
                $model->update([
                    'hls_status' => 'failed',
                ]);
            }
        }

        // 🚫 JOB CANCELED
        if ($status === 'CANCELED') {
            \Log::warning('MediaConvert job canceled', [
                'job_id' => $jobId,
            ]);

            if ($model) {
                $model->update([
                    'hls_status' => 'canceled',
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

}

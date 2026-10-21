<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\HomeBanner;
use App\Models\Event;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\EventCategory;
use App\Models\City;
use App\Models\Service;
use App\Models\Content;
use App\Models\Post;
use App\Models\VersionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class GeneralController extends Controller
{
    public function dashboard()
    {
        return view('user.general.dashboard', [
            'title' => __('admin.dashboard'),
            'business_count' => Business::where(['user_id' => Auth::user()->primaryId()])->count(),
            'event_count' => Event::where(['user_id' => Auth::user()->primaryId()])->count(),
            'post_count' => Post::where(['user_id' => Auth::user()->primaryId()])->count(),
        ]);
    }

    public function getProfile(Request $request)
    {
        return view('user.general.profile', [
            'title' => __('admin.profile'),
            'user' => $request->user(),
            'breadcrumbs' => [
                __('admin.profile') => route('user.profile'),
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'profile_image' => ['file', 'image'],
            'name' => ['required', 'max:255'],
            'username' => ['required', 'max:255', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'email' => ['required', 'max:255', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
        ]);

        // Initialize profile image with the original one
        $profile_image = $user->getRawOriginal('profile_image');

        // Check if a new profile image is uploaded
        if ($request->hasFile('profile_image')) {
            $upload = upload_file('profile_image', 'profile_images');
            if ($upload) {
                un_link_file($profile_image);
                $profile_image = $upload;
            } else {
                flash_session('error', 'Profile image upload failed');
                return redirect()->back();
            }
        }
        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'profile_image' => $profile_image,
        ]);

        flash_session('success', 'Profile Updated successfully');
        return redirect()->back();
    }

    public function showChangePasswordForm(Request $request)
    {
        return view('user.general.change-password', [
            'title' => __('user.change_password'),
            'user' => $request->user(),
            'breadcrumbs' => [
                __('user.change_password') => route('user.change-password'),
            ]
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'opassword' => ['required'],
            'npassword' => ['required', 'min:6'],
        ]);
        if (Hash::check($request->opassword, $user->getAuthPassword())) {
            $is_update = $user->update(['password' => $request->npassword]);
            if ($is_update) {
                flash_session('success', __('admin.change_password_updated'));
            } else {
                flash_session('error', __('admin.change_password_updated'));
            }
        } else {
            flash_session('error', __('admin.change_password_not_match'));
        }
        return redirect()->back();
    }

    public function logout()
    {
        $name = get_dashboard_route_name();
        Auth::logout();
        return redirect()->route($name);
    }

    public function availabilityChecker(Request $request)
    {
        $count = 0;
        $type = $request->type;
        $val = $request->val;
        $user_id = Auth::id() ?? 0;
        if ($type == "username" || $type == "email") {
            $count = User::where($type, $val)->where('id', '!=', $user_id)->count();
        }
        return $count ? "false" : "true";
    }

    public function availabilityCheckerUser(Request $request)
    {
        $count = 0;
        $type = $request->type;
        $val = $request->val;
        $user_id = $request->id ?? 0;
        if ($type == "username" || $type == "email") {
            $count = User::where($type, $val)->where('id', '!=', $user_id)->count();
        }
        return $count ? "false" : "true";
    }
}

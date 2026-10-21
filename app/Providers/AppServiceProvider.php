<?php

namespace App\Providers;

use App\Models\GeneralSetting;
use App\Services\SidebarService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sandbox preview only: the preview proxy terminates TLS and forwards the request over
        // plain HTTP on the sandbox-internal host, so Laravel would emit redirect and asset URLs
        // on that host — which the browser blocks as the wrong origin / mixed content, leaving
        // every redirect (e.g. after login) unusable. Generate URLs on the public preview origin
        // instead. Unset, or any value other than '1', keeps the original behaviour.
        if (env('BASE44_PREVIEW_MODE') === '1' && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // Check if the 'general_settings' table exists
        if (Schema::hasTable('general_settings')) {
            // If the table exists, fetch the settings and store them in the config
            $settings = GeneralSetting::pluck('value', 'unique_name')->toArray();
            config(['general_settings' => $settings]);
        } else {
            // Handle the case when the table doesn't exist, e.g., set default settings or log an error
            // For example, you can set an empty array or a default value
            config(['general_settings' => []]);
        }

        View::composer('*', function ($view) {
            $sidebarMenu = app(SidebarService::class)->getAdminSidebarMenu();
            $view->with('sidebarMenu', $sidebarMenu);
        });
        Schema::defaultStringLength(191);
    }
}

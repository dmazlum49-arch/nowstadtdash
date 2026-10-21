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
        // Sandbox preview only: the Base44 preview proxy terminates TLS and forwards the
        // request over plain HTTP, so Laravel would emit http:// asset and redirect URLs
        // that the browser blocks as mixed content. Unset (or any other value) keeps the
        // original behaviour.
        if (env('BASE44_PREVIEW_MODE') === '1') {
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

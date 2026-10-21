<?php

namespace App\Providers;

use App\Models\GeneralSetting;
use App\Services\SidebarService;
use Illuminate\Support\ServiceProvider;
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

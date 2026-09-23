<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //share admin panel setting model with layouts
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('admin_panel_settings')) {
                View::share('adminPanelSetting', \App\Models\AdminPanelSetting::first());
            }
        } catch (\Throwable $e) {
            // Ignore during initial migrations or when database is not yet initialized
        }
    }
}

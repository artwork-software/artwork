<?php

namespace App\Providers;

use App\Models\GlobalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if(Schema::hasTable('global_notifications')){
            $globalNotification = GlobalNotification::first();
            Inertia::share('globalNotification', $globalNotification ?? null);
        }
    }
}

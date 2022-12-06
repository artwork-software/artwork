<?php

namespace App\Providers;

use App\Models\GlobalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use MeiliSearch\Client;
use MeiliSearch\MeiliSearch;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }
}

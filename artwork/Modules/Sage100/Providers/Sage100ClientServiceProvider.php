<?php

namespace Artwork\Modules\Sage100\Providers;

use Artwork\Modules\Budget\Services\SageBookingLogRecorder;
use Artwork\Modules\Sage100\Clients\SageClient;
use Artwork\Modules\Sage100\Clients\SageClientFactory;
use Illuminate\Support\ServiceProvider;

class Sage100ClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SageClient::class, function ($app) {
            return $app->make(SageClientFactory::class)->createClient();
        });

        // Shared recording context for import/delete runs — must be a singleton so the two
        // data services, Sage100Service and SageBookingDataDeleteService use the same instance.
        $this->app->singleton(SageBookingLogRecorder::class);
    }
}

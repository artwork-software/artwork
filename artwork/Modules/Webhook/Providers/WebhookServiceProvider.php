<?php

namespace Artwork\Modules\Webhook\Providers;

use Artwork\Modules\Webhook\Services\WebhookDispatcher;
use Artwork\Modules\Webhook\Services\WebhookSignature;
use Illuminate\Support\ServiceProvider;

class WebhookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Vier Ebenen hoch von artwork/Modules/Webhook/Providers/ auf das Projektverzeichnis.
        $this->mergeConfigFrom(
            dirname(__DIR__, 4) . '/config/webhooks.php',
            'webhooks'
        );

        $this->app->singleton(WebhookSignature::class);
        $this->app->singleton(WebhookDispatcher::class);
    }
}

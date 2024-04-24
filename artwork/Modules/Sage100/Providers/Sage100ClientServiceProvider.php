<?php

namespace Artwork\Modules\Sage100\Providers;

use Artwork\Modules\Sage100\Clients\Sage100Client;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Support\ServiceProvider;

class Sage100ClientServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind(Sage100Client::class, function () {
            $sageApiSettings = app(SageApiSettingsService::class)->getFirst();

            return new Sage100Client(
                $sageApiSettings?->host,
                $sageApiSettings?->endpoint,
                $sageApiSettings?->user,
                $sageApiSettings?->password
            );
        });
    }

    /**
     * @return array<Sage100Client>
     */
    public function provides(): array
    {
        return [Sage100Client::class];
    }
}

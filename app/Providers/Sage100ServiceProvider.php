<?php

namespace App\Providers;

use App\Sage100\Sage100;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Support\ServiceProvider;

class Sage100ServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind(Sage100::class, function () {
            $sageApiSettings = app(SageApiSettingsService::class)->getFirst();
            return new Sage100(
                $sageApiSettings?->host,
                $sageApiSettings?->endpoint,
                $sageApiSettings?->user,
                $sageApiSettings?->password
            );
        });
    }

    /**
     * @return array<Sage100>
     */
    public function provides(): array
    {
        return [Sage100::class];
    }
}

<?php

namespace Artwork\Modules\Sage100\Clients;

use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;

class SageClientFactory
{
    public function createClient(): SageClient
    {
        if (!config('services.sage.enabled')) {
            return new NullSageClient();
        }

        $sageApiSettings = app(SageApiSettingsService::class)->getFirst();

        return new Sage100Client(
            $sageApiSettings?->host,
            $sageApiSettings?->endpoint,
            $sageApiSettings?->user,
            $sageApiSettings?->password
        );
    }
}

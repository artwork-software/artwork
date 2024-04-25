<?php

namespace Artwork\Modules\ServiceProvider\Services;

use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderRepository;

readonly class ServiceProviderService
{
    public function __construct(private ServiceProviderRepository $serviceProviderRepository)
    {
    }
}

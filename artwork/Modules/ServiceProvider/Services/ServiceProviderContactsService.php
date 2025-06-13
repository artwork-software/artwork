<?php

namespace Artwork\Modules\ServiceProvider\Services;

use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderContactsRepository;

readonly class ServiceProviderContactsService
{
    public function __construct(private ServiceProviderContactsRepository $serviceProviderContactsRepository)
    {
    }
}

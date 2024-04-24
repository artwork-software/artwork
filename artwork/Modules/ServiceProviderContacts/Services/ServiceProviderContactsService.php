<?php

namespace Artwork\Modules\ServiceProviderContacts\Services;

use Artwork\Modules\ServiceProviderContacts\Repositories\ServiceProviderContactsRepository;

readonly class ServiceProviderContactsService
{
    public function __construct(private ServiceProviderContactsRepository $serviceProviderContactsRepository)
    {
    }
}

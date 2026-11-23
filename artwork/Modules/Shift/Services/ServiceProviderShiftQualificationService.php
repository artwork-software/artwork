<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Repositories\ServiceProviderShiftQualificationRepository;

readonly class ServiceProviderShiftQualificationService
{
    public function __construct(
        private ServiceProviderShiftQualificationRepository $serviceProviderShiftQualificationRepository
    ) {
    }
}

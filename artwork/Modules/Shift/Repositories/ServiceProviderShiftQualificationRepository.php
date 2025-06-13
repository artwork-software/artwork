<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ServiceProviderShiftQualification;

class ServiceProviderShiftQualificationRepository extends BaseRepository
{
    public function removeByServiceProviderIdAndShiftQualificationId(
        int $serviceProviderId,
        int $shiftQualificationId
    ): void {
        ServiceProviderShiftQualification::query()
            ->byServiceProviderIdAndShiftQualificationId($serviceProviderId, $shiftQualificationId)
            ->delete();
    }
}

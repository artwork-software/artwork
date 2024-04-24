<?php

namespace Artwork\Modules\ShiftQualification\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ShiftQualification\Models\ServiceProviderShiftQualification;

readonly class ServiceProviderShiftQualificationRepository extends BaseRepository
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

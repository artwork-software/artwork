<?php

namespace Artwork\Modules\ShiftQualification\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ShiftQualification\Models\FreelancerShiftQualification;

readonly class FreelancerShiftQualificationRepository extends BaseRepository
{
    public function removeByFreelancerIdAndShiftQualificationId(int $freelancerId, int $shiftQualificationId): void
    {
        FreelancerShiftQualification::query()
            ->byFreelancerIdAndShiftQualificationId($freelancerId, $shiftQualificationId)
            ->delete();
    }
}

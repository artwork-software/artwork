<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\FreelancerShiftQualification;

class FreelancerShiftQualificationRepository extends BaseRepository
{
    public function removeByFreelancerIdAndShiftQualificationId(int $freelancerId, int $shiftQualificationId): void
    {
        FreelancerShiftQualification::query()
            ->byFreelancerIdAndShiftQualificationId($freelancerId, $shiftQualificationId)
            ->delete();
    }
}

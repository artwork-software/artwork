<?php

namespace Artwork\Modules\ShiftQualification\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ShiftQualification\Models\UserShiftQualification;

readonly class UserShiftQualificationRepository extends BaseRepository
{
    public function removeByUserIdAndShiftQualificationId(int $userId, int $shiftQualificationId): void
    {
        UserShiftQualification::query()
            ->byUserIdAndShiftQualificationId($userId, $shiftQualificationId)
            ->delete();
    }
}

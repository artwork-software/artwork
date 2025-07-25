<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\UserShiftQualification;

class UserShiftQualificationRepository extends BaseRepository
{
    public function removeByUserIdAndShiftQualificationId(int $userId, int $shiftQualificationId): void
    {
        UserShiftQualification::query()
            ->byUserIdAndShiftQualificationId($userId, $shiftQualificationId)
            ->delete();
    }
}

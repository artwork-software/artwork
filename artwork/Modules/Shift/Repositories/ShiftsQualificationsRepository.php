<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftsQualifications;

readonly class ShiftsQualificationsRepository extends BaseRepository
{
    public function findByShiftIdAndShiftQualificationId(
        int $shiftId,
        int $shiftQualificationId
    ): ShiftsQualifications|null {
        return ShiftsQualifications::query()
            ->byShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)
            ->first();
    }
}

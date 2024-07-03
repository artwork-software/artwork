<?php

namespace Artwork\Modules\PresetShift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\PresetShift\Models\PresetShiftShiftsQualifications;

class PresetShiftsShiftsQualificationsRepository extends BaseRepository
{
    public function findByShiftIdAndShiftQualificationId(
        int $presetShiftId,
        int $shiftQualificationId
    ): PresetShiftShiftsQualifications|null {
        return PresetShiftShiftsQualifications::query()
            ->byShiftIdAndShiftQualificationId($presetShiftId, $shiftQualificationId)
            ->first();
    }
}

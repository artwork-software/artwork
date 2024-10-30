<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Illuminate\Database\Eloquent\Collection;

class ShiftsQualificationsRepository extends BaseRepository
{
    public function findByShiftIdAndShiftQualificationId(
        int $shiftId,
        int $shiftQualificationId
    ): ShiftsQualifications|null {
        return ShiftsQualifications::query()
            ->byShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)
            ->first();
    }

    public function findAllByShiftQualificationId(int $shiftQualificationId): Collection
    {
        return ShiftsQualifications::query()->where('shift_qualification_id', $shiftQualificationId)->get();
    }
}

<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftUser;

class ShiftUserRepository extends BaseRepository
{
    public function createForShift(
        int $shiftId,
        int $userId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        Shift $shift
    ): ShiftUser {
        $shiftUser = new ShiftUser([
            'shift_id' => $shiftId,
            'user_id' => $userId,
            'shift_qualification_id' => $shiftQualificationId,
            'craft_abbreviation' => $craftAbbreviation,
            'short_description' => null,
            'start_date' => $shift->start_date,
            'end_date' => $shift->end_date,
            'start_time' => $shift->start,
            'end_time' => $shift->end,
        ]);

        $this->save($shiftUser);
        return $shiftUser;
    }

    public function getShiftByUserPivotId(int $pivotId): ?ShiftUser
    {
        return ShiftUser::find($pivotId);
    }

    public function getById(int $modelId): ?ShiftUser
    {
        return ShiftUser::find($modelId);
    }

    public function getCountForShiftIdAndShiftQualificationId(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftUser::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)->count();
    }

    public function findByUserIdAndShiftId(int $userId, int $shiftId): ?ShiftUser
    {
        return ShiftUser::byUserIdAndShiftId($userId, $shiftId)->first();
    }
}

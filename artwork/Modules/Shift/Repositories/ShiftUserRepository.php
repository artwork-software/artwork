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
        // Sicherstellen, dass alle Felder gesetzt sind
        $startDate = $shift->start_date ?? now();
        $endDate = $shift->end_date ?? $startDate;
        $startTime = $shift->start ?? '00:00';
        $endTime = $shift->end ?? '23:59';

        $shiftUser = new ShiftUser([
            'shift_id' => $shiftId,
            'user_id' => $userId,
            'shift_qualification_id' => $shiftQualificationId,
            'craft_abbreviation' => $craftAbbreviation,
            'short_description' => null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
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

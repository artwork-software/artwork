<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftUser;

readonly class ShiftUserRepository extends BaseRepository
{
    public function createForShift(
        int $shiftId,
        int $userId,
        int $shiftQualificationId
    ): ShiftUser {
        $shiftUser = new ShiftUser([
            'shift_id' => $shiftId,
            'user_id' => $userId,
            'shift_qualification_id' => $shiftQualificationId
        ]);

        $this->save($shiftUser);
        return $shiftUser;
    }

    public function getById(int $modelId): ShiftUser
    {
        return ShiftUser::find($modelId);
    }

    public function getCountForShiftIdAndShiftQualificationId(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftUser::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)->count();
    }

    public function findByUserIdAndShiftId(int $userId, int $shiftId): ShiftUser
    {
        return ShiftUser::byUserIdAndShiftId($userId, $shiftId)->first();
    }
}

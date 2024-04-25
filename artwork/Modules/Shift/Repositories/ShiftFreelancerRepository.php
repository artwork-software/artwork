<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftFreelancer;

readonly class ShiftFreelancerRepository extends BaseRepository
{
    public function createForShift(int $shiftId, int $freelancerId, int $shiftQualificationId): ShiftFreelancer
    {
        $shiftFreelancer = new ShiftFreelancer([
            'shift_id' => $shiftId,
            'freelancer_id' => $freelancerId,
            'shift_qualification_id' => $shiftQualificationId
        ]);

        $this->save($shiftFreelancer);
        return $shiftFreelancer;
    }

    public function getById(int $modelId): ShiftFreelancer
    {
        return ShiftFreelancer::find($modelId);
    }

    public function getCountForShiftIdAndShiftQualificationId(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftFreelancer::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)->count();
    }

    public function findByUserIdAndShiftId(int $userId, int $shiftId): ShiftFreelancer
    {
        return ShiftFreelancer::byUserIdAndShiftId($userId, $shiftId)->first();
    }
}

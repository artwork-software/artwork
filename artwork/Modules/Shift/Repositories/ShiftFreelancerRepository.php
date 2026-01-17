<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;

class ShiftFreelancerRepository extends BaseRepository
{
    public function createForShift(
        int $shiftId,
        int $freelancerId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        Shift $shift
    ): ShiftFreelancer {
        $shiftFreelancer = new ShiftFreelancer([
            'shift_id' => $shiftId,
            'freelancer_id' => $freelancerId,
            'shift_qualification_id' => $shiftQualificationId,
            'craft_abbreviation' => $craftAbbreviation,
            'short_description' => null,
            'start_date' => $shift->start_date,
            'end_date' => $shift->end_date,
            'start_time' => $shift->start,
            'end_time' => $shift->end,
        ]);

        $this->save($shiftFreelancer);
        return $shiftFreelancer;
    }

    public function getById(int $modelId): ?ShiftFreelancer
    {
        return ShiftFreelancer::find($modelId);
    }

    public function getCountForShiftIdAndShiftQualificationId(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftFreelancer::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)->count();
    }

    public function findByFreelancerIdAndShiftId(int $userId, int $shiftId): ?ShiftFreelancer
    {
        return ShiftFreelancer::byFreelancerIdAndShiftId($userId, $shiftId)->first();
    }
}

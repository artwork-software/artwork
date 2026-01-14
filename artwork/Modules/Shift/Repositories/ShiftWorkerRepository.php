<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Contracts\Employable;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;

class ShiftWorkerRepository extends BaseRepository
{
    public function createForShift(
        int $shiftId,
        string $employableType,
        int $employableId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        Shift $shift
    ): ShiftWorker {
        $startDate = $shift->start_date ?? now();
        $endDate = $shift->end_date ?? $startDate;
        $startTime = $shift->start ?? '00:00';
        $endTime = $shift->end ?? '23:59';

        $shiftWorker = new ShiftWorker([
            'shift_id' => $shiftId,
            'employable_type' => $employableType,
            'employable_id' => $employableId,
            'shift_qualification_id' => $shiftQualificationId,
            'craft_abbreviation' => $craftAbbreviation,
            'short_description' => null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $shiftWorker->save();

        return $shiftWorker->load(['shift', 'shiftQualification']);
    }

    public function getById(int $modelId): ?ShiftWorker
    {
        return ShiftWorker::find($modelId);
    }

    public function getCountForShiftIdAndShiftQualificationId(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftWorker::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)->count();
    }

    public function findByEmployableIdAndShiftId(
        string $employableType,
        int $employableId,
        int $shiftId
    ): ?ShiftWorker {
        return ShiftWorker::byEmployableIdAndShiftId($employableType, $employableId, $shiftId)->first();
    }
}

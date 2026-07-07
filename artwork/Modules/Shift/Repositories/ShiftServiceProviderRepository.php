<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;

class ShiftServiceProviderRepository extends BaseRepository
{
    public function createForShift(
        int $shiftId,
        int $serviceProviderId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        Shift $shift
    ): ShiftServiceProvider {
        $shiftServiceProvider = new ShiftServiceProvider([
            'shift_id' => $shiftId,
            'service_provider_id' => $serviceProviderId,
            'shift_qualification_id' => $shiftQualificationId,
            'craft_abbreviation' => $craftAbbreviation,
            'short_description' => null,
            'start_date' => $shift->start_date,
            'end_date' => $shift->end_date,
            'start_time' => $shift->start,
            'end_time' => $shift->end,
        ]);

        $this->save($shiftServiceProvider);
        return $shiftServiceProvider;
    }

    public function getById(int $modelId): ?ShiftServiceProvider
    {
        return ShiftServiceProvider::find($modelId);
    }

    public function getCountForShiftIdAndShiftQualificationId(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftServiceProvider::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)->count();
    }

    public function findByServiceProviderIdAndShiftId(int $userId, int $shiftId): ?ShiftServiceProvider
    {
        return ShiftServiceProvider::byServiceProviderIdAndShiftId($userId, $shiftId)->first();
    }
}

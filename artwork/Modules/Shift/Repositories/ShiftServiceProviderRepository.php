<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;

class ShiftServiceProviderRepository extends BaseRepository
{
    public function createForShift(
        int $shiftId,
        int $serviceProviderId,
        int $shiftQualificationId,
        string $craftAbbreviation
    ): ShiftServiceProvider {
        $shiftServiceProvider = new ShiftServiceProvider([
            'shift_id' => $shiftId,
            'service_provider_id' => $serviceProviderId,
            'shift_qualification_id' => $shiftQualificationId,
            'craft_abbreviation' => $craftAbbreviation
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

    public function findByServiceProviderIdAndShiftId(int $userId, int $shiftId): ShiftServiceProvider
    {
        return ShiftServiceProvider::byServiceProviderIdAndShiftId($userId, $shiftId)->first();
    }
}

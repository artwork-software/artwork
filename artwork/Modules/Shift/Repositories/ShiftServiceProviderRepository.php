<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;

readonly class ShiftServiceProviderRepository extends BaseRepository
{
    public function createForShift(
        int $shiftId,
        int $serviceProviderId,
        int $shiftQualificationId
    ): ShiftServiceProvider {
        $shiftServiceProvider = new ShiftServiceProvider([
            'shift_id' => $shiftId,
            'service_provider_id' => $serviceProviderId,
            'shift_qualification_id' => $shiftQualificationId
        ]);

        $this->save($shiftServiceProvider);
        return $shiftServiceProvider;
    }

    public function getById(int $modelId): ShiftServiceProvider
    {
        return ShiftServiceProvider::find($modelId);
    }

    public function getCountForShiftIdAndShiftQualificationId(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftServiceProvider::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)->count();
    }

    public function findByUserIdAndShiftId(int $userId, int $shiftId): ShiftServiceProvider
    {
        return ShiftServiceProvider::byUserIdAndShiftId($userId, $shiftId)->first();
    }
}

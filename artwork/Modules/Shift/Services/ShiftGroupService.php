<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Repositories\ShiftGroupRepository;

class ShiftGroupService
{


    public function __construct(
        protected ShiftGroupRepository $shiftGroupRepository
    ) {
    }

    public function getAllShiftGroups(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->shiftGroupRepository->all();
    }

    public function getShiftGroupById(int $id): ?ShiftGroup
    {
        return $this->shiftGroupRepository->findById($id);
    }

    public function createShiftGroup(array $data): ShiftGroup
    {
        return $this->shiftGroupRepository->create($data);
    }

    public function updateShiftGroup(ShiftGroup $shiftGroup, array $data): void
    {
        if ($shiftGroup) {
            $this->shiftGroupRepository->update($shiftGroup, $data);
        }
    }

    public function deleteShiftGroup(ShiftGroup $shiftGroup): void
    {
        if ($shiftGroup) {
            $this->shiftGroupRepository->delete($shiftGroup);
        }
    }
}

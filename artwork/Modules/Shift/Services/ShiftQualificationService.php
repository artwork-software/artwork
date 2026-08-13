<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Http\Requests\StoreShiftQualificationRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateShiftQualificationRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Repositories\ShiftQualificationRepository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

readonly class ShiftQualificationService
{
    public function __construct(private ShiftQualificationRepository $shiftQualificationRepository)
    {
    }

    public function getAllOrderedByPosition(): Collection
    {
        return $this->shiftQualificationRepository->getAllOrderedByPosition();
    }

    /**
     * @param array<int, int> $orderedIds
     */
    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            ShiftQualification::query()->where('id', $id)->update(['position' => $index + 1]);
        }
    }

    /**
     * @throws Throwable
     */
    public function createFromRequest(StoreShiftQualificationRequest $storeShiftQualificationRequest): void
    {
        $this->shiftQualificationRepository->saveOrFail(
            new ShiftQualification(
                $storeShiftQualificationRequest->only(['icon', 'name', 'available']) +
                ['position' => ((int) ShiftQualification::query()->max('position')) + 1]
            )
        );
    }

    /**
     * @throws Throwable
     */
    public function updateFromRequest(
        UpdateShiftQualificationRequest $updateShiftQualificationRequest,
        ShiftQualification $shiftQualification
    ): void {
        $this->shiftQualificationRepository->saveOrFail(
            $shiftQualification->fill($updateShiftQualificationRequest->only('icon', 'name', 'available'))
        );
    }

    public function isStillAvailable(int $shiftQualificationId): bool
    {
        if ($shiftQualification = $this->shiftQualificationRepository->findById($shiftQualificationId)) {
            return $shiftQualification->available;
        }

        return false;
    }

    public function delete(ShiftQualification $shiftQualification): bool
    {
        return $this->shiftQualificationRepository->delete($shiftQualification);
    }
}

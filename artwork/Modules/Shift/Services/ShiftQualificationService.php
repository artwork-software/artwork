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

    public function getAllOrderedByCreationDateAscending(): Collection
    {
        return $this->shiftQualificationRepository->getAllOrderedByCreationDateAscending();
    }

    /**
     * @throws Throwable
     */
    public function createFromRequest(StoreShiftQualificationRequest $storeShiftQualificationRequest): void
    {
        $this->shiftQualificationRepository->saveOrFail(
            new ShiftQualification(
                $storeShiftQualificationRequest->only(['icon', 'name', 'available'])
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

<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;

readonly class ShiftsQualificationsService
{
    public function __construct(private ShiftsQualificationsRepository $shiftsQualificationsRepository)
    {
    }

    public function createShiftsQualificationForShift(int $shiftId, array $shiftsQualification): void
    {
        $this->shiftsQualificationsRepository->save(
            new ShiftsQualifications(
                array_merge(
                    ['shift_id' => $shiftId],
                    $shiftsQualification
                )
            )
        );
    }

    public function updateShiftsQualificationForShift(int $shiftId, array $shiftsQualification): void
    {
        //try to find existing entry by shiftId and used shiftQualificationId
        $existingShiftsQualification = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftsQualification['shift_qualification_id']
        );

        if (is_null($existingShiftsQualification)) {
            //if shiftsQualification is not existing create it
            $this->createShiftsQualificationForShift($shiftId, $shiftsQualification);
        } else {
            //update existing shiftsQualification value based on parameters
            $this->shiftsQualificationsRepository->save(
                $existingShiftsQualification->fill(
                    ['value' => $shiftsQualification['value']]
                )
            );
        }
    }

    public function delete(ShiftsQualifications $shiftsQualification): bool
    {
        return $this->shiftsQualificationsRepository->delete($shiftsQualification);
    }

    public function restore(ShiftsQualifications $shiftsQualification): bool
    {
        return $this->shiftsQualificationsRepository->restore($shiftsQualification);
    }
}

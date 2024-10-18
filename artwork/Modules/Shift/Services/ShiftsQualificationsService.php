<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
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
                    ['value' => $shiftsQualification['value'] ?? 0]
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

    public function increaseValueOrCreateWithOne(int $shiftId, int $shiftQualificationId): void
    {
        /**
         * @var ShiftsQualifications $existingShiftsQualifications
         */
        $existingShiftsQualifications = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );

        if (is_null($existingShiftsQualifications)) {
            $this->createShiftsQualificationForShift(
                $shiftId,
                [
                    'shift_qualification_id' => $shiftQualificationId,
                    'value' => 1
                ]
            );

            return;
        }

        /** @var Shift $shiftWithWorker */
        $shiftWithWorker = $existingShiftsQualifications
            ->load('shift')
            ->getAttribute('shift');

        $workerCount = $shiftWithWorker->users()
                ->wherePivot('shift_qualification_id', $shiftQualificationId)
                ->count() +
            $shiftWithWorker->freelancer()
                ->wherePivot('shift_qualification_id', $shiftQualificationId)
                ->count() +
            $shiftWithWorker->serviceProvider()
                ->wherePivot('shift_qualification_id', $shiftQualificationId)
                ->count();

        if ($existingShiftsQualifications->getAttribute('value') < $workerCount) {
            $this->shiftsQualificationsRepository->update(
                $existingShiftsQualifications,
                ['value' => $workerCount]
            );
        }
    }

    public function increaseValueOrCreateWithOneByQualification(int $shiftId, int $shiftQualificationId): void
    {
        /**
         * @var ShiftsQualifications $existingShiftsQualifications
         */
        $existingShiftsQualifications = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );

        if (is_null($existingShiftsQualifications)) {
            $this->createShiftsQualificationForShift(
                $shiftId,
                [
                    'shift_qualification_id' => $shiftQualificationId,
                    'value' => 1
                ]
            );

            return;
        }

        $this->shiftsQualificationsRepository->update(
            $existingShiftsQualifications,
            ['value' => $existingShiftsQualifications->getAttribute('value') + 1]
        );
    }
}

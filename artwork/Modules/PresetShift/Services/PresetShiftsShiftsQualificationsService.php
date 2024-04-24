<?php

namespace Artwork\Modules\PresetShift\Services;

use Artwork\Modules\PresetShift\Models\PresetShiftShiftsQualifications;
use Artwork\Modules\PresetShift\Repositories\PresetShiftsShiftsQualificationsRepository;

readonly class PresetShiftsShiftsQualificationsService
{
    public function __construct(
        private PresetShiftsShiftsQualificationsRepository $presetShiftsQualificationsRepository
    ) {
    }

    public function createShiftsQualificationsForPresetShift(
        int $presetShiftId,
        array $shiftsQualification
    ): PresetShiftShiftsQualifications {
        $presetShiftShiftsQualifications = new PresetShiftShiftsQualifications(
            array_merge(
                ['preset_shift_id' => $presetShiftId],
                $shiftsQualification
            )
        );
        $this->presetShiftsQualificationsRepository->save($presetShiftShiftsQualifications);

        return $presetShiftShiftsQualifications;
    }

    public function updateShiftsQualificationForShift(int $presetShiftId, array $shiftsQualification): void
    {
        //try to find existing entry by shiftId and used shiftQualificationId
        $existingShiftsQualification = $this
            ->presetShiftsQualificationsRepository
            ->findByShiftIdAndShiftQualificationId(
                $presetShiftId,
                $shiftsQualification['shift_qualification_id']
            );

        if (is_null($existingShiftsQualification)) {
            //if shiftsQualification is not existing create it
            $this->createShiftsQualificationsForPresetShift($presetShiftId, $shiftsQualification);
        } else {
            //update existing shiftsQualification value based on parameters
            $this->presetShiftsQualificationsRepository->save(
                $existingShiftsQualification->fill(
                    ['value' => $shiftsQualification['value']]
                )
            );
        }
    }
}

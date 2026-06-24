<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Illuminate\Database\Eloquent\Collection;

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
            // Der Bedarf darf nicht unter die Zahl der bereits regulär zugewiesenen
            // Personen sinken — sonst entsteht eine Überbesetzung ohne
            // Überbuchungs-Markierung (Anzeige "3/1" ohne is_overbooked).
            $requestedValue = max(0, (int) ($shiftsQualification['value'] ?? 0));
            $regularWorkerCount = $this->getRegularWorkerCount(
                $shiftId,
                $shiftsQualification['shift_qualification_id']
            );

            $this->shiftsQualificationsRepository->save(
                $existingShiftsQualification->fill(
                    ['value' => max($requestedValue, $regularWorkerCount)]
                )
            );
        }
    }

    public function delete(ShiftsQualifications $shiftsQualification): bool
    {
        return $this->shiftsQualificationsRepository->delete($shiftsQualification);
    }

    public function forceDelete(ShiftsQualifications $shiftsQualification): bool
    {
        return $this->shiftsQualificationsRepository->forceDelete($shiftsQualification);
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

        // Nur REGULÄRE Worker zählen: Überbuchte belegen den separaten
        // overbooked_value-Slot — würden sie mitgezählt, würde der geplante
        // Bedarf (value) bei jeder weiteren regulären Zuweisung aufgebläht.
        $workerCount = $this->getRegularWorkerCount($shiftId, $shiftQualificationId);

        if ($existingShiftsQualifications->getAttribute('value') < $workerCount) {
            $this->shiftsQualificationsRepository->update(
                $existingShiftsQualifications,
                ['value' => $workerCount]
            );
        }
    }

    /**
     * Schafft einen zusätzlichen Überbuchungsplatz für die Funktion einer Schicht.
     * Der geplante Bedarf (value) bleibt dabei unangetastet.
     */
    public function increaseOverbookedValue(int $shiftId, int $shiftQualificationId): void
    {
        $existingShiftsQualifications = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );

        if (is_null($existingShiftsQualifications)) {
            $this->createShiftsQualificationForShift(
                $shiftId,
                [
                    'shift_qualification_id' => $shiftQualificationId,
                    'value' => 0,
                    'overbooked_value' => 1
                ]
            );

            return;
        }

        $this->shiftsQualificationsRepository->update(
            $existingShiftsQualifications,
            ['overbooked_value' => $existingShiftsQualifications->getAttribute('overbooked_value') + 1]
        );
    }

    /**
     * Entfernt einen offenen Überbuchungsplatz. Befüllte Überbuchungsplätze bleiben bestehen,
     * dafür muss zuerst der zugewiesene Worker entfernt werden.
     */
    public function decreaseOverbookedValue(int $shiftId, int $shiftQualificationId): void
    {
        $existingShiftsQualifications = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );

        if (
            is_null($existingShiftsQualifications) ||
            $existingShiftsQualifications->getAttribute('overbooked_value') <= 0 ||
            $existingShiftsQualifications->getAttribute('overbooked_value') <=
                $this->getOverbookedWorkerCount($shiftId, $shiftQualificationId)
        ) {
            return;
        }

        $this->shiftsQualificationsRepository->update(
            $existingShiftsQualifications,
            ['overbooked_value' => $existingShiftsQualifications->getAttribute('overbooked_value') - 1]
        );
    }

    /**
     * Stellt sicher, dass für eine Überbuchungs-Zuweisung ein offener Überbuchungsplatz existiert
     * (Multiedit/Drag&Drop erzeugen den Platz implizit mit).
     */
    public function ensureOpenOverbookedSlot(int $shiftId, int $shiftQualificationId): void
    {
        $existingShiftsQualifications = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );

        if (is_null($existingShiftsQualifications)) {
            $this->createShiftsQualificationForShift(
                $shiftId,
                [
                    'shift_qualification_id' => $shiftQualificationId,
                    'value' => 0,
                    'overbooked_value' => 1
                ]
            );

            return;
        }

        $overbookedWorkerCount = $this->getOverbookedWorkerCount($shiftId, $shiftQualificationId);

        if ($existingShiftsQualifications->getAttribute('overbooked_value') <= $overbookedWorkerCount) {
            $this->shiftsQualificationsRepository->update(
                $existingShiftsQualifications,
                ['overbooked_value' => $overbookedWorkerCount + 1]
            );
        }
    }

    private function getOverbookedWorkerCount(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftWorker::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)
            ->where('is_overbooked', true)
            ->count();
    }

    public function getRegularWorkerCount(int $shiftId, int $shiftQualificationId): int
    {
        return ShiftWorker::allByShiftIdAndShiftQualificationId($shiftId, $shiftQualificationId)
            ->where('is_overbooked', false)
            ->count();
    }

    /**
     * Existiert ein offener (noch unbesetzter) Überbuchungsplatz für die Funktion?
     */
    public function hasOpenOverbookedSlot(int $shiftId, int $shiftQualificationId): bool
    {
        $existingShiftsQualifications = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );

        if (is_null($existingShiftsQualifications)) {
            return false;
        }

        return (int) $existingShiftsQualifications->getAttribute('overbooked_value')
            > $this->getOverbookedWorkerCount($shiftId, $shiftQualificationId);
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

    public function findAllByShiftQualificationId(int $shiftQualificationId): Collection
    {
        return $this->shiftsQualificationsRepository->findAllByShiftQualificationId($shiftQualificationId);
    }
}

<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Database\Traits\ReceivesNewHistoryServiceTrait;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Illuminate\Database\Eloquent\Collection;

class ShiftService
{
    use ReceivesNewHistoryServiceTrait;

    public function __construct(private readonly ShiftRepository $shiftRepository)
    {
    }

    public function getById(int $shiftId): Shift|null
    {
        return $this->shiftRepository->getById($shiftId);
    }

    public function createFromShiftPresetShiftForEvent(PresetShift $presetShift, int $eventId): Shift
    {
        $shift = new Shift([
            'event_id' => $eventId,
            'start' => $presetShift->start,
            'end' => $presetShift->end,
            'break_minutes' => $presetShift->break_minutes,
            'craft_id' => $presetShift->craft_id,
            'description' => $presetShift->description,
            'is_committed' => false
        ]);

        $this->shiftRepository->save($shift);
        return $shift;
    }

    public function createRemovedAllUsersFromShiftHistoryEntry(Shift $shift): void
    {
        $this->getNewHistoryService(Shift::class)->createHistory(
            $shift->id,
            'Alle eingeplanten Mitarbeiter wurde von Schicht (' . $shift->craft->abbreviation .
            ' - ' . $shift->event->eventName . ') entfernt',
            'shift'
        );
    }

    public function delete(Shift $shift): void
    {
        $this->shiftRepository->delete($shift);
    }

    public function deleteShifts(Collection|array $shifts): void
    {
        foreach ($shifts as $shift) {
            $this->delete($shift);
        }
    }
}

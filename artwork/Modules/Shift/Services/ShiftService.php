<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Database\Traits\ReceivesNewHistoryServiceTrait;
use Artwork\Modules\Shift\Models\Shift;

class ShiftService
{
    use ReceivesNewHistoryServiceTrait;

    public function createRemovedAllUsersFromShiftHistoryEntry(Shift $shift): void
    {
        $this->getNewHistoryService(Shift::class)->createHistory(
            $shift->id,
            'Alle eingeplanten Mitarbeiter wurde von Schicht (' . $shift->craft->abbreviation .
            ' - ' . $shift->event->eventName . ') entfernt',
            'shift'
        );
    }
}

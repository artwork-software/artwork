<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Planer-Block der Tagesuebersicht (S1), nur mit „Schichten planen" oder als Admin.
 */
class AiDayPlannerData extends Data
{
    public function __construct(
        public AiDayOpenShiftsData $open_shifts,
        public AiDayAbsencesData $absences,
        public AiDayRoomConflictsData $room_conflicts,
    ) {
    }
}

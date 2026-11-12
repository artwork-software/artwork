<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von GET /api/v1/ai/me/schedule (core-api-schedule-shifts; Konzept B1 §3, §6).
 *
 * Enthaelt ausschliesslich Daten der Person, die den Token traegt.
 */
class AiScheduleData extends Data
{
    public function __construct(
        public AiPeriodData $period,
        public AiShiftListData $shifts,
        public AiAvailabilityListData $availabilities,
        public AiAbsenceListData $absences,
    ) {
    }
}

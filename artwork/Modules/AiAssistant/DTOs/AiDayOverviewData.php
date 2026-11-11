<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/day-overview?date=` (ai-scenarios-3 S1).
 *
 * `events`: Termine des Tages in Projekten, in deren Team die Person ist, plus Termine mit eigener
 * Schicht. `my_shifts`: eigene Schichten wie in `/ai/me/schedule`. `my_tasks_due`: offene eigene
 * Aufgaben mit Deadline bis Tagesende (inkl. ueberfaelliger), hoechstens 10. `planner`: nur fuer
 * Schichtplaner:innen und Admins, sonst null.
 */
class AiDayOverviewData extends Data
{
    /** @param AiTaskData[] $my_tasks_due */
    public function __construct(
        public string $date,
        public AiDayEventListData $events,
        public AiScheduleData $my_shifts,
        public array $my_tasks_due,
        public ?AiDayPlannerData $planner,
    ) {
    }
}

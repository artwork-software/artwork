<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von GET /api/v1/ai/projects/{id} (Konzept B1 §3).
 *
 * Ohne Budgetdaten — die bleiben in v1 bewusst draussen (B1 §4); Budgetzahlen liefert
 * `/ai/projects/{project}/budget-summary` (ai-scenarios-3 S2).
 */
class AiProjectDetailData extends Data
{
    /**
     * @param AiEventBriefData[] $next_events
     * @param AiRefData[] $managers
     */
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $state,
        /** Kurzbeschreibung fuer den Dienstplan — das einzige Freitextfeld am Projekt. */
        public ?string $shift_description,
        public AiPeriodData $period,
        public int $team_size,
        public int $event_count,
        public int $room_count,
        public array $next_events,
        /** Projektleitung (ai-scenarios-3 S8). */
        public array $managers = [],
        /** Budget-Stichtag `YYYY-MM-DD` oder null. */
        public ?string $budget_deadline = null,
        public bool $marked_as_done = false,
        public bool $is_group = false,
        /** Fuer die fragende Person sichtbare offene Aufgaben im Projekt. */
        public int $open_tasks = 0,
    ) {
    }
}

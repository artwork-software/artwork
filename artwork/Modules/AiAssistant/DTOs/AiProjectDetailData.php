<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von GET /api/v1/ai/projects/{id} (Konzept B1 §3).
 *
 * Ohne Budgetdaten — die bleiben in v1 bewusst draussen (B1 §4).
 */
class AiProjectDetailData extends Data
{
    /** @param AiEventBriefData[] $next_events */
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
    ) {
    }
}

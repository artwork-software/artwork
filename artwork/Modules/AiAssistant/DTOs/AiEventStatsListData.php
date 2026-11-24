<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von GET /api/v1/ai/events/stats (core-ai-event-stats-exports).
 *
 * `total_events`: alle passenden Termine im Zeitraum, vor den Grenzen `min_count`/`max_count`.
 * `total_groups`: Gruppen nach diesen Grenzen, vor `limit`.
 */
class AiEventStatsListData extends Data
{
    /** @param AiEventStatsGroupData[] $data */
    public function __construct(
        public array $data,
        public string $group_by,
        public AiPeriodData $period,
        public int $total_events,
        public int $total_groups,
        public int $limit,
        public bool $truncated,
    ) {
    }
}

<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Treffer aus GET /api/v1/ai/projects (Konzept B1 §3).
 *
 * ai-scenarios-4 S18 (additiv): `state_id` (ID zu `state`, das der Name bleibt), `budget_deadline`
 * (`Y-m-d`|null) und `marked_as_done`.
 */
class AiProjectSummaryData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $state,
        public AiPeriodData $period,
        public int $room_count,
        public int $event_count,
        public ?int $state_id = null,
        public ?string $budget_deadline = null,
        public bool $marked_as_done = false,
    ) {
    }
}

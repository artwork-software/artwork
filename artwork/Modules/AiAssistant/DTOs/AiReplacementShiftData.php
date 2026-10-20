<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Schicht der ausfallenden Person mit Ersatzvorschlag (ai-scenarios-4 S9).
 *
 * `replacement` ist die erste Person, die die Schicht ohne Regelverstoss uebernehmen kann (ueber
 * alle Picks derselben Person hinweg in einer Simulation geprueft), `alternatives` bis zu drei
 * weitere. Ohne Ersatz steht in `reason_code` `no_eligible_people` oder
 * `all_candidates_violate_rules`.
 */
class AiReplacementShiftData extends Data
{
    /** @param AiPersonData[] $alternatives */
    public function __construct(
        public int $shift_id,
        public int $shift_qualification_id,
        public AiRefData $qualification,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $event,
        public AiRefData $craft,
        public ?AiPersonData $replacement,
        public array $alternatives,
        public ?string $reason_code,
    ) {
    }
}

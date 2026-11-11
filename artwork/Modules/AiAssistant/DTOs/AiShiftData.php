<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine eigene Schicht aus GET /api/v1/ai/me/schedule (core-api-schedule-shifts; Konzept B1 §6).
 *
 * `start`/`end` sind die **effektiven** Zeiten der Zuweisung: individuelle Zeiten am
 * `shift_workers`-Pivot schlagen die Schichtzeiten, wie in der Oberflaeche
 * (ShiftAssignmentPreflightService::resolvePivotInterval()).
 */
class AiShiftData extends Data
{
    public function __construct(
        public int $id,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $craft,
        public ?string $qualification,
        public ?AiRefData $event,
        public ?AiRefData $project,
        public bool $committed,
    ) {
    }
}

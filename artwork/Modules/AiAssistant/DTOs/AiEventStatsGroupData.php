<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Gruppe aus GET /api/v1/ai/events/stats: Projekt, Raum oder Terminart mit der Zahl der
 * Termine im Zeitraum. `id` ist null fuer Termine ohne Projekt bzw. ohne Raum.
 */
class AiEventStatsGroupData extends Data
{
    public function __construct(
        public ?int $id,
        public ?string $name,
        public int $count,
    ) {
    }
}

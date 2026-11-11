<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Availability\Models\Availability;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * Ein Verfuegbarkeitseintrag der eigenen Person (core-api-schedule-shifts).
 *
 * Verfuegbarkeiten sind tagesbezogen: entweder ganztaegig oder mit Uhrzeiten. `date` ist im
 * Modell nicht gecastet und kommt als Zeichenkette aus der Datenbank.
 */
class AiAvailabilityData extends Data
{
    public function __construct(
        public int $id,
        public string $date,
        public bool $full_day,
        public ?string $start_time,
        public ?string $end_time,
        public ?string $comment,
    ) {
    }

    public static function fromModel(Availability $availability): self
    {
        $fullDay = (bool) $availability->full_day;

        return new self(
            id: $availability->id,
            date: CarbonImmutable::parse($availability->date)->toDateString(),
            full_day: $fullDay,
            start_time: $fullDay ? null : $availability->start_time?->format('H:i'),
            end_time: $fullDay ? null : $availability->end_time?->format('H:i'),
            comment: AiText::truncate($availability->comment),
        );
    }
}

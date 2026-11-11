<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * Ein Abwesenheitseintrag der eigenen Person (core-api-schedule-shifts).
 *
 * `type` ist der Grund aus der Oberflaeche (`Artwork\Modules\Vacation\Enums\Vacation`, als
 * Zeichenkette gespeichert: OFF_WORK, NOT_AVAILABLE, FREE_WORK, AVAILABLE).
 */
class AiAbsenceData extends Data
{
    public function __construct(
        public int $id,
        public string $date,
        public bool $full_day,
        public ?string $start_time,
        public ?string $end_time,
        public ?string $type,
        public ?string $comment,
    ) {
    }

    public static function fromModel(Vacation $vacation): self
    {
        $fullDay = (bool) $vacation->full_day;

        return new self(
            id: $vacation->id,
            date: CarbonImmutable::parse($vacation->date)->toDateString(),
            full_day: $fullDay,
            start_time: $fullDay ? null : $vacation->start_time?->format('H:i'),
            end_time: $fullDay ? null : $vacation->end_time?->format('H:i'),
            type: AiText::truncate(is_string($vacation->type) ? $vacation->type : null),
            comment: AiText::truncate($vacation->comment),
        );
    }
}

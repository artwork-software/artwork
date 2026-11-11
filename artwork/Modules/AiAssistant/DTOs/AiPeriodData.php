<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

/**
 * Zeitraum in ISO 8601, immer in der Haus-Zeitzone (Konzept B1 §4).
 */
class AiPeriodData extends Data
{
    public function __construct(
        public ?string $from,
        public ?string $to,
    ) {
    }

    public static function fromDates(?CarbonInterface $from, ?CarbonInterface $to): self
    {
        $timezone = (string) config('app.timezone');

        return new self(
            from: $from?->copy()->setTimezone($timezone)->toIso8601String(),
            to: $to?->copy()->setTimezone($timezone)->toIso8601String(),
        );
    }
}

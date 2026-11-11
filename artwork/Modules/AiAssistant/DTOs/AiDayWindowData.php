<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/** Das Tagesfenster, auf das sich Belegung und freie Slots beziehen (Konzept B1 §5). */
class AiDayWindowData extends Data
{
    public function __construct(
        public string $from,
        public string $to,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            from: (string) config('ai.day_window.from'),
            to: (string) config('ai.day_window.to'),
        );
    }
}

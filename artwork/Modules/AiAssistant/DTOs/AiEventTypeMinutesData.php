<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiEventTypeMinutesData extends Data
{
    public function __construct(
        public ?string $name,
        public int $minutes,
    ) {
    }
}

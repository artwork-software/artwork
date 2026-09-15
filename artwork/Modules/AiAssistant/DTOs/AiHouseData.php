<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiHouseData extends Data
{
    public function __construct(
        public string $name,
        public string $timezone,
    ) {
    }
}

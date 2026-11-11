<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiEventShiftQualificationData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public int $required,
        public int $staffed,
        public int $open,
    ) {
    }
}

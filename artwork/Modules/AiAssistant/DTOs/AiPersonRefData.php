<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Kurzform einer Person: `type` (`user`, `freelancer`, `service_provider`), `id`, `name`.
 */
class AiPersonRefData extends Data
{
    public function __construct(
        public string $type,
        public int $id,
        public string $name,
    ) {
    }
}

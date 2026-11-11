<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Termine eines Tages (S1), Listenrahmen wie alle Listen.
 */
class AiDayEventListData extends Data
{
    /** @param AiDayEventData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}

<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiPersonShiftListData extends Data
{
    /** @param AiPersonShiftData[] $shifts */
    public function __construct(
        public AiPersonRefData $person,
        public AiPeriodData $period,
        public array $shifts,
        public int $total,
        public int $total_minutes,
        public bool $truncated,
    ) {
    }
}

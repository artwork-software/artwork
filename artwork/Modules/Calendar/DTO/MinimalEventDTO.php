<?php

namespace Artwork\Modules\Calendar\DTO;

use Spatie\LaravelData\Data;

class MinimalEventDTO extends Data
{
    public bool $isMinimal = true; // this is used by frontend, dont remove it
    public function __construct(
        public int $id,
        public string $start,
        public string $end,
        public int $roomId,
    ) {
    }

}

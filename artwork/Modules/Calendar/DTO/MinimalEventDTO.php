<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Core\Dto\TransformsWithoutDataPipeline;
use Spatie\LaravelData\Data;

class MinimalEventDTO extends Data
{
    use TransformsWithoutDataPipeline;

    public bool $isMinimal = true; // this is used by frontend, dont remove it
    public function __construct(
        public int $id,
        public string $start,
        public string $end,
        public int $roomId,
    ) {
    }
}

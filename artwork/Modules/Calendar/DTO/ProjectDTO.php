<?php

namespace Artwork\Modules\Calendar\DTO;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class ProjectDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $statusId,
        public ?string $backgroundColor,
        public ?string $borderColor,
        public ?string $statusName,
        public ?string $artistNames,
        public ?Collection $leaders
    ) {
    }
}
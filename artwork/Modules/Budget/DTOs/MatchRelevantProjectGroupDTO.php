<?php

namespace Artwork\Modules\Budget\DTOs;

use Spatie\LaravelData\Data;

class MatchRelevantProjectGroupDTO extends Data
{
    public function __construct(
        public int $subProjectId,
        public string $subProjectName,
        public int $groupRowId,
        public ?string $groupRowName,
        public ?int $subPositionId,
        public ?int $mainPositionId,
        public int $relevantColumnId,
        public ?string $relevantColumnName,
        public string $value,
        public ?int $cellId,
        public string $type,
        public ?bool $commented,
        public string $value1,
        public string $value2,

    ){}
}
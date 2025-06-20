<?php

namespace Artwork\Modules\Project\DTOs;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class ProjectSearchDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }


    public static function fromModel(Project $project): self
    {
        return new self(
            $project->id,
            $project->name
        );
    }

}
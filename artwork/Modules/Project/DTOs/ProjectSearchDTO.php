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
        public ?string $first_event_date = null,
        public ?string $last_event_date = null,
    ) {
    }


    public static function fromModel(Project $project): self
    {
        $dates = $project->first_and_last_event_date;

        return new self(
            $project->id,
            $project->name,
            $dates['first_event_date'] ?? null,
            $dates['last_event_date'] ?? null
        );
    }

}

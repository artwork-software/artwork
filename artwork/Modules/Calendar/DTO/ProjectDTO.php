<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;

class ProjectDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public Lazy|ProjectState|null $status,
        public ?string $artistNames,
        public Lazy|Collection $leaders,
        public ?string $color = null,
        public ?string $icon = null,
        public ?bool $isGroup = false,
        public ?bool $isInGroup = false,
        public ?Collection $group = null,
        public ?array $userIds = null,
    ) {
    }


    public static function fromModel(Project $project, UserCalendarSettings $userCalendarSettings = null): self
    {
        return new self(
            $project->id,
            $project->name,
            $userCalendarSettings?->project_status ? $project->status : Lazy::inertia(fn() => $project->status),
            $project->artists,
            $userCalendarSettings?->project_management ?
                $project->managerUsers :
                Lazy::inertia(fn() => $project->managerUsers),
            $project->color,
            $project->icon,
            $project->is_group,
            $project->groups->isNotEmpty(),
            $project->groups->isNotEmpty() ? $project->groups->map(fn(Project $group) => self::fromModel($group, $userCalendarSettings)) : null,
            $project->users->pluck('id')->toArray(),
        );
    }

}
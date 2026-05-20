<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class ProjectDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?array $status,
        public ?string $artistNames,
        public Lazy|array $leaders,
        public ?string $color = null,
        public ?string $icon = null,
        public ?bool $isGroup = false,
        public ?bool $isInGroup = false,
        public ?array $group = null,
        public ?array $userIds = null,
        public ?string $mainCategoryColor = null,
    ) {
    }


    public static function fromModel(Project $project, null|UserCalendarSettings|UserDailyViewCalendarSettings $userCalendarSettings = null): self
    {
        $statusModel = $project->relationLoaded('status') ? $project->status : null;

        return new self(
            $project->id,
            $project->name,
            $statusModel ? [
                'id' => $statusModel->id,
                'name' => $statusModel->name,
                'color' => $statusModel->color,
            ] : null,
            $project->artists,
            $userCalendarSettings?->project_management ?
                self::serializeLeaders($project->managerUsers) :
                Lazy::inertia(fn () => self::serializeLeaders($project->managerUsers)),
            $project->color,
            $project->icon,
            $project->is_group,
            $project->groups->isNotEmpty(),
            $project->groups->isNotEmpty() ? $project->groups->map(fn (Project $group) => self::fromModel($group, $userCalendarSettings))->all() : null,
            $project->users->pluck('id')->toArray(),
            $project->categories->firstWhere('pivot.is_main', true)?->color,
        );
    }

    public static function fromModelForCalendar(Project $project): self
    {
        $groups = $project->relationLoaded('groups') ? $project->groups : collect();
        $statusModel = $project->relationLoaded('status') ? $project->status : null;

        return new self(
            id: $project->id,
            name: $project->name,
            status: $statusModel ? [
                'id' => $statusModel->id,
                'name' => $statusModel->name,
                'color' => $statusModel->color,
            ] : null,
            artistNames: $project->artists,
            leaders: [],
            color: $project->color,
            icon: $project->icon,
            isGroup: $project->is_group,
            isInGroup: $groups->isNotEmpty(),
            group: $groups->isNotEmpty() ? $groups->map(fn (Project $group) => self::fromModelForCalendar($group))->all() : null,
            userIds: $project->relationLoaded('users')
                ? $project->users->pluck('id')->toArray()
                : [],
            mainCategoryColor: $project->relationLoaded('categories')
                ? $project->categories->firstWhere('pivot.is_main', true)?->color
                : null,
        );
    }

    /**
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $managers
     */
    private static function serializeLeaders($managers): array
    {
        return $managers->map(fn ($user) => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'position' => $user->position ?? null,
            'email' => $user->email,
            'profile_photo_url' => $user->profile_photo_path
                ? '/storage/' . $user->profile_photo_path
                : null,
        ])->values()->all();
    }
}

<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectStates;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\SubEvent\Http\Resources\SubEventResource;
use Artwork\Modules\User\Models\User;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

/**
 * @mixin Event
 */
class MinimalCacheBasedCalendarEventResource extends JsonResource
{
    /**
     * @throws Throwable
     * @return array<string, mixed>
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        /** @var array<int, Project> $cachedProjects */
        /** @var array<int, ProjectStates> $cachedProjectStates */
        /** @var array<int, Shift> $cachedShifts */
        [
            $cachedProjects,
            $cachedProjectStates,
            $cachedShifts
        ] = $this->getCacheData();

        /** @var string|null $projectName */
        /** @var ProjectStates|null $projectState */
        /** @var array<string, string> $projectLeaders */
        [
            $projectName,
            $projectState,
            $projectLeaders
        ] = $this->aggregateProjectRelevantData(
            $projectId = $this->getAttribute('project_id'),
            $cachedProjects,
            $cachedProjectStates
        );

        return [
            'id' => $this->getAttribute('id'),
            'projectId' => $projectId,
            'roomId' => $this->getAttribute('room_id'),
            'start' => $this->getAttribute('start_time')->utc()->toIso8601String(),
            'startTime' => $this->getAttribute('start_time'),
            'end' => $this->getAttribute('end_time')->utc()->toIso8601String(),
            'allDay' => $this->getAttribute('allDay'),
            'alwaysEventName' => $this->getAttribute('eventName'),
            'eventName' => $this->getAttribute('eventName'),
            'title' => $projectName ?:
                $this->getAttribute('eventName') ?:
                    $this->getAttribute('event_type')->getAttribute('name'),
            'event_type_color' => $this->getAttribute('event_type')->getAttribute('hex_code'),
            'eventTypeColorBackground' => $this->getAttribute('event_type')->getAttribute('hex_code') . '33',
            'eventTypeName' => $this->getAttribute('event_type')->getAttribute('name'),
            'eventTypeAbbreviation' => $this->getAttribute('event_type')->getAttribute('abbreviation'),
            'audience' => $this->getAttribute('audience'),
            'isLoud' => $this->getAttribute('is_loud'),
            'projectName' => $projectName,
            'projectStateColor' => $projectState?->getAttribute('color'),
            'projectLeaders' => $projectLeaders,
            'subEvents' => SubEventResource::collection($this->getAttribute('subEvents')),
            'shifts' => $this->aggregateEventShifts($cachedShifts)
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     * @throws Throwable
     */
    private function getCacheData(): array
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = app()->get(CacheManager::class);

        return [
            $cacheManager->get('projects'),
            $cacheManager->get('projectStates'),
            $cacheManager->get('shifts')
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     * @throws Throwable
     */
    private function aggregateProjectRelevantData(
        ?int $eventProjectId,
        array $cachedProjects,
        array $cachedProjectStates
    ): array {
        $project = $this->determineProject($eventProjectId, $cachedProjects);

        return [
            $project?->getAttribute('name'),
            $this->determineProjectState($cachedProjectStates, $project),
            $this->determineProjectLeaders($project?->getAttribute('managerUsers'))
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aggregateEventShifts(array $cachedShifts): array
    {
        $desiredShiftIds = $this->getAttribute('shifts')->pluck('id')->all();

        return array_map(
            function (Shift $shift): array {
                return [
                    'id' => $shift->getAttribute('id'),
                    'craft' => [
                        'id' => $shift->getAttribute('craft')->getAttribute('id'),
                        'name' => $shift->getAttribute('craft')->getAttribute('name'),
                        'abbreviation' => $shift->getAttribute('craft')->getAttribute('abbreviation')
                    ],
                    'worker_count' => $shift->getAttribute('users')->count() +
                        $shift->getAttribute('freelancer')->count() +
                        $shift->getAttribute('serviceProvider')->count(),
                    'max_worker_count' => $shift->getAttribute('shiftsQualifications')->sum('value')
                ];
            },
            array_filter(
                $cachedShifts,
                function (Shift $shift) use ($desiredShiftIds): bool {
                    return in_array($shift->getAttribute('id'), $desiredShiftIds);
                }
            )
        );
    }

    private function determineProject(?int $eventProjectId, array $cachedProjects): Project|null
    {
        if ($eventProjectId) {
            foreach ($cachedProjects as $cachedProject) {
                if ($cachedProject->getAttribute('id') === $eventProjectId) {
                    return $cachedProject;
                }
            }
        }

        return null;
    }

    private function determineProjectState(array $cachedProjectStates, ?Project $project): ?ProjectStates
    {
        foreach ($cachedProjectStates as $cachedProjectState) {
            if ($cachedProjectState->getAttribute('id') === $project?->state) {
                return $cachedProjectState;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function determineProjectLeaders(?Collection $managerUsers): array
    {
        return $managerUsers?->count() > 0 ?
            array_map(
                function (User $user): array {
                    return [
                        'profile_photo_url' => $user->getAttribute('profile_photo_url'),
                        'first_name' => $user->getAttribute('first_name'),
                        'last_name' => $user->getAttribute('last_name')
                    ];
                },
                $managerUsers->all()
            ) :
            [];
    }
}

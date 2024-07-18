<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\SubEvent\Http\Resources\SubEventResource;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

/**
 * @mixin Event
 */
class MinimalCalendarEventResource extends JsonResource
{
    public static $wrap = null;
    /**
     * @throws Throwable
     * @return array<string, mixed>
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        /** @var string|null $projectName */
        /** @var string|null $projectStateColor */
        /** @var array<string, string>|null $projectLeaders */
        $projectName = $projectStateColor = $projectLeaders = null;
        if ($eventProjectId = $this->getAttribute('project_id')) {
            [
                $projectName,
                $projectStateColor,
                $projectLeaders
            ] = $this->aggregateProjectRelevantData();
        }

        $creator = $this->getAttribute('creator');
        return [
            'id' => $this->getAttribute('id'),
            'projectId' => $eventProjectId,
            'roomId' => $this->getAttribute('room_id'),
            'created_by' => [
                'id' => $creator->getAttribute('id'),
                'profile_photo_url' => $creator->getAttribute('profile_photo_url'),
                'first_name' => $creator->getAttribute('first_name'),
                'last_name' => $creator->getAttribute('last_name')
            ],
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
            'projectStateColor' => $projectStateColor,
            'projectLeaders' => $projectLeaders,
            'subEvents' => SubEventResource::collection($this->getAttribute('subEvents'))->resolve(),
            'shifts' => $this->aggregateEventShifts($this->getAttribute('shifts')->all())
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     * @throws Throwable
     */
    private function aggregateProjectRelevantData(): array
    {
        /** @var Project $project */
        if (!($project = $this->getAttribute('project'))) {
            return [
                null,
                null,
                null
            ];
        }

        if (($projectStateColor = $projectState = $project->getRelation('state'))) {
            $projectStateColor = $projectState->getAttribute('color');
        }

        return [
            $project->getAttribute('name'),
            $projectStateColor,
            $this->determineProjectLeaders($project->getAttribute('managerUsers')->all())
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aggregateEventShifts(array $shifts): array
    {
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
            $shifts
        );
    }

    /**
     * @return array<string, string>
     */
    private function determineProjectLeaders(array $managerUsers): array
    {
        return count($managerUsers) > 0 ?
            array_map(
                function (User $user): array {
                    return [
                        'profile_photo_url' => $user->getAttribute('profile_photo_url'),
                        'first_name' => $user->getAttribute('first_name'),
                        'last_name' => $user->getAttribute('last_name')
                    ];
                },
                $managerUsers
            ) :
            [];
    }
}

<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SeriesEvents\Models\SeriesEvents;
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
        $projectName = $projectArtists = $projectStateColor = $projectLeaders = null;
        if ($eventProjectId = $this->getAttribute('project_id')) {
            [
                $projectName,
                $projectArtists,
                $projectStateColor,
                $projectLeaders
            ] = $this->aggregateProjectRelevantData();
        }

        $creator = $this->getAttribute('creator');
        $eventType = $this->getAttribute('event_type');
        $eventName = $this->getAttribute('eventName');
        $startTime = $this->getAttribute('start_time');
        return [
            'id' => $this->getAttribute('id'),
            'description' => $this->getAttribute('description'),
            'projectId' => $eventProjectId,
            'projectArtists' => $projectArtists,
            'roomId' => $this->getAttribute('room_id'),
            'roomName' => $this->getAttribute('room')?->getAttribute('name'),
            'created_by' => [
                'id' => $creator->getAttribute('id'),
                'profile_photo_url' => $creator->getAttribute('profile_photo_url'),
                'first_name' => $creator->getAttribute('first_name'),
                'last_name' => $creator->getAttribute('last_name')
            ],
            'project' => $this->getAttribute('project'),
            'start' => $startTime->utc()->toIso8601String(),
            'startTime' => $startTime,
            'end' => $this->getAttribute('end_time')->utc()->toIso8601String(),
            'allDay' => $this->getAttribute('allDay'),
            'is_series' => $this->getAttribute('is_series'),
            'series' => $this->aggregateSeriesEvents(),
            'option_string' => $this->getAttribute('option_string'),
            'occupancy_option' => $this->getAttribute('occupancy_option'),
            'alwaysEventName' => $eventName,
            'eventName' => $eventName,
            'title' => $projectName ?: $eventName ?: $eventType->getAttribute('name'),
            'eventTypeId' => $eventType->getAttribute('id'),
            'eventStatusId' => $this->getAttribute('event_status_id'),
            'eventStatusColor' => $this->getAttribute('eventStatus')?->getAttribute('color'),
            'event_type_color' => $eventType->getAttribute('hex_code'),
            'eventTypeColorBackground' => $eventType->getAttribute('hex_code') . '33',
            'eventTypeName' => $eventType->getAttribute('name'),
            'eventTypeAbbreviation' => $eventType->getAttribute('abbreviation'),
            'audience' => $this->getAttribute('audience'),
            'isLoud' => $this->getAttribute('is_loud'),
            'projectName' => $projectName,
            'projectStateColor' => $projectStateColor,
            'projectLeaders' => $projectLeaders,
            'subEvents' => SubEventResource::collection($this->getAttribute('subEvents'))->resolve(),
            'shifts' => $this->aggregateEventShifts($this->getAttribute('shifts')->all()),
            'start_hour' => $this->getAttribute('start_hour') . ':00',
            'event_length_in_hours' => $this->getAttribute('event_length_in_hours'),
            'hours_to_next_day' => $this->getAttribute('hours_to_next_day'),
            'minutes_form_start_hour_to_start' => $this->getAttribute('minutes_form_start_hour_to_start'),
            'eventProperties' => $this->getAttribute('eventProperties'),
            'status' => $this->getAttribute('eventStatus'),
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
                null,
                null
            ];
        }

        if (($projectStateColor = $projectState = $project->status)) {
            $projectStateColor = $projectState->getAttribute('color');
        }

        return [
            $project->getAttribute('name'),
            $project->getAttribute('artists'),
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

    /**
     * @return array<string, mixed>
     */
    private function aggregateSeriesEvents(): array
    {
        if (!($series = $this->getAttribute('series')) instanceof SeriesEvents) {
            return [];
        }

        return [
            'id' => $series->getAttribute('id'),
            'end_date' => $series->getAttribute('end_date')->format('Y-m-d'),
        ];
    }
}

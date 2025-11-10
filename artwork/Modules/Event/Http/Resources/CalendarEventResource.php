<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Http\Resources\ProjectInCalendarResource;
use Artwork\Modules\Event\Http\Resources\SubEventResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/** @mixin Event */
class CalendarEventResource extends JsonResource
{
    public function toArray($request): array
    {
        $projectId = $this->getAttribute('project_id');
        [$projectName, $projectArtists, $projectStateColor, $projectLeaders] = $projectId
            ? $this->aggregateProjectRelevantData()
            : [null, null, null, null];

        $creator   = $this->getAttribute('creator');
        $eventType = $this->getAttribute('event_type');
        $eventName = $this->getAttribute('eventName');
        $startTime = $this->getAttribute('start_time');

        return [
            'id'          => $this->getAttribute('id'),
            'description' => $this->getAttribute('description'),
            'projectId'   => $projectId,
            'projectArtists' => $projectArtists,
            'roomId'      => $this->getAttribute('room_id'),
            'roomName'    => $this->getAttribute('room')?->getAttribute('name'),
            'created_by'  => [
                'id'               => $creator->getAttribute('id'),
                'profile_photo_url'=> $creator->getAttribute('profile_photo_url'),
                'first_name'       => $creator->getAttribute('first_name'),
                'last_name'        => $creator->getAttribute('last_name'),
            ],
            'project'     => $this->getAttribute('project'),
            'start'       => $startTime->utc()->toIso8601String(),
            'startTime'   => $startTime,
            'end'         => $this->getAttribute('end_time')->utc()->toIso8601String(),
            'allDay'      => (bool)$this->getAttribute('allDay'),
            'is_series'   => (bool)$this->getAttribute('is_series'),
            'series'      => $this->aggregateSeriesEvents(),
            'option_string'    => $this->getAttribute('option_string'),
            'occupancy_option' => $this->getAttribute('occupancy_option'),
            'alwaysEventName'  => $eventName,
            'eventName'        => $eventName,
            'title'            => $projectName ?: $eventName ?: $eventType->getAttribute('name'),
            'eventTypeId'      => $eventType->getAttribute('id'),
            'eventStatusId'    => $this->getAttribute('event_status_id'),
            'eventStatusColor' => $this->getAttribute('eventStatus')?->getAttribute('color'),
            'event_type_color' => $eventType->getAttribute('hex_code'),
            'eventTypeColorBackground' => $eventType->getAttribute('hex_code') . '33',
            'eventTypeName'    => $eventType->getAttribute('name'),
            'eventTypeAbbreviation' => $eventType->getAttribute('abbreviation'),
            'audience'         => $this->getAttribute('audience'),
            'isLoud'           => $this->getAttribute('is_loud'),
            'projectName'      => $projectName,
            'projectStateColor'=> $projectStateColor,
            'projectLeaders'   => $projectLeaders,
            'subEvents'        => SubEventResource::collection($this->getAttribute('subEvents'))->resolve(),
            'shifts'           => $this->aggregateEventShifts($this->getAttribute('shifts')->all()),
            'start_hour'       => $this->getAttribute('start_hour') . ':00',
            'event_length_in_hours' => $this->getAttribute('event_length_in_hours'),
            'hours_to_next_day' => $this->getAttribute('hours_to_next_day'),
            'minutes_form_start_hour_to_start' => $this->getAttribute('minutes_form_start_hour_to_start'),
            'eventProperties'  => $this->getAttribute('eventProperties'),
            'status'           => $this->getAttribute('eventStatus'),
        ];
    }

    private function aggregateProjectRelevantData(): array
    {
        /** @var Project|null $project */
        $project = $this->getAttribute('project');
        if (!$project) {
            return [null, null, null, null];
        }

        $projectStateColor = $project->status?->getAttribute('color');

        return [
            $project->getAttribute('name'),
            $project->getAttribute('artists'),
            $projectStateColor,
            $this->determineProjectLeaders($project->getAttribute('managerUsers')->all())
        ];
    }

    private function aggregateEventShifts(array $shifts): array
    {
        return array_map(function (Shift $shift): array {
            return [
                'id' => $shift->getAttribute('id'),
                'craft' => [
                    'id' => $shift->getAttribute('craft')->getAttribute('id'),
                    'name' => $shift->getAttribute('craft')->getAttribute('name'),
                    'abbreviation' => $shift->getAttribute('craft')->getAttribute('abbreviation'),
                ],
                'worker_count' => $shift->getAttribute('users')->count()
                    + $shift->getAttribute('freelancer')->count()
                    + $shift->getAttribute('serviceProvider')->count(),
                'max_worker_count' => (int)$shift->getAttribute('shiftsQualifications')->sum('value'),
            ];
        }, $shifts);
    }

    private function determineProjectLeaders(array $managerUsers): array
    {
        return count($managerUsers) > 0
            ? array_map(fn(User $u) => [
                'profile_photo_url' => $u->getAttribute('profile_photo_url'),
                'first_name'        => $u->getAttribute('first_name'),
                'last_name'         => $u->getAttribute('last_name'),
            ], $managerUsers)
            : [];
    }

    private function aggregateSeriesEvents(): array
    {
        $series = $this->getAttribute('series');
        if (!($series instanceof \Artwork\Modules\Event\Models\SeriesEvents)) {
            return [];
        }

        return [
            'id'       => $series->getAttribute('id'),
            'end_date' => $series->getAttribute('end_date')->format('Y-m-d'),
        ];
    }
}

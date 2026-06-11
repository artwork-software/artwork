<?php

namespace Artwork\Modules\Calendar\DTO;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Calendar\Traits\SerializesEventRelations;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class EventDTO extends Data
{
    use SerializesEventRelations;
    public bool $isMinimal = false; // this is used by frontend, dont remove it

    public function __construct(
        public int $id,
        public string $start,
        public string $end,
        public ?string $eventName,
        public ?string $description,
        public ?ProjectDTO $project,
        public ?array $eventType,
        public ?array $shifts,
        public bool $allDay,
        public ?int $roomId,
        public ?string $roomName,
        public ?int $startHour,
        public ?int $minutesFormStartHourToStart,
        public ?int $eventLengthInHours,
        public MinimalCreatorDTO|null|Optional $created_by,
        public ?bool $is_series,
        public ?int $series_id,
        public ?array $eventProperties,
        public ?bool $occupancy_option,
        public ?int $declinedRoomId = null,
        public ?array $eventStatus,
        public ?array $subEvents,
        public ?string $option_string,
        public ?bool $isPlanning = false,
        public ?bool $hasVerification = false,
        public ?bool $hasTimelines = false,
    ) {
    }

    public static function fromModel(
        Event $event,
        UserCalendarSettings|UserDailyViewCalendarSettings $userCalendarSettings,
        Collection $projects,
        Collection $eventTypes,
        Collection $users,
        Collection $eventStatuses,
    ): EventDTO {
        $eventType = $eventTypes[$event->event_type_id] ?? null;
        $user = $event->user_id ? ($users[$event->user_id] ?? null) : null;
        $project = $event->project_id ? ($projects[$event->project_id] ?? null) : null;
        $eventStatus = $event->event_type_id !== null
            ? ($eventStatuses[$event->event_status_id] ?? null)
            : null;

        return new self(
            id: $event->id,
            start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
            end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
            eventName: $event->eventName,
            description: $event->description,
            // Lookup kann leer sein, wenn das Projekt bereits im Papierkorb liegt
            // (z.B. abgebrochene Lösch-Kaskade) – dann ohne Projekt-Chip rendern statt 500.
            project: $project ? ProjectDTO::fromModel($project, $userCalendarSettings) : null,
            eventType: $eventType ? [
                'id' => $eventType->id,
                'name' => $eventType->name,
                'abbreviation' => $eventType->abbreviation,
                'hex_code' => $eventType->hex_code,
            ] : null,
            shifts: $userCalendarSettings->work_shifts ?
                MinimalShiftPlanShiftResource::collection($event->shifts)->resolve() :
                null,
            allDay: $event->allDay,
            roomId: $event->room_id,
            roomName: $event->room->name,
            startHour: $event->getAttribute('start_hour') ?? 0,
            minutesFormStartHourToStart: $event->getAttribute('minutes_form_start_hour_to_start') ?? 0,
            eventLengthInHours: $event->getAttribute('event_length_in_hours') ?? 0,
            created_by: $user ? new MinimalCreatorDTO(
                id: $user->id,
                first_name: $user->first_name,
                last_name: $user->last_name,
                profile_photo_url: $user->profile_photo_url ?? null,
            ) : null,
            is_series: $event->is_series,
            series_id: $event->series_id,
            eventProperties: self::serializeEventProperties($event),
            occupancy_option: $event->occupancy_option,
            declinedRoomId: $event->declined_room_id,
            eventStatus: $eventStatus ? [
                'id' => $eventStatus->id,
                'color' => $eventStatus->color,
            ] : null,
            subEvents: self::serializeSubEvents($event),
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: $event->getAttribute('has_pending_verification')
                ?? $event->getAttribute('has_verification') ?? false,
            hasTimelines: $event->timelines_exists ?? $event->hasTimelines()
        );
    }

}

<?php

namespace Artwork\Modules\Calendar\DTO;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class EventWithoutRoomDTO extends Data
{
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
        public ?MinimalCreatorDTO $created_by,
        public ?bool $is_series,
        public ?bool $occupancy_option,
        public ?int $declinedRoomId = null,
        public ?array $eventStatus,
        public ?array $eventProperties,
        public ?array $subEvents,
        public ?string $option_string,
        public ?bool $isPlanning = false,
        public ?bool $hasVerification = false,
    ) {
    }

    public static function formModel(
        Event $event,
        UserCalendarSettings|UserDailyViewCalendarSettings $userCalendarSettings,
        Collection $eventTypes,
    ): EventWithoutRoomDTO {
        $eventType = $eventTypes[$event->event_type_id] ?? null;
        $creator = $event->creator;

        return new self(
            id: $event->id,
            start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
            end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
            eventName: $event->eventName,
            description: $event->description,
            project: $event->project_id ? ProjectDTO::fromModel($event->project, $userCalendarSettings) : null,
            eventType: $eventType ? [
                'id' => $eventType->id,
                'name' => $eventType->name,
                'abbreviation' => $eventType->abbreviation,
                'hex_code' => $eventType->hex_code,
            ] : null,
            shifts: $userCalendarSettings->work_shifts
                ? MinimalShiftPlanShiftResource::collection($event->shifts)->resolve()
                : null,
            allDay: $event->allDay,
            roomId: $event->room_id,
            roomName: $event->room?->name,
            startHour: $event->startHour,
            minutesFormStartHourToStart: $event->minutesFormStartHourToStart,
            eventLengthInHours: $event->eventLengthInHours,
            created_by: $creator ? new MinimalCreatorDTO(
                id: $creator->id,
                first_name: $creator->first_name,
                last_name: $creator->last_name,
                profile_photo_url: $creator->profile_photo_url ?? null,
            ) : null,
            is_series: $event->is_series,
            occupancy_option: $event->occupancy_option,
            declinedRoomId: $event->declined_room_id,
            eventStatus: $event->eventStatus ? [
                'id' => $event->eventStatus->id,
                'color' => $event->eventStatus->color,
            ] : null,
            eventProperties: self::serializeEventProperties($event),
            subEvents: self::serializeSubEvents($event),
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: $event->getAttribute('has_verification') ?? false,
        );
    }

    private static function serializeSubEvents(Event $event): array
    {
        if (!$event->relationLoaded('subEvents')) {
            return [];
        }

        return $event->subEvents->map(fn ($sub) => [
            'id' => $sub->id,
            'eventName' => $sub->eventName,
            'allDay' => $sub->allDay,
            'formattedDates' => $sub->formattedDates,
            'type' => $sub->type ? [
                'hex_code' => $sub->type->hex_code,
                'abbreviation' => $sub->type->abbreviation,
                'name' => $sub->type->name,
            ] : null,
            'event_properties' => $sub->eventProperties->map(fn ($p) => ['id' => $p->id])->values()->all(),
        ])->values()->all();
    }

    private static function serializeEventProperties(Event $event): array
    {
        if (!$event->relationLoaded('eventProperties')) {
            return [];
        }

        return $event->eventProperties->map(fn ($p) => [
            'id' => $p->id,
            'icon' => $p->icon ?? null,
        ])->values()->all();
    }
}

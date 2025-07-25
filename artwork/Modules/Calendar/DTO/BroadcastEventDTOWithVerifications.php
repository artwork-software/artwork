<?php

namespace Artwork\Modules\Calendar\DTO;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Data;

class BroadcastEventDTOWithVerifications extends Data
{
    public function __construct(
        public int $id,
        public string $start,
        public string $end,
        public ?string $eventName,
        public ?string $description,
        public ?ProjectDTO $project,
        public ?EventType $eventType,
        public Collection|AnonymousResourceCollection $shifts,
        public bool $allDay,
        public ?int $roomId,
        public ?string $roomName,
        public ?array $daysOfEvent,
        public ?int $startHour,
        public ?int $minutesFormStartHourToStart,
        public ?int $eventLengthInHours,
        public ?User $created_by,
        public ?array $formattedDates,
        public ?bool $is_series,
        public ?bool $occupancy_option,
        public ?int $declinedRoomId = null,
        public ?EventStatus $eventStatus,
        public Collection $eventProperties,
        public Collection $subEvents,
        public ?SeriesEvents $series,
        public ?string $option_string,
        public ?bool $isPlanning = false,
        public ?bool $hasVerification = false,
        public ?Collection $verifications = null,
        public ?Collection $timelines = null,
    ) {
    }



    public static function formModel(
        Event $event,
    ): BroadcastEventDTOWithVerifications {
        return new self(
            id: $event->id,
            start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
            end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
            eventName: $event->eventName,
            description: $event->description,
            project: $event->project_id ? ProjectDTO::fromModel($event->project, null) : null,
            eventType: $event->event_type ?? null,
            shifts: MinimalShiftPlanShiftResource::collection($event->shifts),
            allDay: $event->allDay,
            roomId: $event->room_id,
            roomName: $event->room?->name,
            daysOfEvent: $event->daysOfEvent,
            startHour: $event->startHour,
            minutesFormStartHourToStart: $event->minutesFormStartHourToStart,
            eventLengthInHours: $event->eventLengthInHours,
            created_by: $event->creator,
            formattedDates: $event->formattedDates,
            is_series: $event->is_series,
            occupancy_option: $event->occupancy_option,
            declinedRoomId: $event->declined_room_id,
            eventStatus: $event->eventStatus,
            eventProperties: $event->eventProperties,
            subEvents: $event->subEvents,
            series: $event->is_series ? $event->series : null,
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: $event->getAttribute('has_verification') ?? false,
            verifications: $event->verifications()
                ->where('status', 'rejected')
                ->orderBy('created_at', 'desc')
                ->with('verifier')
                ->get(),
            timelines: $event->timelines ?? new Collection(),
        );
    }
}

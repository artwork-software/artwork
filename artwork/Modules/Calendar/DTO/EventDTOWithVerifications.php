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
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;

class EventDTOWithVerifications extends Data
{
    use SerializesEventRelations;
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
        public ?array $eventProperties,
        public ?bool $occupancy_option,
        public ?int $declinedRoomId = null,
        // Kann ein Array, null ODER ein Lazy/InertiaLazy sein (siehe fromModel: Lazy::inertia(...)),
        // wenn ein Event-Status existiert, aber use_event_status_color deaktiviert ist.
        public array|Lazy|null $eventStatus,
        public ?array $subEvents,
        public ?string $option_string,
        public ?bool $isPlanning = false,
        public ?bool $hasVerification = false,
        public ?array $verifications = null,
    ) {
    }

    public static function fromModel(
        Event $event,
        UserCalendarSettings|UserDailyViewCalendarSettings $userCalendarSettings,
        Collection $projects,
        Collection $eventTypes,
        Collection $users,
        Collection $eventStatuses,
        ?Collection $verificationsByEvent = null,
    ): EventDTOWithVerifications {
        $eventType = $eventTypes[$event->event_type_id] ?? null;
        $user = $event->user_id ? ($users[$event->user_id] ?? null) : null;
        $project = $event->project_id ? ($projects[$event->project_id] ?? null) : null;

        $useStatusColor = $userCalendarSettings->use_event_status_color ?? false;
        $eventStatusModel = null;
        if ($event->event_type_id !== null) {
            $eventStatusModel = $eventStatuses[$event->event_status_id] ?? null;
        }

        return new self(
            id: $event->id,
            start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
            end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
            eventName: $event->eventName,
            description: $event->description,
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
            eventProperties: self::serializeEventProperties($event),
            occupancy_option: $event->occupancy_option,
            declinedRoomId: $event->declined_room_id,
            eventStatus: ($useStatusColor && $eventStatusModel) ? [
                'id' => $eventStatusModel->id,
                'color' => $eventStatusModel->color,
            ] : ($eventStatusModel ? Lazy::inertia(fn () => [
                'id' => $eventStatusModel->id,
                'color' => $eventStatusModel->color,
            ]) : null),
            subEvents: self::serializeSubEvents($event),
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: $event->getAttribute('has_pending_verification')
                ?? $event->getAttribute('has_verification') ?? false,
            verifications: self::serializeVerifications($verificationsByEvent, $event->id),
        );
    }

    private static function serializeVerifications(?Collection $verificationsByEvent, int $eventId): array
    {
        if ($verificationsByEvent === null) {
            return [];
        }

        $verifications = $verificationsByEvent[$eventId] ?? collect();

        return $verifications->map(fn ($v) => [
            'id' => $v->id,
            'status' => $v->status,
            'created_at' => $v->created_at?->toIso8601String(),
            'verifier' => $v->verifier ? [
                'id' => $v->verifier->id,
                'first_name' => $v->verifier->first_name ?? null,
                'last_name' => $v->verifier->last_name ?? null,
            ] : null,
        ])->values()->all();
    }
}

<?php

namespace Artwork\Modules\Calendar\DTO;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Calendar\Traits\SerializesEventRelations;
use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class BroadcastEventDTOWithVerifications extends Data
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
        public ?MinimalCreatorDTO $created_by,
        public ?bool $is_series,
        public ?bool $occupancy_option,
        public ?int $declinedRoomId,
        public ?array $eventStatus,
        public ?array $eventProperties,
        public ?array $subEvents,
        public ?string $option_string,
        public ?bool $isPlanning = false,
        public ?bool $hasVerification = false,
        public ?array $verifications = null,
        public ?Collection $timelines = null,
        public ?bool $hasTimelines = false,
        // Wie beim ersten Laden (EventDTO/EventDTOWithVerifications): ohne fehlten nach einem Live-Update
        // die Einlasszeile und die Serien-Zuordnung in der Kachel
        public ?string $admission_time = null,
        public ?int $series_id = null,
    ) {
    }



    public static function formModel(
        Event $event,
    ): BroadcastEventDTOWithVerifications {
        $creator = $event->creator;

        return new self(
            id: $event->id,
            start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
            end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
            eventName: $event->eventName,
            description: $event->description,
            project: $event->project_id && $event->project ? ProjectDTO::fromModel($event->project, null) : null,
            eventType: $event->event_type ? [
                'id' => $event->event_type->id,
                'name' => $event->event_type->name,
                'abbreviation' => $event->event_type->abbreviation,
                'hex_code' => $event->event_type->hex_code,
            ] : null,
            shifts: MinimalShiftPlanShiftResource::collection($event->shifts)->resolve(),
            allDay: (bool) $event->allDay,
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
                'name' => $event->eventStatus->name,
                'color' => $event->eventStatus->color,
            ] : null,
            eventProperties: self::serializeEventProperties($event),
            subEvents: self::serializeSubEvents($event),
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: $event->getAttribute('has_verification') ?? false,
            verifications: self::serializeVerifications($event),
            timelines: $event->timelines ?? new Collection(),
            hasTimelines: $event->hasTimelines(),
            // TIME-Spalte ("HH:mm:ss") → "HH:mm"
            admission_time: $event->admission_time ? substr($event->admission_time, 0, 5) : null,
            series_id: $event->series_id,
        );
    }

    private static function serializeVerifications(Event $event): array
    {
        $verifications = $event->verifications()
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->with('verifier')
            ->get();

        return $verifications->map(fn ($v) => [
            'id' => $v->id,
            'status' => $v->status,
            'created_at' => $v->createdAtIso8601(),
            'verifier' => $v->verifier ? [
                'id' => $v->verifier->id,
                'first_name' => $v->verifier->first_name ?? null,
                'last_name' => $v->verifier->last_name ?? null,
            ] : null,
        ])->values()->all();
    }
}

<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class EventDTO extends Data
{
    public function __construct(
        public int $id,
        public string $start,
        public string $end,
        public ?string $eventName,
        public ?string $description,
        public ?ProjectDTO $project,
        public ?int $eventTypeId,
        public ?string $eventTypeName,
        public ?string $eventTypeAbbreviation,
        public ?string $eventTypeColor,
        public ?int $eventStatusId,
        public ?string $eventStatusColor,
        public ?array $shifts,
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
        public Collection $eventProperties,
        public ?bool $occupancy_option,
        public ?int $declinedRoomId = null,
    ) {
    }
}
<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class ShiftDTO extends Data
{
    public function __construct(
        public int $id,
        public string $startDate,
        public string $endDate,
        public string $start,
        public string $end,
        public int $break_minutes,
        public ?int $eventId,
        public ?string $description,
        public ?Craft $craft,
        public ?Collection $shifts_qualifications,
        public ?Collection $users,
        public ?Collection $freelancer,
        public ?Collection $serviceProviders,
        public ?Room $room,
        public ?array $daysOfShift,
        public ?int $roomId,
        public ?array $formatted_dates,
        public ?string $startOfShift,
        public ?bool $isCommitted = false,
        public ?bool $inWorkflow = false,
        public ?Project $project = null,
        public ?Collection $globalQualifications = null,
        public ?int $shiftGroupId = null,
        public ?ShiftGroup $shiftGroup = null,
        //public EventDTO $event
    ){
    }


    public static function fromModel(Shift $shift, ?Project $project = null): ShiftDTO
    {
        return new self(
            id: $shift->id,
            startDate: (string) $shift->start_date,
            endDate: (string) $shift->end_date,
            start: (string) $shift->start,
            end: (string) $shift->end,
            break_minutes: (int) $shift->break_minutes,
            eventId: $shift->event_id,
            description: $shift->description,
            craft: $shift->craft,
            shifts_qualifications: $shift->shiftsQualifications,
            users: $shift->users,
            freelancer: $shift->freelancer,
            serviceProviders: $shift->serviceProvider,
            room: $shift->room,
            daysOfShift: $shift->getAttribute('days_of_shift'),
            roomId: $shift->room_id,
            formatted_dates: $shift->getAttribute('formatted_dates'),
            startOfShift: $shift->start_date instanceof \Carbon\CarbonInterface
                ? $shift->start_date->format('d.m.Y')
                : Carbon::parse($shift->start_date)->format('d.m.Y'),
            isCommitted: $shift->is_committed,
            inWorkflow: $shift->in_workflow,
            project: $project,
            globalQualifications: $shift->globalQualifications,
            shiftGroupId: $shift->shift_group_id,
            shiftGroup: $shift->shiftGroup,
        );
    }
}

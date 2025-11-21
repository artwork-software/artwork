<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftGroup;
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
        public ?Project $project = null,
        public ?Collection $globalQualifications = null,
        public ?int $shiftGroupId = null,
        public ?ShiftGroup $shiftGroup = null,
        //public EventDTO $event
    ){
    }


    public static function fromModel(Shift $shift): ShiftDTO
    {
        // Ensure global qualifications of assigned persons are loaded so the frontend
        // can compute personGlobalQualificationsInDemand correctly.
        // We keep the payload minimal by selecting only the id on the related models.
        $shift->loadMissing([
            'users.globalQualifications:id',
            'freelancer.globalQualifications:id',
            'serviceProvider.globalQualifications:id',
        ]);

        return new self(
            id: $shift->id,
            startDate: $shift->start_date,
            endDate: $shift->end_date,
            start: $shift->start,
            end: $shift->end,
            break_minutes: $shift->break_minutes,
            eventId: $shift?->event_id,
            description: $shift->description,
            craft: $shift->craft()->with('qualifications')->first(),
            shifts_qualifications: $shift->shiftsQualifications,
            users: $shift->users,
            freelancer: $shift->freelancer,
            serviceProviders: $shift->serviceProvider,
            room: $shift->room,
            daysOfShift: $shift->getAttribute('days_of_shift'),
            roomId: $shift?->room_id,
            formatted_dates: $shift->getAttribute('formatted_dates'),
            startOfShift: $shift->getAttribute('start_date')->format('d.m.Y'),
            isCommitted: $shift->is_committed,
            project: $shift?->project,
            globalQualifications: $shift->globalQualifications,
            shiftGroupId: $shift->shift_group_id,
            shiftGroup: $shift->shiftGroup,
            //event: EventDTO::fromModel($shift->event)
        );
    }
}

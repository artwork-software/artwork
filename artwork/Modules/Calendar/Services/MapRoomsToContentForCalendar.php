<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

trait MapRoomsToContentForCalendar
{
    public function mapRoomsToContentForCalendar(Collection $rooms, $startDate, $endDate): CalendarFrontendDataDTO
    {
        $period = collect(CarbonPeriod::create($startDate, '1 day', $endDate))
            ->mapWithKeys(fn($date) => [$date->format('d.m.Y') => ['events' => []]])
            ->toArray();
        $roomsData = $rooms->map(function ($room) use ($period) {
            $content = $period;

            $groupedEvents = $room->events->flatMap(
                fn($eventDTO) => collect($eventDTO->daysOfEvent)->map(
                    fn($date) => ['date' => $date, 'event' => $eventDTO]
                )
            )->groupBy('date');

            foreach ($groupedEvents as $date => $eventsOnDate) {
                if (isset($content[$date])) {
                    $content[$date]['events'] = $eventsOnDate->pluck('event')->all();
                }
            }

            return new CalendarRoomDTO(
                roomId: $room->id,
                roomName: $room->name,
                content: $content
            );
        })->values()->toArray();

        return new CalendarFrontendDataDTO(rooms: $roomsData);
    }
}

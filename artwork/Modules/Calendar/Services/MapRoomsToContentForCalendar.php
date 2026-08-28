<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\CalendarShiftDTO;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

trait MapRoomsToContentForCalendar
{
    public function mapRoomsToContentForCalendar(Collection $rooms, $startDate, $endDate): CalendarFrontendDataDTO
    {
        $period = collect(CarbonPeriod::create($startDate, '1 day', $endDate))
            ->mapWithKeys(fn($date) => [$date->format('d.m.Y') => ['events' => [], 'shifts' => []]])
            ->toArray();
        $roomsData = $rooms->map(function ($room) use ($period) {
            $content = $period;

            $groupedEvents = $room->events->flatMap(
                function ($eventDTO) {
                    $eventStart = Carbon::parse($eventDTO->start);
                    $eventEnd = Carbon::parse($eventDTO->end);
                    // Defekte Altdaten (Ende vor Start, z.B. 22:00–00:00 am selben Tag)
                    // ergäben eine leere Periode — mindestens am Starttag anzeigen.
                    if ($eventEnd->lt($eventStart)) {
                        $eventEnd = $eventStart;
                    }
                    return collect(CarbonPeriod::create($eventStart, $eventEnd))
                        ->map(fn($date) => [
                            'date' => $date->format('d.m.Y'),
                            'event' => $eventDTO,
                        ]);
                }
            )->groupBy('date');

            foreach ($groupedEvents as $date => $eventsOnDate) {
                if (isset($content[$date])) {
                    $content[$date]['events'] = $eventsOnDate->pluck('event')->all();
                }
            }

            // Nur explizit angehängte ShiftDTOs lesen — sonst würde $room->shifts die
            // Eloquent-Relation lazy-laden (PDF-Pfad setzt keine Shifts).
            $roomShifts = $room->getAttributes()['shifts'] ?? collect();
            $groupedShifts = collect($roomShifts)->flatMap(
                fn($shiftDTO) => $shiftDTO->startDate && $shiftDTO->endDate
                    ? collect(
                        CarbonPeriod::create(
                            Carbon::parse($shiftDTO->startDate),
                            Carbon::parse($shiftDTO->endDate)
                        )
                    )->map(fn($date) => [
                        'date' => $date->format('d.m.Y'),
                        'shift' => $shiftDTO,
                    ])
                    : collect()
            )->groupBy('date');

            foreach ($groupedShifts as $date => $shiftsOnDate) {
                if (isset($content[$date])) {
                    $content[$date]['shifts'] = $shiftsOnDate->pluck('shift')->all();
                }
            }

            return new CalendarRoomDTO(
                roomId: $room->id,
                roomName: $room->name,
                content: $content,
                roomColor: $room->getEffectiveColor()
            );
        })->values()->toArray();

        return new CalendarFrontendDataDTO(rooms: $roomsData);
    }

    /**
     * Lädt eigenständige Schichten (ohne Termin) für die gefilterten Räume und hängt sie
     * als ShiftDTO-Collections an $room->shifts. Termingebundene Schichten sind Alt-Bestand
     * und werden im Kalender bewusst nicht mehr angezeigt.
     */
    public function attachStandaloneShiftsToRooms(
        Collection $rooms,
        $startDate,
        $endDate,
        ?UserFilter $filter = null,
    ): Collection {
        $roomIds = $rooms->pluck('id');

        // Kalender-Kacheln zeigen nur Zählstände — das CalendarShiftDTO aggregiert
        // die Zuweisungen serverseitig; workers/vacations werden weder serialisiert
        // noch (im Fall der Abwesenheiten) überhaupt geladen. Die vollen Worker-
        // Daten liefert weiterhin der Schichtplan-Pfad (ShiftDTO).
        $shifts = Shift::query()
            ->select([
                'id',
                'start_date',
                'end_date',
                'start',
                'end',
                'break_minutes',
                'event_id',
                'description',
                'craft_id',
                'room_id',
                'project_id',
                'is_committed',
                'in_workflow',
                'shift_group_id',
            ])
            ->whereNull('event_id')
            ->whereIn('room_id', $roomIds)
            ->when(!empty($filter?->craft_ids), fn($q) => $q->whereIn('craft_id', $filter->craft_ids))
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->with([
                'craft:id,name,abbreviation,color',
                'shiftsQualifications',
                'globalQualifications',
                'users:id,first_name,last_name',
                'users.globalQualifications:id',
                'freelancer:id,first_name,last_name',
                'freelancer.globalQualifications:id',
                'serviceProvider:id,provider_name',
                'serviceProvider.globalQualifications:id',
                'project:id,name',
            ])
            ->orderBy('start')
            ->get();

        $shiftDTOs = $shifts
            ->map(fn(Shift $shift) => CalendarShiftDTO::fromModel($shift))
            ->groupBy('roomId');

        foreach ($rooms as $room) {
            $room->shifts = $shiftDTOs[$room->id] ?? collect();
        }

        return $rooms;
    }
}

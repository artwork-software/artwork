<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Models\UserShiftListViewSettings;
use Carbon\Carbon;

readonly class ShiftListViewService
{
    public function getGroupedShifts(
        Carbon $startDate,
        Carbon $endDate,
        UserShiftListViewSettings $settings,
        ?UserFilter $userFilter = null
    ): array {
        $query = Shift::query()
            ->with([
                'craft.qualifications',
                'room',
                'project',
                'event.project',
                'users',
                'freelancer',
                'serviceProvider',
                'shiftsQualifications',
                'shiftGroup',
                'globalQualifications',
            ])
            ->eventStartDayAndEventEndDayBetween($startDate, $endDate);

        if ($userFilter) {
            if (!empty($userFilter->craft_ids)) {
                $query->whereIn('craft_id', $userFilter->craft_ids);
            }

            if (!empty($userFilter->room_ids)) {
                $query->whereIn('room_id', $userFilter->room_ids);
            }

            if (!empty($userFilter->event_type_ids)) {
                $query->whereHas('event', function ($q) use ($userFilter) {
                    $q->whereIn('event_type_id', $userFilter->event_type_ids);
                });
            }

            if (!empty($userFilter->area_ids)) {
                $query->whereHas('room', function ($q) use ($userFilter) {
                    $q->whereIn('area_id', $userFilter->area_ids);
                });
            }

            if (!empty($userFilter->room_category_ids)) {
                $query->whereHas('room.categories', function ($q) use ($userFilter) {
                    $q->whereIn('room_categories.id', $userFilter->room_category_ids);
                });
            }

            if (!empty($userFilter->room_attribute_ids)) {
                $query->whereHas('room.attributes', function ($q) use ($userFilter) {
                    $q->whereIn('room_attributes.id', $userFilter->room_attribute_ids);
                });
            }

            if (!empty($userFilter->event_property_ids)) {
                $query->whereHas('event.eventProperties', function ($q) use ($userFilter) {
                    $q->whereIn('event_properties.id', $userFilter->event_property_ids);
                });
            }
        }

        $shifts = $query->get();

        if (!$settings->show_fully_staffed_shifts) {
            $shifts = $shifts->filter(function (Shift $shift) {
                $assignedCount = $shift->users->count()
                    + $shift->freelancer->count()
                    + $shift->serviceProvider->count();
                $requiredCount = $shift->shiftsQualifications->sum('value');

                return $assignedCount < $requiredCount;
            });
        }

        $shifts = $shifts->sortBy([
            ['start_date', 'asc'],
            fn (Shift $a, Shift $b) => ($a->room->position ?? 0) <=> ($b->room->position ?? 0),
            fn (Shift $a, Shift $b) => ($a->craft->position ?? 0) <=> ($b->craft->position ?? 0),
            ['start', 'asc'],
        ]);

        // Group by day, then by room — using arrays (not objects with numeric keys)
        // to preserve room.position ordering in JavaScript
        $dayMap = [];
        foreach ($shifts as $shift) {
            $day = Carbon::parse($shift->start_date)->format('Y-m-d');
            $roomId = $shift->room_id ?? 0;

            if (!isset($dayMap[$day])) {
                $dayMap[$day] = [];
            }
            if (!isset($dayMap[$day][$roomId])) {
                $dayMap[$day][$roomId] = [
                    'room_id' => $roomId,
                    'room' => $shift->room,
                    'shifts' => [],
                ];
            }
            $dayMap[$day][$roomId]['shifts'][] = $shift;
        }

        ksort($dayMap);

        // Convert to sequential arrays so JS preserves insertion order
        $result = [];
        foreach ($dayMap as $day => $rooms) {
            $result[] = [
                'day' => $day,
                'rooms' => array_values($rooms),
            ];
        }

        return $result;
    }
}

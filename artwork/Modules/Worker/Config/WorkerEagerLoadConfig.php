<?php

namespace Artwork\Modules\Worker\Config;

use Carbon\Carbon;

class WorkerEagerLoadConfig
{
    public static function getShiftPlanEagerLoads(Carbon|null $startDate, Carbon|null $endDate): array
    {
        return [
            'dayServices' => function ($query) use ($startDate, $endDate){
                $query->whereBetween('day_serviceables.date', [$startDate, $endDate]);
            },
            'assignedCrafts',
            'managingCrafts',
            'vacations' => function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('vacations.date', [$startDate->toDateString(), $endDate->toDateString()]);
                }
            },
            'shifts' => function ($query) use ($startDate, $endDate) {
                $query->select([
                    'shifts.id',
                    'shifts.start_date',
                    'shifts.end_date',
                    'shifts.start',
                    'shifts.end',
                    'shifts.description',
                    'shifts.is_committed',
                    'shifts.event_start_day',
                    'shifts.event_end_day',
                    'shifts.craft_id',
                    'shifts.room_id',
                    'shifts.event_id',
                    'shifts.shift_group_id',
                ])->with([
                    'craft:id,name,abbreviation',
                    'shiftGroup:id,name',
                    'event:id,name,eventName,room_id,event_type_id',
                    'event.room:id,name',
                    'event.event_type:id,abbreviation',
                    'room:id,name',
                    'shiftsQualifications' => function ($q) {
                        $q->select(['id', 'shift_id', 'shift_qualification_id', 'value', 'deleted_at']);
                    },
                ]);
                if ($startDate && $endDate) {
                    $query->where('shifts.start_date', '>=', $startDate)->where('shifts.end_date', '<=', $endDate);
                }
            },
            'shiftQualifications' => function ($query) {
                $query->select([
                    'shift_qualifications.id',
                    'shift_qualifications.name',
                    'shift_qualifications.icon',
                    'shift_qualifications.available',
                    'shift_qualifications.created_at'
                ]);
            },
        ];
    }
}

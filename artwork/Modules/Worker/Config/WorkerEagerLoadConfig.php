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
                    $query->where('vacations.start_time', '>=', $startDate)->where('vacations.end_time', '<=', $endDate);
                }
            },
            'shifts' => function ($query) use ($startDate, $endDate) {
                $query->with([
                    'craft',
                    'shiftGroup',
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

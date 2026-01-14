<?php

namespace Artwork\Modules\Worker\Config;

class WorkerEagerLoadConfig
{
    public static function getShiftPlanEagerLoads(): array
    {
        return [
            'dayServices',
            'assignedCrafts',
            'managingCrafts',
            'vacations',
            'shifts' => function ($query) {
                $query->with([
                    'event',
                    'event.room',
                    'event.event_type',
                    'craft',
                    'shiftGroup',
                    'shiftsQualifications' => function ($q) {
                        $q->select(['id', 'shift_id', 'shift_qualification_id', 'value', 'deleted_at']);
                    },
                ]);
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

    public static function getUserSpecificEagerLoads(): array
    {
        return [
            'workTimeBookings',
            'workTimes',
        ];
    }
}

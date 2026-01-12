<?php

namespace Artwork\Modules\Worker\Config;

class WorkerEagerLoadConfig
{
    /**
     * Gibt die Eager-Loading-Konfiguration für Workers zurück
     *
     * @return array<string, mixed>
     */
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
            'shiftQualifications',
        ];
    }

    /**
     * Gibt zusätzliche Eager-Loads für User zurück (nicht für Freelancer/ServiceProvider)
     *
     * @return array<string, mixed>
     */
    public static function getUserSpecificEagerLoads(): array
    {
        return [
            'workTimeBookings',
            'workTimes',
        ];
    }
}

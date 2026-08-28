<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Shift\Models\Shift;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

/**
 * Schlanke Schicht-Repräsentation für die Kalender-Ansichten (BaseCalendar,
 * Tagesansicht). Die Kalender-Kachel zeigt nur Zählstände (besetzt/gefordert)
 * — die vollen workers-Objekte inkl. Pivot/Qualifikationen/Abwesenheiten des
 * ShiftDTO machten ~70 % des Monats-Payloads aus, ohne dass die Kachel sie
 * liest. Der Schichtplan nutzt weiterhin das volle ShiftDTO.
 */
class CalendarShiftDTO extends Data
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
        public ?int $craftId,
        public ?array $shifts_qualifications,
        public ?int $roomId,
        /** Anzahl zugewiesener Personen je shift_qualification_id */
        public ?array $assignedCounts = null,
        /** Anzahl zugewiesener Personen je globaler Qualifikation (nur geforderte) */
        public ?array $globalAssignedCounts = null,
        /** Gesamtzahl zugewiesener Personen (für die Kompakt-Kachel) */
        public int $assignedWorkersTotal = 0,
        public ?bool $isCommitted = false,
        public ?bool $inWorkflow = false,
        public ?int $projectId = null,
        public ?array $globalQualifications = null,
        public ?int $shiftGroupId = null,
        public ?array $craft = null,
        public ?string $projectName = null,
    ) {
    }

    public static function fromModel(Shift $shift): CalendarShiftDTO
    {
        $resolvedProject = $shift->relationLoaded('project') ? $shift->project : null;

        [$assignedCounts, $globalAssignedCounts, $assignedTotal] = self::aggregateWorkerCounts($shift);

        return new self(
            id: $shift->id,
            startDate: $shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : '',
            endDate: $shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : '',
            start: (string) $shift->start,
            end: (string) $shift->end,
            break_minutes: (int) $shift->break_minutes,
            eventId: $shift->event_id,
            description: $shift->description,
            craftId: $shift->craft_id,
            shifts_qualifications: self::serializeShiftsQualifications($shift),
            roomId: $shift->room_id,
            assignedCounts: $assignedCounts,
            globalAssignedCounts: $globalAssignedCounts,
            assignedWorkersTotal: $assignedTotal,
            isCommitted: $shift->is_committed,
            inWorkflow: $shift->in_workflow,
            projectId: $resolvedProject?->id,
            globalQualifications: self::serializeGlobalQualifications($shift),
            shiftGroupId: $shift->shift_group_id,
            craft: self::serializeCraft($shift),
            projectName: $resolvedProject?->name,
        );
    }

    /**
     * @return array{0: array<int|string, int>, 1: array<int|string, int>, 2: int}
     */
    private static function aggregateWorkerCounts(Shift $shift): array
    {
        $assignedCounts = [];
        $globalAssignedCounts = [];
        $total = 0;

        // Nur an der Schicht geforderte globale Qualifikationen zählen — alles andere
        // wäre totes Payload und würde Konsumenten Phantom-Zuweisungen vorgaukeln.
        $demandedGlobalQualificationIds = [];
        if ($shift->relationLoaded('globalQualifications')) {
            foreach ($shift->globalQualifications as $globalQualification) {
                if (($globalQualification->pivot->quantity ?? 0) > 0) {
                    $demandedGlobalQualificationIds[$globalQualification->id] = true;
                }
            }
        }

        foreach (['users', 'freelancer', 'serviceProvider'] as $relation) {
            if (!$shift->relationLoaded($relation)) {
                continue;
            }
            $collection = $shift->{$relation};
            if ($collection === null) {
                continue;
            }

            foreach ($collection as $worker) {
                $total++;

                $qualificationId = $worker->pivot->shift_qualification_id ?? null;
                if ($qualificationId !== null) {
                    $assignedCounts[$qualificationId] = ($assignedCounts[$qualificationId] ?? 0) + 1;
                }

                if ($worker->relationLoaded('globalQualifications')) {
                    foreach ($worker->globalQualifications as $globalQualification) {
                        if (!isset($demandedGlobalQualificationIds[$globalQualification->id])) {
                            continue;
                        }
                        $globalAssignedCounts[$globalQualification->id] =
                            ($globalAssignedCounts[$globalQualification->id] ?? 0) + 1;
                    }
                }
            }
        }

        return [$assignedCounts, $globalAssignedCounts, $total];
    }

    private static function serializeShiftsQualifications(Shift $shift): array
    {
        if (!$shift->relationLoaded('shiftsQualifications')) {
            return [];
        }

        return $shift->shiftsQualifications->map(fn ($sq) => [
            'id' => $sq->id,
            'shift_id' => $sq->shift_id,
            'shift_qualification_id' => $sq->shift_qualification_id,
            'value' => $sq->value,
            'overbooked_value' => $sq->overbooked_value,
        ])->values()->all();
    }

    private static function serializeCraft(Shift $shift): ?array
    {
        $craft = $shift->relationLoaded('craft') ? $shift->craft : null;

        if ($craft === null) {
            return null;
        }

        return [
            'id' => $craft->id,
            'name' => $craft->name,
            'abbreviation' => $craft->abbreviation,
            'color' => $craft->color,
        ];
    }

    private static function serializeGlobalQualifications(Shift $shift): array
    {
        if (!$shift->relationLoaded('globalQualifications')) {
            return [];
        }

        return $shift->globalQualifications->map(fn ($gq) => [
            'id' => $gq->id,
            'name' => $gq->name,
            'pivot' => [
                'quantity' => $gq->pivot->quantity ?? 0,
            ],
        ])->values()->all();
    }
}

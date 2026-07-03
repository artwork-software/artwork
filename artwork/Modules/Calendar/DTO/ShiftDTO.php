<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Carbon\Carbon;
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
        public ?int $craftId,
        public ?array $shifts_qualifications,
        public ?array $workers,
        public ?int $roomId,
        public ?bool $isCommitted = false,
        public ?bool $inWorkflow = false,
        public ?int $projectId = null,
        public ?array $globalQualifications = null,
        public ?int $shiftGroupId = null,
        public ?array $craft = null,
        public ?string $projectName = null,
    ) {
    }


    public static function fromModel(Shift $shift, ?Project $project = null): ShiftDTO
    {
        $resolvedProject = $project ?? ($shift->relationLoaded('project') ? $shift->project : null);

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
            workers: self::serializeAllWorkers($shift),
            roomId: $shift->room_id,
            isCommitted: $shift->is_committed,
            inWorkflow: $shift->in_workflow,
            projectId: $resolvedProject?->id,
            globalQualifications: self::serializeGlobalQualifications($shift),
            shiftGroupId: $shift->shift_group_id,
            craft: self::serializeCraft($shift),
            projectName: $resolvedProject?->name,
        );
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

    /**
     * Merge users, freelancers and service providers into a single workers array.
     */
    private static function serializeAllWorkers(Shift $shift): array
    {
        $workers = [];

        foreach (['users' => 'user', 'freelancer' => 'freelancer', 'serviceProvider' => 'service_provider'] as $relation => $type) {
            $collection = $shift->{$relation};
            if ($collection === null || $collection->isEmpty()) {
                continue;
            }

            foreach ($collection as $worker) {
                $workers[] = [
                    'id' => $worker->id,
                    'type' => $type,
                    'pivot' => $worker->pivot ? [
                        'id' => $worker->pivot->id ?? null,
                        'shift_qualification_id' => $worker->pivot->shift_qualification_id ?? null,
                        'is_overbooked' => (bool) ($worker->pivot->is_overbooked ?? false),
                        'craft_abbreviation' => $worker->pivot->craft_abbreviation ?? null,
                        'short_description' => $worker->pivot->short_description ?? null,
                        'start_time' => $worker->pivot->start_time ?? null,
                        'end_time' => $worker->pivot->end_time ?? null,
                    ] : null,
                    'first_name' => $type === 'service_provider'
                        ? ($worker->provider_name ?? '')
                        : ($worker->first_name ?? ''),
                    'last_name' => $type === 'service_provider' ? '' : ($worker->last_name ?? ''),
                    'name' => $type === 'service_provider'
                        ? ($worker->provider_name ?? '')
                        : trim(($worker->first_name ?? '') . ' ' . ($worker->last_name ?? '')),
                    'globalQualifications' => ($worker->relationLoaded('globalQualifications'))
                        ? $worker->globalQualifications->map(fn ($q) => ['id' => $q->id])->values()->all()
                        : [],
                ];
            }
        }

        return $workers;
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

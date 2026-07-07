<?php

namespace Artwork\Modules\Shift\Abstracts;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin User|Freelancer|ServiceProvider
 */
class WorkerShiftPlanResource extends JsonResource
{
    private ?Carbon $startDate = null;
    private ?Carbon $endDate   = null;

    private ?array $qualificationsCache = null;

    /**
     * @return array<string, mixed>
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray(Request $request): array
    {
        if ($this->startDate === null || $this->endDate === null) {
            throw new \LogicException(
                'Start- und Enddatum müssen vor Verwendung von WorkerShiftPlanResource gesetzt werden.'
            );
        }

        $shifts = $this->loadDesiredShiftsForShiftPlan();

        $shiftsArray = $shifts->toArray();

        $shiftIds = $shifts->pluck('id')->toArray();

        $shiftQualifications = $this->relationLoaded('shiftQualifications')
            ? $this->getRelation('shiftQualifications')
            : $this->getAttribute('shiftQualifications');

        $shiftQualificationObjects = [];
        if ($shiftQualifications instanceof Collection) {
            $shiftQualificationObjects = $shiftQualifications->map(function ($qual) {
                $pivot = $qual->pivot ?? null;
                return [
                    'id' => $qual->id,
                    'name' => $qual->name ?? null,
                    'icon' => $qual->icon ?? null,
                    'available' => $qual->available ?? null,
                    'pivot' => [
                        'craft_id' => $pivot?->craft_id ?? null,
                    ],
                ];
            })->toArray();
        } elseif (is_array($shiftQualifications)) {
            $shiftQualificationObjects = array_map(function ($qual) {
                if (is_object($qual)) {
                    $pivot = $qual->pivot ?? null;
                    return [
                        'id' => $qual->id,
                        'name' => $qual->name ?? null,
                        'icon' => $qual->icon ?? null,
                        'available' => $qual->available ?? null,
                        'pivot' => [
                            'craft_id' => $pivot?->craft_id ?? null,
                        ],
                    ];
                }
                return ['id' => $qual];
            }, $shiftQualifications);
        }

        return [
            'id'                   => $this->getAttribute('id'),
            'shifts'               => $shiftsArray,
            'assigned_craft_ids'   => $this->getAssignedCraftIdsAttribute(),
            'shift_ids'            => $shiftIds,
            'type'                 => $this->getTypeAttribute(),
            'shift_qualifications' => $shiftQualificationObjects,
            'managing_craft_ids'   => $this->getManagingCraftIds(),
        ];
    }

    final public function setStartDate(Carbon $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    final public function setEndDate(Carbon $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function setQualificationsCache(array $qualifications): self
    {
        $this->qualificationsCache = $qualifications;

        return $this;
    }

    /**
     * Lädt alle Schichten inkl. relevanter Pivot-/Event-/Raum-/Craft-Infos
     * für den Shift-Plan.
     */
    protected function loadDesiredShiftsForShiftPlan(): Collection
    {
        $shifts = $this->relationLoaded('shifts')
            ? $this->getRelation('shifts')
            : $this->getAttribute('shifts');

        if (!$shifts instanceof Collection) {
            return collect();
        }

        $startDateStr = $this->startDate->toDateString();
        $endDateStr = $this->endDate->toDateString();

        $filteredShifts = $shifts->filter(function (Shift $shift) use ($startDateStr, $endDateStr) {
            $eventStartDay = $shift->event_start_day ? Carbon::parse($shift->event_start_day)->toDateString() : null;
            $eventEndDay = $shift->event_end_day ? Carbon::parse($shift->event_end_day)->toDateString() : null;
            $shiftStartDate = $shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : null;
            $shiftEndDate = $shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : null;

            return ($eventStartDay && $eventStartDay >= $startDateStr && $eventStartDay <= $endDateStr)
                || ($eventEndDay && $eventEndDay >= $startDateStr && $eventEndDay <= $endDateStr)
                || ($shiftStartDate && $shiftStartDate >= $startDateStr && $shiftStartDate <= $endDateStr)
                || ($shiftEndDate && $shiftEndDate >= $startDateStr && $shiftEndDate <= $endDateStr);
        });

        return $filteredShifts->map(function (Shift $shift): array {
            $pivot      = $shift->pivot;              // belongsToMany-Pivot
            $event      = $shift->event;              // kann null sein
            $room       = $event?->room ?? $shift->room;
            $craft      = $shift->craft;
            $shiftGroup = $shift->shiftGroup;

            // N+1 fix, aus array cache laden
            $pivotQual = null;
            if ($pivot?->shift_qualification_id && $this->qualificationsCache !== null) {
                $pivotQual = $this->qualificationsCache[$pivot->shift_qualification_id] ?? null;
            } elseif ($pivot?->shift_qualification_id) {
                if ($pivot->relationLoaded('shiftQualification')) {
                    $pivotQual = $pivot->getRelation('shiftQualification');
                }
            }

            // Pivot-Zeiten
            $pivotStart = $pivot?->start_time;
            $pivotEnd   = $pivot?->end_time;

            $startPivotFormatted = $pivotStart instanceof Carbon
                ? $pivotStart->format('H:i')
                : ($pivotStart ? Carbon::parse($pivotStart)->format('H:i') : null);

            $endPivotFormatted = $pivotEnd instanceof Carbon
                ? $pivotEnd->format('H:i')
                : ($pivotEnd ? Carbon::parse($pivotEnd)->format('H:i') : null);

            $startDate = $shift->start_date;

            // Berechne days_of_shift direkt statt Accessor zu verwenden
            $daysOfShift = [];
            if ($shift->start_date && $shift->end_date) {
                $daysOfShift = collect(CarbonPeriod::create($shift->start_date, $shift->end_date))
                    ->map(fn($day) => $day->format('d.m.Y'))
                    ->all();
            }

            return [
                'id'                    => $shift->id,
                'pivotId'               => $pivot?->id,
                'startPivot'            => $startPivotFormatted,
                'endPivot'              => $endPivotFormatted,
                'shortDescription'      => $pivot?->short_description,
                'craftAbbreviationUser' => $pivot?->craft_abbreviation,
                'qualificationId'       => $pivot?->shift_qualification_id,
                'qualificationName'     => $pivotQual?->name,

                'start'                 => $shift->start,
                'end'                   => $shift->end,
                'description'           => $shift->description,

                'craftAbbreviation'     => $craft?->abbreviation,
                'days_of_shift'         => $daysOfShift,

                'start_of_shift'        => $startDate instanceof Carbon
                    ? $startDate->format('d.m.Y')
                    : ($startDate ? Carbon::parse((string) $startDate)->format('d.m.Y') : null),

                'roomName'              => $room?->name,
                'eventName'             => $event?->name ?? $event?->eventName,
                'eventTypeAbbreviation' => $event?->event_type?->abbreviation,

                'craft'                 => $craft ? $craft->only(['id', 'name', 'abbreviation']) : null,
                'isCommitted'           => $shift->is_committed,
                'shiftGroup'            => $shiftGroup ? $shiftGroup->only(['id', 'name']) : null,
            ];
        });
    }
}

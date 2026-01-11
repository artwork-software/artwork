<?php

namespace Artwork\Modules\Shift\Abstracts;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
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

        return [
            'id'                   => $this->getAttribute('id'),
            'shifts'               => $this->loadDesiredShiftsForShiftPlan(),
            'assigned_craft_ids'   => $this->getAssignedCraftIdsAttribute(),
            'shift_ids'            => $this->getShiftIdsBetweenStartDateAndEndDate($this->startDate, $this->endDate),
            'type'                 => $this->getTypeAttribute(),
            'shift_qualifications' => $this->getAttribute('shiftQualifications'),
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

    /**
     * Lädt alle Schichten inkl. relevanter Pivot-/Event-/Raum-/Craft-Infos
     * für den Shift-Plan.
     */
    protected function loadDesiredShiftsForShiftPlan(): Collection
    {
        /** @var Collection<int, Shift>|null $shifts */
        $shifts = $this->getAttribute('shifts');

        if (!$shifts instanceof Collection) {
            return collect();
        }

        return $shifts->map(static function (Shift $shift): array {
            $pivot      = $shift->pivot;              // belongsToMany-Pivot
            $event      = $shift->event;              // kann null sein
            $room       = $event?->room ?? $shift->room;
            $craft      = $shift->craft;
            $shiftGroup = $shift->shiftGroup;
            $pivotQual  = $pivot?->shiftQualification;

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
                'days_of_shift'         => $shift->days_of_shift,

                'start_of_shift'        => $startDate instanceof Carbon
                    ? $startDate->format('d.m.Y')
                    : ($startDate ? Carbon::parse((string) $startDate)->format('d.m.Y') : null),

                'roomName'              => $room?->name,
                'eventName'             => $event?->name ?? $event?->eventName,
                'eventTypeAbbreviation' => $event?->event_type?->abbreviation,

                'craft'                 => $craft,
                'isCommitted'           => $shift->is_committed,
                'shiftGroup'            => $shiftGroup,
            ];
        });
    }
}

<?php

namespace Artwork\Modules\Shift\Models\Traits;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Services\WorkingHourService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait HasShifts
{
    public function shifts(): MorphToMany
    {
        return $this->morphToMany(
            Shift::class,
            'employable',
            'shift_workers',
            'employable_id',
            'shift_id'
        )
            ->using(ShiftWorker::class)
            ->withPivot([
                'id',
                'shift_qualification_id',
                'shift_count',
                'craft_abbreviation',
                'short_description',
                'start_date',
                'end_date',
                'start_time',
                'end_time'
            ]);
    }

    public function shiftQualifications(): MorphToMany
    {
        return $this->morphToMany(
            ShiftQualification::class,
            'qualifiable',
            'shift_qualifiables',
            'qualifiable_id',
            'shift_qualification_id'
        )->withPivot('craft_id');
    }

    public function globalQualifications(): MorphToMany
    {
        return $this->morphToMany(
            GlobalQualification::class,
            'qualifiable',
            'global_qualifiables',
            'qualifiable_id',
            'global_qualification_id'
        );
    }

    public function getShiftIdsBetweenStartDateAndEndDate(
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        if ($this->relationLoaded('shifts')) {
            $startDateStr = $startDate->toDateString();
            $endDateStr = $endDate->toDateString();
            
            return $this->shifts->filter(function ($shift) use ($startDateStr, $endDateStr) {
                $eventStartDay = $shift->event_start_day ? Carbon::parse($shift->event_start_day)->toDateString() : null;
                $eventEndDay = $shift->event_end_day ? Carbon::parse($shift->event_end_day)->toDateString() : null;
                $shiftStartDate = $shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : null;
                $shiftEndDate = $shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : null;
                
                return ($eventStartDay && $eventStartDay >= $startDateStr && $eventStartDay <= $endDateStr)
                    || ($eventEndDay && $eventEndDay >= $startDateStr && $eventEndDay <= $endDateStr)
                    || ($shiftStartDate && $shiftStartDate >= $startDateStr && $shiftStartDate <= $endDateStr)
                    || ($shiftEndDate && $shiftEndDate >= $startDateStr && $shiftEndDate <= $endDateStr);
            })->pluck('id');
        }
        
        return $this->shifts()
            ->eventStartDayAndEventEndDayBetween($startDate, $endDate)
            ->pluck('shifts.id');
    }

    public function plannedWorkingHours(Carbon $startDate, Carbon $endDate): float|int
    {
        $workingHourService = app(WorkingHourService::class);
        return $workingHourService->convertMinutesInHours(
            $workingHourService->calculateShiftTime($this, $startDate, $endDate)
        );
    }

    public function scopeCanWorkShifts(Builder $builder): Builder
    {
        return $builder->where('can_work_shifts', true);
    }

    public function assignedCrafts(): MorphToMany
    {
        return $this->morphToMany(Craft::class, 'craftable')->with('qualifications');
    }

    public function managingCrafts(): MorphToMany
    {
        return $this->morphToMany(Craft::class, 'craft_manager');
    }

    public function getAssignedCraftIdsAttribute(): array
    {
        // Wenn assignedCrafts bereits geladen ist, verwende die Collection
        if ($this->relationLoaded('assignedCrafts')) {
            return $this->assignedCrafts->pluck('id')->toArray();
        }
        
        // Fallback: Query ausführen (für Kompatibilität)
        return $this->assignedCrafts()->pluck('crafts.id')->toArray();
    }

    public function craftsToManage(): MorphToMany
    {
        return $this->morphToMany(Craft::class, 'craft_manager');
    }

    public function getManagingCraftIds(): array
    {
        // Wenn managingCrafts bereits geladen ist, verwende die Collection
        if ($this->relationLoaded('managingCrafts')) {
            return $this->managingCrafts->pluck('id')->toArray();
        }
        
        // Fallback: Query ausführen (für Kompatibilität)
        return $this->craftsToManage()->pluck('id')->toArray();
    }
}

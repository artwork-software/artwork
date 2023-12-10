<?php

namespace Artwork\Modules\Vacation\Models;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

trait GoesOnVacation
{
    public function vacations(): MorphMany
    {
        return $this->morphMany(Vacation::class, 'vacationer');
    }

    public function hasVacationDays(): array
    {
        $vacations = $this->vacations()->get();
        $returnInterval = [];
        foreach ($vacations as $vacation) {
            $start = Carbon::parse($vacation->from);
            $end = Carbon::parse($vacation->until);

            $interval = CarbonPeriod::create($start, $end);

            foreach ($interval as $date) {
                $returnInterval[] = $date->format('Y-m-d');
            }
        }
        return $returnInterval;
    }
}

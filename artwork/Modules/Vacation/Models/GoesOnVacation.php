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
        $vacations = $this->vacations;
        return $vacations->map(fn(Vacation $vacation) => $vacation->date)->toArray();
    }
}

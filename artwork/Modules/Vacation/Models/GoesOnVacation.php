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

    /**
     * @return array<int, Carbon>
     */
    public function hasVacationDays(): array
    {
        return $this->vacations->map(fn(Vacation $vacation) => $vacation->date)->toArray();
    }
}

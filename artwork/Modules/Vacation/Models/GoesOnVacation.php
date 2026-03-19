<?php

namespace Artwork\Modules\Vacation\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

trait GoesOnVacation
{
    public function vacations(): MorphMany
    {
        return $this->morphMany(Vacation::class, 'vacationer');
    }

    public function getVacationDays(): array
    {
        return $this->vacations->map(fn(Vacation $vacation) => [
            'date' => Carbon::parse($vacation->date)->toDateString(),
            'type' => $vacation->type,
            'full_day' => $vacation->full_day,
            'start_time' => $vacation->start_time?->format('H:i'),
            'end_time' => $vacation->end_time?->format('H:i'),
            'created_by' => $vacation->created_by,
        ])->toArray();
    }
}

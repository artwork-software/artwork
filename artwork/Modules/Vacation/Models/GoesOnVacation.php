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
            'created_by' => $vacation->created_by,
        ])->toArray();
    }
}

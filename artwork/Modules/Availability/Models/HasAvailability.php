<?php

namespace Artwork\Modules\Availability\Models;

use Artwork\Modules\Vacation\Models\Vacation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAvailability
{
    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'available');
    }

    public function hasavailabilityDays(): array
    {
        $availabilities = $this->availabilities;
        return $availabilities->map(fn(Availability $availability) => $availability->date)->toArray();
    }
}

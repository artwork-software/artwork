<?php

namespace Artwork\Modules\DayService\Models\Traits;

use Artwork\Modules\DayService\Models\DayService;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait CanHasDayServices
{
    public function dayServices(): MorphToMany
    {
        return $this->morphToMany(
            DayService::class,
            'day_serviceable',
            'day_serviceables',
            'day_serviceable_id',
            'day_service_id'
        )->withTimestamps()->withPivot('date');
    }
}

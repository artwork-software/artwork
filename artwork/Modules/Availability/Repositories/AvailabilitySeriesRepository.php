<?php

namespace Artwork\Modules\Availability\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Availability\Models\AvailabilitySeries;

class AvailabilitySeriesRepository
{
    public function save(AvailabilitySeries $availabilitySeries): AvailabilitySeries|Model
    {
        $availabilitySeries->save();
        return $availabilitySeries;
    }
}

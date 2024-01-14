<?php

namespace Artwork\Modules\Vacation\Repository;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Vacation\Models\VacationSeries;

class VacationSeriesRepository
{
    public function save(VacationSeries $vacationSeries): VacationSeries|Model
    {
        $vacationSeries->save();
        return $vacationSeries;
    }
}

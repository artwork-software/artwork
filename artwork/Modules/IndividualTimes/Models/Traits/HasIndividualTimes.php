<?php

namespace Artwork\Modules\IndividualTimes\Models\Traits;

use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

trait HasIndividualTimes
{
    /**
     * Polymorphic relationship to the IndividualTime model.
     */
    public function individualTimes()
    {
        return $this->morphMany(IndividualTime::class, 'timeable');
    }
}

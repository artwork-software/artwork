<?php

namespace Artwork\Modules\IndividualTimes\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

trait HasIndividualTimes
{
    /**
     * Polymorphic relationship to the IndividualTime model.
     */
    public function individualTimes(): MorphMany
    {
        return $this->morphMany(IndividualTime::class, 'timeable');
    }
}

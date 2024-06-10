<?php

namespace Artwork\Modules\DayService\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface DayServiceable
{
    public function dayServices(): MorphToMany;
}

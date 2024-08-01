<?php

namespace Artwork\Modules\Availability\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Available
{
    public function availabilities(): MorphMany;
}

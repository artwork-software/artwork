<?php

namespace Artwork\Modules\Availability\Models;

interface Available
{
    public function availabilities(): \Illuminate\Database\Eloquent\Relations\MorphMany;
}

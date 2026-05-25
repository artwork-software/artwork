<?php

namespace Artwork\Modules\Change\Interfaces;

use Spatie\Activitylog\Contracts\Activity;

interface Builder
{
    public function build(): Activity;
}

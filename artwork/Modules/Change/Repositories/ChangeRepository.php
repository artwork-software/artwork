<?php

namespace Artwork\Modules\Change\Repositories;

use Spatie\Activitylog\Contracts\Activity;

readonly class ChangeRepository
{
    public function save(Activity $activity): Activity
    {
        $activity->save();

        return $activity;
    }
}

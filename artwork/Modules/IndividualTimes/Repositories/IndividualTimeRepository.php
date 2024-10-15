<?php

namespace Artwork\Modules\IndividualTimes\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Illuminate\Notifications\DatabaseNotification;

class IndividualTimeRepository extends BaseRepository
{
    public function createNewIndividualTime($model, array $attributes)
    {
        return $model->individualTimes()->create($attributes);
    }

}

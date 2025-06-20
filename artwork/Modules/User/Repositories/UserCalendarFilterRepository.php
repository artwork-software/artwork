<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class UserCalendarFilterRepository extends BaseRepository
{
    public function __construct(private readonly UserCalendarFilter $userCalendarFilter)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->userCalendarFilter->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->userCalendarFilter->newModelQuery();
    }

    public function getAll(): Collection
    {
        return $this->getNewModelQuery()->get();
    }
}

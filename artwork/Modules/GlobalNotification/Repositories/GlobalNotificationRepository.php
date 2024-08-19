<?php

namespace Artwork\Modules\GlobalNotification\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class GlobalNotificationRepository extends BaseRepository
{
    public function __construct(private readonly GlobalNotification $globalNotification)
    {
    }

    public function getNewModelInstance(array $attributes = []): GlobalNotification
    {
        return $this->globalNotification->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->globalNotification->newModelQuery();
    }

    public function getFirst(): ?GlobalNotification
    {
        /** @var GlobalNotification $globalNotification */
        $globalNotification = $this->getNewModelQuery()->first();

        return $globalNotification;
    }
}

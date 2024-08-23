<?php

namespace Artwork\Modules\DatabaseNotification\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Throwable;

class DatabaseNotificationRepository extends BaseRepository
{
    public function __construct(private readonly DatabaseNotification $databaseNotification)
    {
    }

    public function getNewModelInstance(): DatabaseNotification
    {
        return $this->databaseNotification->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->databaseNotification->newModelQuery();
    }

    /**
     * @throws Throwable
     */
    public function deleteByKey(string $notificationKey): bool
    {
        /**
         * @var DatabaseNotification $databaseNotification
         */
        $databaseNotification = $this->getNewModelQuery()->whereJsonContains(
            'data->notificationKey',
            $notificationKey
        )->first();

        return $this->deleteOrFail($databaseNotification);
    }
}

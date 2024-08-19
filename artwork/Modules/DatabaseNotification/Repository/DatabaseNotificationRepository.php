<?php

namespace Artwork\Modules\DatabaseNotification\Repository;

use Illuminate\Notifications\DatabaseNotification;
use Throwable;

class DatabaseNotificationRepository
{
    /**
     * @throws Throwable
     */
    public function updateOrFail(DatabaseNotification $databaseNotification, array $attributes): bool
    {
        return $databaseNotification->updateOrFail($attributes);
    }
}

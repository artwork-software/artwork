<?php

namespace Artwork\Modules\DatabaseNotification\Repositories;

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

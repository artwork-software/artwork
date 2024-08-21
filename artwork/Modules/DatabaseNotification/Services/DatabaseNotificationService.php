<?php

namespace Artwork\Modules\DatabaseNotification\Services;

use Artwork\Modules\DatabaseNotification\Repositories\DatabaseNotificationRepository;
use Illuminate\Notifications\DatabaseNotification;
use Throwable;

class DatabaseNotificationService
{
    public function __construct(
        private readonly DatabaseNotificationRepository $databaseNotificationRepository
    ) {
    }

    /**
     * @throws Throwable
     */
    public function updateSentInSummary(DatabaseNotification $databaseNotification, bool $sent): bool
    {
        return $this->databaseNotificationRepository->updateOrFail(
            $databaseNotification,
            [
                'sent_in_summary' => $sent
            ]
        );
    }
}

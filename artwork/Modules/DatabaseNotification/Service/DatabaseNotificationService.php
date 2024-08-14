<?php

namespace Artwork\Modules\DatabaseNotification\Service;

use Artwork\Modules\DatabaseNotification\Repository\DatabaseNotificationRepository;
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

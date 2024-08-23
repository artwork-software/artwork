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
    public function updateSentInSummary(DatabaseNotification $databaseNotification, bool $sent): DatabaseNotification
    {
        /**
         * @var DatabaseNotification $databaseNotification
         */
        $databaseNotification = $this->databaseNotificationRepository->updateOrFail(
            $databaseNotification,
            [
                'sent_in_summary' => $sent
            ]
        );

        return $databaseNotification;
    }

    /**
     * @throws Throwable
     */
    public function deleteByKey(string $notificationKey): bool
    {
        return $this->databaseNotificationRepository->deleteByKey($notificationKey);
    }
}

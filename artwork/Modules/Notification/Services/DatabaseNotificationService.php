<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Notification\Repositories\DatabaseNotificationRepository;
use Illuminate\Notifications\DatabaseNotification;
use Throwable;

class DatabaseNotificationService
{
    public function __construct(
        private readonly DatabaseNotificationRepository $databaseNotificationRepository,
        private readonly CarbonService $carbonService
    )
    {
    }

    public function find(string $id): ?DatabaseNotification
    {
        return $this->databaseNotificationRepository->find($id);
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

    public function removeNotificationsOlderThanSevenDays(): void
    {
        foreach (
            $this->databaseNotificationRepository->findOlderThan(
                $this->carbonService->getNow()->subDays(7)
            ) as $notification
        ) {
            $this->databaseNotificationRepository->forceDelete($notification);
        }
    }
}

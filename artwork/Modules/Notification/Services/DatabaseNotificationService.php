<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Notification\Repositories\DatabaseNotificationRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Throwable;

class DatabaseNotificationService
{
    /**
     * Buttons that mark a notification as "passive" / archivable. A notification may only be
     * archived (bulk or single) when ALL of its buttons are within this set – otherwise it still
     * requires an explicit user action (accept/decline/answer ...) and must stay unread.
     * Kept in sync with the frontend filter in NotificationSectionComponent / NotificationBlock.
     */
    public const ARCHIVABLE_BUTTONS = [
        'showInTasks',
        'show_project',
        'delete_shift_notification',
        'see_shift',
        'change_shift',
        'accept',
        'decline',
        'answerDialog',
        'answer',
        'change_request',
        'event_delete',
    ];

    public function __construct(
        private readonly DatabaseNotificationRepository $databaseNotificationRepository,
        private readonly CarbonService $carbonService
    )
    {
    }

    /**
     * Returns whether a notification's button payload allows it to be archived.
     */
    public function isArchivable(DatabaseNotification $notification): bool
    {
        $buttons = $notification->getAttribute('data')['buttons'] ?? [];

        return count(array_diff($buttons, self::ARCHIVABLE_BUTTONS)) === 0;
    }

    /**
     * Bulk-archives (sets read_at) all archivable unread notifications of a user, optionally
     * restricted to a single notification group. Works in id-cursor chunks with one UPDATE per
     * chunk so even tens of thousands of rows are processed without hydrating them all or holding
     * a long table lock. Returns the number of archived notifications.
     */
    public function archiveAllUnreadForUser(User $user, ?string $groupType = null, int $chunkSize = 1000): int
    {
        $now = $this->carbonService->getNow();
        $archived = 0;

        $query = $user->notifications()->whereNull('read_at');

        if ($groupType !== null) {
            $query->where('data->groupType', $groupType);
        }

        $query->select(['id', 'data'])
            ->chunkById($chunkSize, function ($chunk) use (&$archived, $now): void {
                $ids = $chunk
                    ->filter(fn (DatabaseNotification $notification): bool => $this->isArchivable($notification))
                    ->pluck('id');

                if ($ids->isEmpty()) {
                    return;
                }

                DatabaseNotification::query()->whereIn('id', $ids)->update(['read_at' => $now]);
                $archived += $ids->count();
            });

        return $archived;
    }

    /**
     * Hard-deletes all notifications of a user in id-cursor chunks (one DELETE per chunk),
     * optionally only the archived ones. Used for one-off remediation of users with extreme
     * notification counts. Returns the number of deleted rows.
     */
    public function deleteAllForUser(User $user, bool $onlyArchived = false, int $chunkSize = 2000): int
    {
        $deleted = 0;

        do {
            $query = $user->notifications();

            if ($onlyArchived) {
                $query->whereNotNull('read_at');
            }

            $affected = $query->limit($chunkSize)->delete();
            $deleted += $affected;
        } while ($affected > 0);

        return $deleted;
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

    public function removeArchivedNotificationsOlderThanThirtyDays(): void
    {
        $cutoff = $this->carbonService->getNow()->subDays(30);

        do {
            $deleted = $this->databaseNotificationRepository->deleteArchivedOlderThan($cutoff);
        } while ($deleted > 0);
    }

    public function removeUnreadNotificationsOlderThanOneYear(): void
    {
        $cutoff = $this->carbonService->getNow()->subYear();

        do {
            $deleted = $this->databaseNotificationRepository->deleteUnreadOlderThan($cutoff);
        } while ($deleted > 0);
    }
}

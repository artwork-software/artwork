<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * One-off remediation for users with an extreme number of notifications (e.g. after a bulk event
 * deletion created tens of thousands of rows). Archives or deletes a single user's notifications in
 * chunked bulk operations so the database is not blocked.
 *
 * Examples:
 *   php artisan artwork:purge-user-notifications 42 --mode=archive
 *   php artisan artwork:purge-user-notifications 42 --mode=delete --archived-only
 */
class PurgeUserNotificationsCommand extends Command
{
    protected $signature = 'artwork:purge-user-notifications
        {user : The id of the user whose notifications should be purged}
        {--mode=archive : "archive" (set read_at on archivable unread) or "delete" (hard delete)}
        {--archived-only : Only affect already archived (read) notifications – delete mode only}';

    protected $description = 'Archives or deletes all notifications of a single user in chunked bulk operations';

    public function handle(DatabaseNotificationService $databaseNotificationService): int
    {
        $user = User::find((int) $this->argument('user'));

        if ($user === null) {
            $this->error('User not found.');

            return CommandAlias::FAILURE;
        }

        $mode = (string) $this->option('mode');

        if ($mode === 'delete') {
            $onlyArchived = (bool) $this->option('archived-only');
            $deleted = $databaseNotificationService->deleteAllForUser($user, $onlyArchived);
            $this->info(sprintf('Deleted %d notifications for user #%d.', $deleted, $user->id));

            return CommandAlias::SUCCESS;
        }

        if ($mode === 'archive') {
            $archived = $databaseNotificationService->archiveAllUnreadForUser($user);
            $this->info(sprintf('Archived %d notifications for user #%d.', $archived, $user->id));

            return CommandAlias::SUCCESS;
        }

        $this->error('Invalid --mode. Use "archive" or "delete".');

        return CommandAlias::FAILURE;
    }
}

<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Notification\Models\GlobalNotification;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DeleteExpiredNotificationsForAllCommand extends Command
{
    protected $signature = 'artwork:delete-expired-notifications-for-all';

    protected $description = 'This command deletes the expired Notification for all';

    public function handle(): int
    {
        $notificationForAll = GlobalNotification::all();
        foreach ($notificationForAll as $notification) {
            if ($notification->expiration_date <= now()) {
                $notification->delete();
            }
        }

        return CommandAlias::SUCCESS;
    }
}

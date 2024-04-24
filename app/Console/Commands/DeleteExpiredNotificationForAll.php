<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DeleteExpiredNotificationForAll extends Command
{
    protected $signature = 'app:notification-for-all-delete';

    protected $description = 'This command deletes the expired Notification for all';

    public function handle(SchedulingController $schedulingController): int
    {
        $schedulingController->deleteExpiredNotificationForAll();

        return CommandAlias::SUCCESS;
    }
}

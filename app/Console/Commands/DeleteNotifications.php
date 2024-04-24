<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DeleteNotifications extends Command
{
    protected $signature = 'app:delete-notification';

    protected $description = 'Deletes notifications that were archived 7 or more days ago';

    public function handle(SchedulingController $schedulingController): int
    {
        $schedulingController->deleteOldNotifications();

        return CommandAlias::SUCCESS;
    }
}

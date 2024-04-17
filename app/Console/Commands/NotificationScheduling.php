<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class NotificationScheduling extends Command
{
    protected $signature = 'app:schedule-notification';

    protected $description = 'Send User Task Notification if task older than 30 minutes';

    public function handle(SchedulingController $schedulingController): int
    {
        $schedulingController->sendNotification();

        return CommandAlias::SUCCESS;
    }
}

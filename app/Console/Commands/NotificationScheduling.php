<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;

class NotificationScheduling extends Command
{
    protected $signature = 'app:schedule-notification';

    protected $description = 'Send User Task Notification if task older than 30 minutes';

    public function handle(): int
    {
        $taskScheduling = new SchedulingController();
        $taskScheduling->sendNotification();
        return 1;
    }
}

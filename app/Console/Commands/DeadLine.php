<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DeadLine extends Command
{
    protected $signature = 'app:send-deadline-notification';

    protected $description = 'Create Deadline Notification';

    /**
     * @throws Exception
     */
    public function handle(SchedulingController $schedulingController): int
    {
        $schedulingController->sendDeadlineNotification();

        return CommandAlias::SUCCESS;
    }
}

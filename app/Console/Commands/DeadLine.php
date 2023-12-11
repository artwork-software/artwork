<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;

class DeadLine extends Command
{
    protected $signature = 'app:send-deadline-notification';

    protected $description = 'Create Deadline Notification';

    public function handle(): int
    {
        $scheduling = new SchedulingController();
        $scheduling->sendDeadlineNotification();
        return 1;
    }
}

<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;

class NotificationScheduling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send User Task Notification if task older than 30 minutes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $taskScheduling = new SchedulingController();
        $taskScheduling->sendNotification();
        return 1;
    }
}

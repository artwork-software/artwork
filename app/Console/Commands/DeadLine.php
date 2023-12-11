<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Exception;
use Illuminate\Console\Command;

class DeadLine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-deadline-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Deadline Notification';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        $scheduling = new SchedulingController();
        $scheduling->sendDeadlineNotification();
        return 1;
    }
}

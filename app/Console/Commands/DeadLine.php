<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;

class DeadLine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deadline:notification';

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
     */
    public function handle()
    {
        $scheduling = new SchedulingController();
        $scheduling->sendDeadlineNotification();
        return 1;
    }
}

<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;

class DeleteNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes notifications that were archived 7 or more days ago';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $taskScheduling = new SchedulingController();
        $taskScheduling->deleteOldNotifications();
        return 1;
    }
}

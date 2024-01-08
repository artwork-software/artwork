<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;

class DeleteNotifications extends Command
{
    protected $signature = 'app:delete-notification';

    protected $description = 'Deletes notifications that were archived 7 or more days ago';

    public function handle(): int
    {
        $taskScheduling = new SchedulingController();
        $taskScheduling->deleteOldNotifications();
        return 1;
    }
}

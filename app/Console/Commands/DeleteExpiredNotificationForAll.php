<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulingController;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DeleteExpiredNotificationForAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notification-for-all-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command deletes the expired Notification for all';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $scheduling = new SchedulingController();
        $scheduling->deleteExpiredNotificationForAll();
        return CommandAlias::SUCCESS;
    }
}

<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SendScheduledNotificationsCommand extends Command
{
    protected $signature = 'artwork:send-scheduled-notifications';

    protected $description = 'Send User Task Notification if task older than 30 minutes';

    public function handle(
        SchedulingService $schedulingService,
        NotificationService $notificationService,
        ProjectTabService $projectTabService
    ): int {
        $schedulingService->sendNotification($notificationService, $projectTabService);

        return CommandAlias::SUCCESS;
    }
}

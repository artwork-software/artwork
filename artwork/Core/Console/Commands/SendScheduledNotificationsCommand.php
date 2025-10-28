<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SendScheduledNotificationsCommand extends Command
{
    protected $signature = 'artwork:send-scheduled-notifications';

    protected $description = 'Send User Task Notification if task older than 30 minutes';

    public function handle(
        SchedulingService $schedulingService,
        NotificationService $notificationService,
        ProjectTabService $projectTabService,
        LoggerInterface $logger
    ): int {
        try {
            $schedulingService->sendNotification($notificationService, $projectTabService);
        } catch (\Throwable $e) {
            // Log and report the exception, but prevent the scheduler from failing the whole run
            $logger->error('[artwork:send-scheduled-notifications] Unhandled exception', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            // Keep exit code SUCCESS to avoid failing schedule:run
            return CommandAlias::SUCCESS;
        }

        return CommandAlias::SUCCESS;
    }
}

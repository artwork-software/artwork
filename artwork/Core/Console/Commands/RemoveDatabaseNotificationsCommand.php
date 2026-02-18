<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Throwable;

class RemoveDatabaseNotificationsCommand extends Command
{
    protected $signature = 'artwork:remove-archived-database-notifications';

    protected $description = 'This command forceDeletes archived notifications older than 30 days and unread notifications older than 1 year';

    public function __construct(
        private readonly DatabaseNotificationService $databaseNotificationService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->databaseNotificationService->removeArchivedNotificationsOlderThanThirtyDays();
            $this->databaseNotificationService->removeUnreadNotificationsOlderThanOneYear();
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());
            $this->error($t->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Throwable;

class RemoveDatabaseNotificationsCommand extends Command
{
    protected $signature = 'artwork:remove-archived-database-notifications';

    protected $description = 'This command forceDeletes all DatabaseNotifications which are older than 7 days';

    public function __construct(
        private readonly DatabaseNotificationService $databaseNotificationService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->databaseNotificationService->removeNotificationsOlderThanSevenDays();
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());
            $this->error($t->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

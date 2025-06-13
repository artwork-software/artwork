<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Event\Services\EventPropertyService;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Services\UserCalendarFilterService;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Throwable;

class UpdateUserCalendarFilterEventPropertiesCommand extends Command
{
    protected $description = 'Update user calendar filter event properties, all filters activated by default';

    protected $signature = 'artwork:update-user-calendar-filter-event-properties';

    public function __construct(
        private readonly EventPropertyService $eventPropertyService,
        private readonly UserCalendarFilterService $userCalendarFilterService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        try {
            if (
                !$this->confirm(
                    sprintf(
                        'You are updating each existing "%s" "event_properties"-column to default values! Continue?',
                        UserCalendarFilter::class
                    )
                )
            ) {
                $this->info('Abort!');
                return self::SUCCESS;
            }

            $this->info(sprintf(
                'Updating "%s" "event_properties" to default values!',
                UserCalendarFilter::class
            ));
            $this->userCalendarFilterService->updateEventPropertiesOfAll(
                $this->eventPropertyService->getIdsOfAllExisting()->all()
            );

            $this->info('Success');
            return self::SUCCESS;
        } catch (Throwable $t) {
            $this->logger->error(self::class . ' failed for reason: ' . $t->getMessage());

            throw $t;
        }
    }
}

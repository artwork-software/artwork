<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventPropertyService;
use Illuminate\Console\Command;
use Throwable;

class MigrateEventsTableAudienceAndIsLoudToEventEventPropertiesTable extends Command
{
    protected $description =
        'Creates new event_event_properties-table entries based on audience and is_loud of events-table';

    protected $signature = 'migrate:events_table-audience-and-is_loud-to-event_event_properties_table';

    public function __construct(
        private readonly EventService $eventService,
        private readonly EventPropertyService $eventPropertyService
    ) {
        parent::__construct();
    }

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        try {
            $audienceEventProperty = $this->eventPropertyService->findOrFailById(1);
            $loudEventProperty = $this->eventPropertyService->findOrFailById(2);
        } catch (Throwable $t) {
            $this->info('Desired event properties could not be found. Seeded the database?');
            throw $t;
        }

        foreach ($this->eventService->getAll() as $event) {
            $isLoud = $event->getAttribute('is_loud');
            $hasAudience = $event->getAttribute('audience');

            if (!$isLoud && !$hasAudience) {
                $this->setEventAudienceAndIsLoudNull($event);
                continue;
            }

            $this->info(
                sprintf(
                    'Processing: "Audience" - "%s" | Loud" - "%s" - EventID "%d"',
                    $event->getAttribute('audience') > 0 ? 'Y' : 'N',
                    $event->getAttribute('is_loud') > 0 ? 'Y' : 'N',
                    $event->getAttribute('id'),
                )
            );

            if ($isLoud) {
                $this->eventService->attachEventProperty($event, $loudEventProperty);
            }

            if ($hasAudience) {
                $this->eventService->attachEventProperty($event, $audienceEventProperty);
            }

            $this->setEventAudienceAndIsLoudNull($event);
        }

        $this->info('Done. If there is no output there was no event to migrate!');

        return self::SUCCESS;
    }

    private function setEventAudienceAndIsLoudNull(Event $event): void
    {
        $this->eventService->update(
            $event,
            [
                'is_loud' => null,
                'audience' => null
            ]
        );
    }
}

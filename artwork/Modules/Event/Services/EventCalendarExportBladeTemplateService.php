<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Log\Logger;
use Illuminate\Translation\Translator;

class EventCalendarExportBladeTemplateService
{
    private bool $desiresTimespanExport;

    private string $createdBy;

    private array $rooms;

    private Collection $events;

    private ?array $projects;

    private ?string $dateStart;

    private ?string $dateEnd;

    public function __construct(
        private readonly Logger $logger,
        private readonly CarbonService $carbonService,
        private readonly Translator $translator,
    ) {
    }

    public function initialize(
        bool $desiresTimespanExport,
        string $createdBy,
        array $rooms,
        Collection $events,
        ?array $projects,
        ?string $dateStart,
        ?string $dateEnd,
    ): self {
        $this->logger->info('Initialize "' . self::class . '":');

        $this->desiresTimespanExport = $desiresTimespanExport;
        $this->createdBy = $createdBy;
        $this->rooms = $rooms;
        $this->events = $events;
        $this->projects = $projects;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function render(): void
    {
        $this->logger->info('-> Render...');
        [$desiredLocale, $firstStartingEvent, $lastStartingEvent] = $this->setup();

        $this->logger->info('Create output:');

        if ($this->events->isEmpty()) {
            $this->logger->info('-> No Events given, aborting.');
            return;
        }

        $output = '<table>';
        $output .= $this->renderDateAndCreator(
            $desiredLocale,
            $firstStartingEvent,
            $lastStartingEvent
        );
        $output .= $this->createDateHeadingAndRooms();
        $output .= $this->createTableBody(
            $desiredLocale,
            $firstStartingEvent?->getAttribute('start_time'),
            $lastStartingEvent?->getAttribute('end_time')
        );
        $output .= '</table>';

        $this->logger->info('Render output...');
        $this->logger->debug($output);

        echo $output;
    }

    /**
     * @return array<int, string|Event>
     */
    private function setup(): array
    {
        $this->logger->info('-> Render Setup...');

        $getSortByCallback = function (string $attribute) {
            return function (Event $event) use ($attribute) {
                return $event->getAttribute($attribute)->unix();
            };
        };

        if (!$this->desiresTimespanExport) {
            $firstStartingEvent = $this->events->sortBy(
                $getSortByCallback('start_time'),
                SORT_NUMERIC
            )->first();

            $lastStartingEvent = $this->events->sortBy(
                $getSortByCallback('end_time'),
                SORT_NUMERIC,
                true
            )->first();
        }

        return [
            $this->translator->getLocale(),
            $firstStartingEvent ?? null,
            $lastStartingEvent ?? null,
        ];
    }

    private function renderDateAndCreator(
        string $desiredLocale,
        //event dates are only given if $this->desiresTimespanExport === false
        ?Event $firstStartingEvent,
        ?Event $lastStartingEvent,
    ): string {
        $this->logger->info('-> Create date and creator row...');
        $desiredFormat = $this->carbonService->getDesiredDateFormatFromLocale($desiredLocale);

        //created by column in first row, saved for later
        $createdBy = sprintf(
            '<th colspan="3" height="20" class="text-2xl text-center">%s</th>',
            $this->translator->get(
                'export.excel-event-calendar-export.created-by',
                [
                    $this->createdBy,
                    $this->carbonService->formatFromString(
                        $this->carbonService->getNow(),
                        $this->carbonService->appendTimeToDateFormat($desiredFormat)
                    ),
                ]
            )
        );

        if ($this->desiresTimespanExport) {
            //date column in first row by dates given from ui
            return sprintf(
                '<tr><th colspan="3" height="20" class="text-2xl">%s (%s) - %s (%s)</th>%s</tr>',
                $this->getTranslatedMonthFrom($this->dateStart),
                $this->carbonService->formatFromString($this->dateStart, $desiredFormat),
                $this->getTranslatedMonthFrom($this->dateEnd),
                $this->carbonService->formatFromString($this->dateEnd, $desiredFormat),
                $createdBy
            );
        }

        //date column in first row by dates depending on first and last event
        $dateStart = $firstStartingEvent->getAttribute('start_time');
        $dateEnd = $lastStartingEvent->getAttribute('end_time');

        return sprintf(
            '<tr><th colspan="3" height="20" class="text-2xl">%s (%s) - %s (%s)</th>%s - %s</tr>',
            $this->getTranslatedMonthFrom($dateStart),
            $this->carbonService->formatFromString($dateStart, $desiredFormat),
            $this->getTranslatedMonthFrom($dateEnd),
            $this->carbonService->formatFromString($dateEnd, $desiredFormat),
            $createdBy,
            implode(', ', $this->projects)
        );
    }

    private function createDateHeadingAndRooms(): string
    {
        $this->logger->info('-> Create date and room header...');

        $roomMarkup = '';
        /** @var  $room */
        foreach ($this->rooms as $room) {
            $roomMarkup .= sprintf(
                '<td colspan="2" style="text-align:center; border: 1px solid black;">%s</td>',
                $room
            );
        }

        return sprintf(
            '<tr><td style="text-align: right; border: 1px solid black;">%s</td>%s</tr>',
            $this->translator->get('export.date-heading'),
            $roomMarkup
        );
    }

    private function createTableBody(
        string $desiredLocale,
        ?Carbon $firstEventStartDate,
        ?Carbon $lastEventStartDate,
    ): string {
        $this->logger->info('-> Create table body...');
        $period = $this->carbonService->createPeriodOf(
            $this->desiresTimespanExport ?
                $this->carbonService->create($this->dateStart) :
                $firstEventStartDate,
            $this->desiresTimespanExport ?
                $this->carbonService->create($this->dateEnd) :
                $lastEventStartDate
        );
        $this->logger->info(sprintf('--> Used date period: "%s" - "%s"', $period->first(), $period->last()));

        $tableBody = '';
        /** @var Carbon $date */
        foreach ($period as $date) {
            $this->logger->info(strtolower($date->format('M')));

            $roomAndEventsForDateMarkup = '';
            //iterate each room and save for later
            foreach ($this->rooms as $room) {
                //find events of given date and room



                //event name with color -> check where to get from -> time of event <td></td><td></td>
                $roomAndEventsForDateMarkup .= sprintf(
                    '<td></td><td></td>'
                );
                //event infos <td>infos</td> -> <td></td> <- is empty (saving space in file)
                $roomAndEventsForDateMarkup .= sprintf(
                    '<td></td><td></td>'
                );
            }

            $tableBody .= sprintf(
                '<tr><td style="border-left:3px solid #000000;">*%s, %s</td>%s</tr>',
                //translate
                $this->translator->get('export.shortMonths.' . strtolower($date->format('M'))),
                $date->format(
                    $desiredLocale === 'de' ?
                        CarbonService::GERMAN_DATE_FORMAT :
                        CarbonService::INTERNATIONAL_DATE_FORMAT
                ),
                $roomAndEventsForDateMarkup
            );




        }

        return $tableBody;
    }

    private function getTranslatedMonthFrom(string $date): string
    {
        return $this->translator->get(
            'export.months.' . strtolower($this->carbonService->formatFromString($date, 'F'))
        );
    }
}

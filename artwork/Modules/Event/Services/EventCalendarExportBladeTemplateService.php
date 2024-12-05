<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Log\Logger;
use Illuminate\Translation\Translator;

class EventCalendarExportBladeTemplateService
{
    private bool $desiresTimespanExport;

    private string $createdBy;

    /**
     * @var Collection<Room>
     */
    private Collection $rooms;

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

    public function render(
        bool $desiresTimespanExport,
        string $createdBy,
        Collection $rooms,
        Collection $events,
        ?array $projects,
        ?string $dateStart,
        ?string $dateEnd,
    ): void {
        $this->logger->debug(sprintf('Initialize %s"', self::class));

        $this->desiresTimespanExport = $desiresTimespanExport;
        $this->createdBy = $createdBy;
        $this->rooms = $rooms;
        $this->events = $events;
        $this->projects = $projects;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;

        $this->logger->debug(sprintf('-> Initialized %s"', self::class));

        [
            $desiredLocale,
            $firstStartingEvent,
            $lastStartingEvent
        ] = $this->setup();

        $output = '<table>';
        $output .= $this->renderDateAndCreator(
            $desiredLocale,
            $firstStartingEvent,
            $lastStartingEvent
        );
        $output .= $this->createDateHeadingAndRooms();

        if ($this->events->isEmpty()) {
            $this->logger->debug('-> No Events given, echo and return.');
            echo $output . '</table>';

            return;
        }

        $output .= $this->createTableBody(
            $desiredLocale,
            $firstStartingEvent?->getAttribute('start_time'),
            $lastStartingEvent?->getAttribute('end_time')
        );
        $output .= '</table>';

        $this->logger->debug('Echo output...');
        //$this->logger->debug($output);

        echo $output;
    }

    /**
     * @return array<int, string|Event>
     */
    private function setup(): array
    {
        $this->logger->debug('-> Render Setup...');

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
        ?Event $firstStartingEvent,
        ?Event $lastStartingEvent,
    ): string {
        $this->logger->debug('-> Create date and creator row...');
        $desiredFormat = $this->carbonService->getDesiredDateFormatFromLocale($desiredLocale);
        $dateStart = $this->desiresTimespanExport ?
            $this->dateStart :
            $firstStartingEvent->getAttribute('start_time');
        $dateEnd = $this->desiresTimespanExport ?
            $this->dateEnd :
            $lastStartingEvent->getAttribute('end_time');

        return sprintf(
            '<tr><th colspan="3" height="20" class="text-2xl">%s (%s) - %s (%s)</th>%s</tr>',
            $this->getTranslatedMonthFrom($dateStart),
            $this->carbonService->formatFromString($dateStart, $desiredFormat),
            $this->getTranslatedMonthFrom($dateEnd),
            $this->carbonService->formatFromString($dateEnd, $desiredFormat),
            sprintf(
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
            )
        );
    }

    private function createDateHeadingAndRooms(): string
    {
        $this->logger->debug('-> Create date and room header...');

        $markup = '';
        foreach ($this->rooms as $room) {
            $markup .= sprintf(
                '<td colspan="2" style="text-align:center; border: 1px solid black;">%s</td>',
                $room->getAttribute('name')
            );
        }

        return sprintf(
            '<tr><td style="text-align: right; border: 1px solid black;">%s</td>%s</tr>',
            $this->translator->get('export.date-heading'),
            $markup
        );
    }

    private function createTableBody(
        string $desiredLocale,
        ?Carbon $firstEventStartDate,
        ?Carbon $lastEventStartDate,
    ): string {
        $this->logger->debug('-> Create table body...');
        $period = $this->carbonService->createPeriodOf(
            $this->desiresTimespanExport ?
                $this->carbonService->create($this->dateStart) :
                $firstEventStartDate,
            $this->desiresTimespanExport ?
                $this->carbonService->create($this->dateEnd) :
                $lastEventStartDate,
        );

        $desiredDateFormat = $this->carbonService->getDesiredDateFormatFromLocale($desiredLocale);
        $this->logger->debug(
            sprintf(
                '-> Used date period: "%s" - "%s"',
                $period->first()->format($desiredDateFormat),
                $period->last()->format($desiredDateFormat)
            )
        );

        return $this->handleRoomsAndEvents($period, $desiredLocale);
    }

    /**
     * @return string
     */
    private function handleRoomsAndEvents(CarbonPeriod $period, string $desiredLocale): string
    {
        $markup = '';

        foreach ($period as $date) {
            $biggestEventCountInRoomsOfDate = 0;
            $eventsForRoomsOnDate = $this->findEventsForRoomsOnDate($date);

            foreach ($eventsForRoomsOnDate as $eventsForRoomOnDate) {
                $eventCount = count($eventsForRoomOnDate);
                if ($eventCount > $biggestEventCountInRoomsOfDate) {
                    $biggestEventCountInRoomsOfDate = $eventCount;
                }
            }
            $this->logger->debug('-> Process row...');

            if ($biggestEventCountInRoomsOfDate === 0) {
                //empty row for given date
                $markup .= '<tr>' . $this->createDateColumn($date, $desiredLocale);
                foreach ($this->rooms as $room) {
                    $markup .= '<td style="border-top:1px solid #000000;"></td>' .
                        '<td style="border-top: 1px solid #000000; border-right:1px solid #000000;"></td>';
                }
                $markup .= '</tr>';
                $markup .= '<tr>' . $this->createEmptyDateColumn();

                foreach ($this->rooms as $room) {
                    $markup .= '<td style="border-bottom:1px solid #000000;"></td>' .
                        '<td style="border-bottom:1px solid #000000; border-right:1px solid #000000;"></td>';
                }
                $markup .= '</tr>';

                $this->logger->debug(
                    sprintf(
                        '--> Created empty row for: %s',
                        $date->format($this->carbonService->getDesiredDateFormatFromLocale($desiredLocale))
                    )
                );
                continue;
            }

            $markup .= $this->handleRowsOfDay(
                $biggestEventCountInRoomsOfDate,
                $date,
                $desiredLocale,
                $eventsForRoomsOnDate
            );
        }

        return $markup;
    }

    /**
     * @param Carbon $date
     * @return array<int, array<int, Event>>
     */
    private function findEventsForRoomsOnDate(Carbon $date): array
    {
        $eventsForRoomsOnDate = [];

        foreach ($this->rooms as $room) {
            $roomId = $room->getAttribute('id');
            $eventsForRoomsOnDate[$roomId] = $this->findEventsOfRoomOnDate($roomId, $date)->all();
        }

        return $eventsForRoomsOnDate;
    }

    private function findEventsOfRoomOnDate(
        int $roomId,
        Carbon $date,
    ): Collection {
        return $this->events
            ->filter(
                function (Event $event) use ($roomId, $date): bool {
                    $eventRoomId = $event->getAttribute('room_id');

                    return $eventRoomId === $roomId &&
                        $this->carbonService->isInBetween(
                            $event->getAttribute('start_time'),
                            $event->getAttribute('end_time'),
                            $date,
                        );
                }
            )
            ->values()
            ->sortBy(
                function (Event $event): Carbon {
                    return $event->getAttribute('start_time');
                }
            );
    }

    private function handleRowsOfDay(
        int $biggestEventCountInRoomsOfDate,
        Carbon $date,
        string $desiredLocale,
        array $eventsForRoomsOnDate
    ): string {
        $roomEventIndicesOfDay = [];
        $markup = '';

        foreach (range(1, ($biggestEventCountInRoomsOfDate * 2)) as $rowOfDay) {
            $markup .= '<tr>';
            $this->logger->debug(
                sprintf(
                    '--> Create row number: %s for day %s',
                    $rowOfDay,
                    $date->format($this->carbonService->getDesiredDateFormatFromLocale($desiredLocale))
                )
            );

            $markup .= $rowOfDay === 1 ?
                $this->createDateColumn($date, $desiredLocale) :
                $this->createEmptyDateColumn();

            foreach ($this->rooms as $room) {
                $roomId = $room->getAttribute('id');
                $this->logger->debug('Room id: ' . $roomId);
                if (!isset($roomEventIndicesOfDay[$roomId])) {
                    $roomEventIndicesOfDay[$roomId] = 0;
                }

                if (count($eventsForRoomsOnDate[$roomId]) === 0) {
                    $this->logger->debug('--> Skip because there are no events');
                    $markup .= '<td></td><td></td>';
                    continue;
                }
                $this->logger->debug(sprintf('--> Event count: %d ', count($eventsForRoomsOnDate[$roomId])));

                $event = $eventsForRoomsOnDate[$roomId][$roomEventIndicesOfDay[$roomId]] ?? null;
                $hasEvent = $event instanceof Event;
                if ($rowOfDay % 2 === 0) {
                    if ($hasEvent) {
                        $this->logger->debug('--> Create event type, event status and description columns...');
                        $eventType = $event->getAttribute('event_type');
                        $eventStatus = $event->getAttribute('eventStatus');
                        $markup .= sprintf(
                            '<td style="border-bottom:1px solid #000000;">%s%s%s</td>' .
                            '<td style="border-bottom:1px solid #000000;"></td>',
                            $eventType->getAttribute('name') ?? '',
                            $eventStatus?->getAttribute('name') ?? '',
                            $event->getAttribute('description')
                        );
                    } else {
                        $this->logger->debug(
                            '--> Create empty event type, event status and description columns...'
                        );
                        $markup .= sprintf(
                            '%s%s',
                            '<td></td>',
                            '<td></td>'
                        );
                    }

                    $roomEventIndicesOfDay[$roomId]++;
                } elseif ($rowOfDay % 2 === 1) {
                    if ($hasEvent) {
                        $this->logger->debug('--> Create name and start columns...');
                        $eventType = $event->getAttribute('event_type');
                        $eventStatus = $event->getAttribute('eventStatus');
                        $eventNameBackgroundColorHexCode = $eventType?->getAttribute('hex_code') ??
                            $eventStatus?->getAttribute('color') ??
                            '#FFFFFF';
                        $markup .= sprintf(
                            '<td style="background-color: %s; border-top:1px solid #000000;">%s</td>' .
                            '<td style="border-top:1px solid #000000;">%s - %s</td>',
                            $eventNameBackgroundColorHexCode,
                            $event->getAttribute('name') ?? $event->getAttribute('eventName'),
                            $event->getAttribute('start_time')->format('H:i'),
                            $event->getAttribute('end_time')->format('H:i'),
                        );
                    } else {
                        $this->logger->debug('--> Create empty name and start columns...');
                        $markup .= sprintf(
                            '%s%s',
                            '<td></td>',
                            '<td></td>'
                        );
                    }
                }
            }
            $markup .= '</tr>';
        }

        return $markup;
    }

    private function createDateColumn($date, $desiredLocale): string
    {
        return sprintf(
            '<td style="border:1px solid #000000; border-bottom:none; font-weight: bold;">*%s, %s</td>',
            $this->translator->get('export.shortMonths.' . strtolower($date->format('M'))),
            $date->format($this->carbonService->getDesiredDateFormatFromLocale($desiredLocale))
        );
    }

    private function createEmptyDateColumn(): string
    {
        return '<td style="border:1px solid #000000; border-top:none;"></td>';
    }

    private function getTranslatedMonthFrom(string $date): string
    {
        return $this->translator->get(
            'export.months.' . strtolower($this->carbonService->formatFromString($date, 'F'))
        );
    }
}

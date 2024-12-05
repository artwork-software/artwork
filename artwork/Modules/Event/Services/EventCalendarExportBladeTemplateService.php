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

    public function initialize(
        bool $desiresTimespanExport,
        string $createdBy,
        Collection $rooms,
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
        //$this->logger->debug($output);

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
            $createdBy
        );
    }

    private function createDateHeadingAndRooms(): string
    {
        $this->logger->info('-> Create date and room header...');

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

    /**
     * @return array<int, mixed>
     */
    private function handleRoomsAndEvents(CarbonPeriod $period, string $desiredLocale): string
    {
        $tmp = '';

        //handle events on date for given rooms
        //keep in mind: row in xlsx (for given date) is made of 2 <tr> tags
        foreach ($period as $date) {
            $biggestEventCountInRoomsOfDate = 0;
            $eventsForRoomsOnDate = $this->findEventsForRoomsOnDate($date);

            foreach ($eventsForRoomsOnDate as $eventsForRoomOnDate) {
                $eventCount = count($eventsForRoomOnDate);
                if ($eventCount > $biggestEventCountInRoomsOfDate) {
                    $biggestEventCountInRoomsOfDate = $eventCount;
                }
            }

            $createDateColumnCallback = function () use ($date, $desiredLocale) {
                return sprintf(
                    '<td style="border:1px solid #000000; border-bottom:none; font-weight: bold;">*%s, %s</td>',
                    $this->translator->get('export.shortMonths.' . strtolower($date->format('M'))),
                    $date->format(
                        $desiredLocale === 'de' ?
                            CarbonService::GERMAN_DATE_FORMAT :
                            CarbonService::INTERNATIONAL_DATE_FORMAT
                    )
                );
            };
            $createEmptyDateColumnCallback = function () {
                return '<td style="border:1px solid #000000; border-top:none;"></td>';
            };
            $createEmptyTopRow = function () {
                return '<td style="border-top:1px solid #000000;"></td>' .
                    '<td style="border-top: 1px solid #000000; border-right:1px solid #000000;"></td>';
            };
            $createEmptyBottomRow = function () {
                return '<td style="border-bottom:1px solid #000000;"></td>' .
                    '<td style="border-bottom:1px solid #000000; border-right:1px solid #000000;"></td>';
            };

            if ($biggestEventCountInRoomsOfDate === 0) {
                //empty row for given date
                $tmp .= '<tr>' . $createDateColumnCallback();
                foreach ($this->rooms as $room) {
                    $tmp .= $createEmptyTopRow();
                }
                $tmp .= '</tr>';
                $tmp .= '<tr>' . $createEmptyDateColumnCallback();
                foreach ($this->rooms as $room) {
                    $tmp .= $createEmptyBottomRow();
                }
                $tmp .= '</tr>';

                continue; //day finished
            }

            $desiredRows = $biggestEventCountInRoomsOfDate * 2;
            foreach (range(1, $desiredRows) as $rowOfDay) {
                $this->logger->info('-> Create row of day: ' . $rowOfDay);
                $tmp .= '<tr>';
                //append date or empty column at the beginning of given rowOfDay
                if ($rowOfDay === 1) {
                    $tmp .= $createDateColumnCallback();
                } else {
                    $tmp .= $createEmptyDateColumnCallback();
                }

                $roomEventIndices = [];
                foreach ($this->rooms as $room) {
                    $roomId = $room->getAttribute('id');
                    if (!isset($roomEventIndices[$roomId])) {
                        $this->logger->info('-> Create room event indices for room (value 0) ' . $roomId);
                        $roomEventIndices[$roomId] = 0;
                    }
                    $this->logger->info('event count: ' . count($eventsForRoomsOnDate[$roomId]));
                    if (count($eventsForRoomsOnDate[$roomId]) === 0) {
                        $tmp .= '<td></td><td></td>';
                        continue;
                    }
                    $event = $eventsForRoomsOnDate[$roomId][$roomEventIndices[$roomId]] ?? null;
                    $hasEvent = $event instanceof Event;
                    if ($rowOfDay % 2 === 0) {
                        if ($hasEvent) {
                            $eventType = $event->getAttribute('event_type');
                            $eventStatus = $event->getAttribute('eventStatus');
                            $tmp .= sprintf(
                                '<td style="border-bottom:1px solid #000000;">%s%s%s</td>' .
                                '<td style="border-bottom:1px solid #000000;"></td>',
                                $eventType->getAttribute('name') ?? '',
                                $eventStatus?->getAttribute('name') ?? '',
                                $event->getAttribute('description')
                            );
                        } else {
                            $tmp .= sprintf(
                                '%s%s',
                                '<td></td>',
                                '<td></td>'
                            );
                        }
                    } else if ($rowOfDay % 2 === 1) {
                        if ($hasEvent) {
                            $eventType = $event->getAttribute('event_type');
                            $eventStatus = $event->getAttribute('eventStatus');
                            $eventNameBackgroundColorHexCode = $eventType?->getAttribute('hex_code') ??
                                $eventStatus?->getAttribute('color') ??
                                '#FFFFFF';
                            $tmp .= sprintf(
                                '<td style="background-color: %s; border-top:1px solid #000000;">%s</td>' .
                                '<td style="border-top:1px solid #000000;">%s - %s</td>',
                                $eventNameBackgroundColorHexCode,
                                $event->getAttribute('name') ?? $event->getAttribute('eventName'),
                                $event->getAttribute('start_time')->format('H:i'),
                                $event->getAttribute('end_time')->format('H:i'),
                            );
                        } else {
                            $tmp .= sprintf(
                                '%s%s',
                                '<td></td>',
                                '<td></td>'
                            );
                        }
                        $roomEventIndices[$roomId]++;
                    } else {
                        $tmp .= sprintf(
                            '%s%s',
                            '<td></td>',
                            '<td></td>'
                        );
                    }
                }

                $tmp .= '</tr>';
            }
        }

        return $tmp;
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
                $lastEventStartDate,
        );
        $this->logger->info(sprintf('--> Used date period: "%s" - "%s"', $period->first(), $period->last()));

        return $this->handleRoomsAndEvents($period, $desiredLocale);
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
            )->sortBy(
                function (Event $event): Carbon {
                    return $event->getAttribute('start_time');
                }
            );
    }

    private function getTranslatedMonthFrom(string $date): string
    {
        return $this->translator->get(
            'export.months.' . strtolower($this->carbonService->formatFromString($date, 'F'))
        );
    }
}

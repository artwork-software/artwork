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

    private ?Carbon $dateStart;

    private ?Carbon $dateEnd;

    public function __construct(
        private readonly CarbonService $carbonService,
        private readonly Translator $translator,
    ) {
    }

    public function render(
        bool $desiresTimespanExport,
        string $createdBy,
        Collection $rooms,
        Collection $events,
        Carbon $dateStart,
        Carbon $dateEnd,
        ?array $projects,
    ): void {

        $this->desiresTimespanExport = $desiresTimespanExport;
        $this->createdBy = $createdBy;
        $this->rooms = $rooms;
        $this->events = $events;
        $this->projects = $projects;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;

        $desiredLocale = $this->translator->getLocale();

        $output = '<table>';
        $output .= $this->renderDateAndCreator($desiredLocale);
        $output .= $this->createDateHeadingAndRooms();

        if ($this->events->isEmpty()) {
            echo $output . '</table>';

            return;
        }

        $output .= $this->createTableBody($desiredLocale);
        $output .= '</table>';

        echo $output;
    }

    private function renderDateAndCreator(string $desiredLocale): string
    {
        $desiredFormat = $this->carbonService->getDesiredDateFormatFromLocale($desiredLocale);

        return sprintf(
            '<tr>' .
                '<th colspan="3" height="20" style="width:230px; height:20px; font-size:18px;">%s (%s) - %s (%s)</th>' .
                '%s' .
                '<th>%s</th>' .
                '</tr>',
            $this->getTranslatedMonthFrom($this->dateStart),
            $this->dateStart->format($desiredFormat),
            $this->getTranslatedMonthFrom($this->dateEnd),
            $this->dateEnd->format($desiredFormat),
            sprintf(
                '<th colspan="3" height="20" style="width:160px; height:20px; font-size:18px;">%s</th>',
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
            ),
            !$this->desiresTimespanExport ? implode(', ', $this->projects) : ''
        );
    }

    private function createDateHeadingAndRooms(): string
    {
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

    private function createTableBody(string $desiredLocale): string
    {
        $period = $this->carbonService->createPeriodOf($this->dateStart, $this->dateEnd);

        $desiredDateFormat = $this->carbonService->getDesiredDateFormatFromLocale($desiredLocale);

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

            if ($biggestEventCountInRoomsOfDate === 0) {
                //empty row for given date
                $markup .= '<tr>' . $this->createDateColumn($date, $desiredLocale);
                foreach ($this->rooms as $room) {
                    $markup .= sprintf(
                        '<td style="%s"></td><td style="%s"></td>',
                        'width: 125px; border-top:1px solid #000000;',
                        'width: 125px; border-top: 1px solid #000000; border-right:1px solid #000000;'
                    );
                }
                $markup .= '</tr>';
                $markup .= '<tr>' . $this->createEmptyDateColumn();

                foreach ($this->rooms as $room) {
                    $markup .= sprintf(
                        '<td style="%s"></td><td style="%s"></td>',
                        'width: 125px; border-bottom:1px solid #000000;',
                        'width: 125px; border-bottom:1px solid #000000; border-right:1px solid #000000;'
                    );
                }
                $markup .= '</tr>';

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
                    return $event->getAttribute('room_id') === $roomId &&
                        $this->carbonService->compareAsStringsForSameDate(
                            $event->getAttribute('start_time'),
                            $event->getAttribute('end_time'),
                            $date
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

            $markup .= $rowOfDay === 1 ?
                $this->createDateColumn($date, $desiredLocale) :
                $this->createEmptyDateColumn();

            foreach ($this->rooms as $room) {
                $roomId = $room->getAttribute('id');
                if (!isset($roomEventIndicesOfDay[$roomId])) {
                    $roomEventIndicesOfDay[$roomId] = 0;
                }

                if (count($eventsForRoomsOnDate[$roomId]) === 0) {
                    $markup .= '<td style="width: 125px;"></td>' .
                        '<td style="width: 125px; border-right:1px solid #000000;"></td>';
                    continue;
                }

                $event = $eventsForRoomsOnDate[$roomId][$roomEventIndicesOfDay[$roomId]] ?? null;
                $hasEvent = $event instanceof Event;
                if ($rowOfDay % 2 === 0) {
                    if ($hasEvent) {
                        $markup .= sprintf(
                            '<td style="%s">%s %s %s</td><td style="%s"></td>',
                            'width: 125px; border-bottom:1px solid #000000; border-left:1px solid #000000;',
                            ($eventName = $event->getAttribute('name')) ? $eventName . ' | ' : '',
                            (
                                $eventStatusName = $event->getAttribute('eventStatus')?->getAttribute('name')
                            ) ? $eventStatusName : '',
                            ($description = $event->getAttribute('description')) ? ' | ' . $description : '',
                            'width: 125px; border-bottom:1px solid #000000; border-right:1px solid #000000;'
                        );
                    } else {
                        $markup .= sprintf(
                            '<td style="%s"></td><td style="%s"></td>',
                            'width: 125px; border-bottom:1px solid #000000; border-left:1px solid #000000;',
                            'width: 125px; border-bottom:1px solid #000000; border-right:1px solid #000000;'
                        );
                    }

                    $roomEventIndicesOfDay[$roomId]++;
                } elseif ($rowOfDay % 2 === 1) {
                    if ($hasEvent) {
                        $eventType = $event->getAttribute('event_type');

                        $eventNameBackgroundColorHexCode = $eventType?->getAttribute('hex_code') ?? '#FFFFFF';
                        [$r, $g, $b] = sscanf($eventNameBackgroundColorHexCode, "#%02x%02x%02x");
                        $fontColor = (($r + $g + $b) > ((255 + 255 + 255) / 2)) ? 'color: black;' : 'color: white;';
                        $markup .= sprintf(
                            '<td style="%s">%s</td>' .
                            '<td style="%s">%s - %s</td>',
                            sprintf(
                                '%s %s %s %s %s',
                                'width: 125px;',
                                sprintf('background-color: %s;', $eventNameBackgroundColorHexCode),
                                'border-top:1px solid #000000;',
                                'border-left:1px solid #000000;',
                                $fontColor
                            ),
                            $eventType->getAttribute('name'),
                            'width: 125px; border-top:1px solid #000000; border-right:1px solid #000000;',
                            $event->getAttribute('start_time')->format('H:i'),
                            $event->getAttribute('end_time')->format('H:i'),
                        );
                    } else {
                        $markup .= sprintf(
                            '<td style="%s"></td><td style="%s"></td>',
                            'width: 125px; border-top:1px solid #000000; border-left:1px solid #000000;',
                            'width: 125px; border-top:1px solid #000000; border-right:1px solid #000000;'
                        );
                    }
                }
            }
            $markup .= '</tr>';
        }

        return $markup;
    }

    private function createDateColumn(Carbon $date, string $desiredLocale): string
    {

        return sprintf(
            '<td style="border-left:1px solid #000000; border-right:1px solid #000000; font-weight: bold;">' .
                '%s%s, %s' . '</td>',
            $date->isWeekend() ? '*' : '',
            $this->translator->get('export.shortMonths.' . strtolower($date->format('M'))),
            $date->format($this->carbonService->getDesiredDateFormatFromLocale($desiredLocale))
        );
    }

    private function createEmptyDateColumn(): string
    {
        return '<td style="border-left:1px solid #000000; border-right:1px solid #000000;"></td>';
    }

    private function getTranslatedMonthFrom(Carbon $date): string
    {
        return $this->translator->get(
            'export.months.' . strtolower($date->format('F'))
        );
    }
}

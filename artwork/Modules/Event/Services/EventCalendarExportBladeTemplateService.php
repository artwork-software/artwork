<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Calendar\Services\EventExportDisplaySettings;
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

    private ?EventExportDisplaySettings $displaySettings = null;

    private bool $admissionEnabled = false;

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
        ?EventExportDisplaySettings $displaySettings = null,
    ): void {

        $this->desiresTimespanExport = $desiresTimespanExport;
        $this->createdBy = $createdBy;
        $this->rooms = $rooms;
        $this->events = $events;
        $this->projects = $projects;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->displaySettings = $displaySettings;
        $this->admissionEnabled = (bool) app(\App\Settings\EventSettings::class)->enable_admission;

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
            !$this->desiresTimespanExport ? e(implode(', ', $this->projects)) : ''
        );
    }

    private function createDateHeadingAndRooms(): string
    {
        $markup = '';
        foreach ($this->rooms as $room) {
            $markup .= sprintf(
                '<td colspan="2" style="text-align:center; border: 1px solid black;">%s</td>',
                e($room->getAttribute('name'))
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
                            '<td style="%s">%s</td><td style="%s"></td>',
                            'width: 125px; border-bottom:1px solid #000000; border-left:1px solid #000000;',
                            $this->composeEventDetailRow($event),
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

                        $eventNameBackgroundColorHexCode = $this->resolveEventBackgroundColor($event, $eventType);
                        [$r, $g, $b] = sscanf($eventNameBackgroundColorHexCode, "#%02x%02x%02x");
                        $fontColor = (($r + $g + $b) > ((255 + 255 + 255) / 2)) ? 'color: black;' : 'color: white;';
                        $markup .= sprintf(
                            '<td style="%s">%s</td>' .
                            '<td style="%s">%s - %s%s</td>',
                            sprintf(
                                '%s %s %s %s %s',
                                'width: 125px;',
                                sprintf('background-color: %s;', $eventNameBackgroundColorHexCode),
                                'border-top:1px solid #000000;',
                                'border-left:1px solid #000000;',
                                $fontColor
                            ),
                            e($eventType->getAttribute('name')) . ($event->project ? ' - ' . e($event->project->name) : ''),
                            'width: 125px; border-top:1px solid #000000; border-right:1px solid #000000;',
                            $event->getAttribute('start_time')->format('H:i'),
                            $event->getAttribute('end_time')->format('H:i'),
                            $this->composeAdmissionSuffix($event),
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

    /**
     * Detailzeile eines Termins gemäß Anzeigeeinstellungen (ohne Settings: Altverhalten
     * Terminname | Terminstatus | Beschreibung). Liefert bereits escaptes Markup.
     */
    private function composeEventDetailRow(Event $event): string
    {
        $display = $this->displaySettings;

        if ($display === null) {
            $parts = [];
            if ($eventName = $event->getAttribute('name')) {
                $parts[] = e($eventName);
            }
            if ($eventStatusName = $event->getAttribute('eventStatus')?->getAttribute('name')) {
                $parts[] = e($eventStatusName);
            }
            if ($description = $event->getAttribute('description')) {
                $parts[] = e($description);
            }

            return implode(' | ', $parts);
        }

        $project = $event->getAttribute('project');
        $artists = $project?->getAttribute('artists');
        $primaryName = $display->resolveEventName($event->getAttribute('name'), $artists);

        $parts = [];
        if ($primaryName) {
            $parts[] = e($primaryName);
        }
        if ($display->shows('project_artists') && $artists && $artists !== $primaryName) {
            $parts[] = e($artists);
        }
        if (
            $display->shows('show_event_status')
            && ($eventStatusName = $event->getAttribute('eventStatus')?->getAttribute('name'))
        ) {
            $parts[] = e($eventStatusName);
        }
        if ($display->shows('description') && ($description = $event->getAttribute('description'))) {
            $parts[] = e($description);
        }
        if (
            $display->shows('project_status')
            && ($projectStatusName = $project?->getAttribute('status')?->getAttribute('name'))
        ) {
            $parts[] = e($projectStatusName);
        }
        if ($display->shows('project_management') && $project) {
            $leaders = $project->getAttribute('managerUsers');
            if ($leaders && $leaders->isNotEmpty()) {
                $parts[] = e(
                    $leaders
                        ->map(static fn ($leader) => trim($leader->first_name . ' ' . $leader->last_name))
                        ->implode(', ')
                );
            }
        }
        if ($display->shows('show_event_creator') && ($creator = $event->getAttribute('creator'))) {
            $parts[] = e(trim($creator->first_name . ' ' . $creator->last_name));
        }

        return implode(' | ', $parts);
    }

    /**
     * Zellenfarbe gemäß Anzeigeeinstellung (Terminart / Terminstatus / Hauptkategorie);
     * ohne Settings wie bisher Terminart-Farbe.
     */
    private function resolveEventBackgroundColor(Event $event, ?object $eventType): string
    {
        if ($this->displaySettings === null) {
            return $eventType?->getAttribute('hex_code') ?? '#FFFFFF';
        }

        $project = $event->getAttribute('project');

        return $this->displaySettings->resolveColor(
            $eventType,
            $event->getAttribute('eventStatus'),
            (bool) $project,
            $project?->getAttribute('categories')?->firstWhere('pivot.is_main', true)?->color,
            '#FFFFFF'
        );
    }

    private function composeAdmissionSuffix(Event $event): string
    {
        if (
            !$this->admissionEnabled
            || $this->displaySettings === null
            || !$this->displaySettings->shows('show_event_admission')
            || empty($event->getAttribute('admission_time'))
        ) {
            return '';
        }

        return ' | Einlass ' . substr((string) $event->getAttribute('admission_time'), 0, 5);
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

<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Aggregiert die Daten für den Spielplan-Übersichts-Export (Jahres-/Saisonübersicht):
 * X-Achse Monate (6 pro Seite), Y-Achse Tage 1-31, pro Tag und Projekt nur der
 * Projektname, bei mehreren passenden Terminen eines Projekts am selben Tag "(n)".
 */
class SeasonSchedulePdfBuilder
{
    private const MONTHS_PER_PAGE = 6;

    public function __construct(
        private readonly CalendarDataService $calendarDataService,
        private readonly EventCalendarService $eventCalendarService,
    ) {
    }

    /**
     * @param array{
     *     showHolidays: bool,
     *     showWeekNumbers: bool,
     *     highlightWeekends: bool,
     *     showColorDots: bool,
     *     showEventsWithoutProject: bool,
     *     showRoomAbbreviations: bool
     * } $options
     * @return array{pages: array<int, array<int, array<string, mixed>>>, monthCount: int}
     */
    public function build(
        Carbon $startDate,
        Carbon $endDate,
        UserFilter $filter,
        ?UserCalendarSettings $userCalendarSettings,
        array $options
    ): array {
        // Tagesgenauer Zeitraum: das Raster zeigt immer volle Monate, Tage außerhalb
        // des Zeitraums werden im Blade ausgegraut und ohne Inhalt gerendert.
        $globalStart = $startDate->copy()->startOfDay();
        $globalEnd = $endDate->copy()->endOfDay();

        $rooms = $this->calendarDataService->getFilteredRooms(
            $filter,
            $userCalendarSettings,
            $globalStart,
            $globalEnd,
        );

        // Settings bewusst nicht durchreichen: Planungstermine bleiben immer außen vor.
        $roomsWithEvents = $this->eventCalendarService->filterRoomsEventsForPdf(
            $rooms,
            $filter,
            $globalStart,
            $globalEnd,
            null
        );

        $roomAbbreviations = $this->buildRoomAbbreviations($rooms);
        $showRoomAbbreviations = $options['showRoomAbbreviations'] && $rooms->count() > 1;

        $cells = $this->aggregateCells(
            $roomsWithEvents,
            $globalStart,
            $globalEnd,
            $options['showEventsWithoutProject'],
            $showRoomAbbreviations,
            $roomAbbreviations
        );

        $holidayMap = $options['showHolidays']
            ? $this->buildHolidayMap($globalStart, $globalEnd)
            : [];

        $months = [];
        $cursor = $globalStart->copy()->startOfMonth();
        while ($cursor->lte($globalEnd)) {
            $months[] = $this->buildMonth($cursor, $cells, $holidayMap, $options, $globalStart, $globalEnd);
            $cursor->addMonth();
        }

        return [
            'pages' => array_chunk($months, self::MONTHS_PER_PAGE),
            'monthCount' => count($months),
        ];
    }

    /**
     * Verdichtet die Events zu Tageszellen: pro Tag und Projekt genau ein Eintrag mit
     * Terminanzahl. Mehrtagestermine zählen an jedem Tag ihrer Laufzeit.
     *
     * @return array<string, array<string, array<string, mixed>>> Y-m-d => entryKey => entry
     */
    private function aggregateCells(
        \Illuminate\Support\Collection $roomsWithEvents,
        Carbon $globalStart,
        Carbon $globalEnd,
        bool $showEventsWithoutProject,
        bool $showRoomAbbreviations,
        array $roomAbbreviations
    ): array {
        $cells = [];

        foreach ($roomsWithEvents as $room) {
            foreach ($room->events as $event) {
                $projectName = $event->project->name ?? null;

                if ($projectName !== null && $projectName !== '') {
                    $entryKey = 'p' . $event->project->id;
                    $name = $projectName;
                } elseif ($showEventsWithoutProject) {
                    $name = $event->eventName ?: ($event->eventType->name ?? null);
                    if ($name === null || $name === '') {
                        continue;
                    }
                    $entryKey = 'e' . mb_strtolower($name);
                } else {
                    continue;
                }

                foreach ($event->daysOfEvent as $dayDisplay) {
                    $day = Carbon::createFromFormat('d.m.Y', $dayDisplay)->startOfDay();
                    if ($day->lt($globalStart) || $day->gt($globalEnd)) {
                        continue;
                    }
                    $dateKey = $day->format('Y-m-d');

                    if (!isset($cells[$dateKey][$entryKey])) {
                        $cells[$dateKey][$entryKey] = [
                            'name' => $name,
                            'count' => 0,
                            'color' => $event->eventType->hex_code ?? null,
                            'rooms' => [],
                            'firstStart' => $event->start,
                        ];
                    }

                    $cells[$dateKey][$entryKey]['count']++;
                    if ($event->start < $cells[$dateKey][$entryKey]['firstStart']) {
                        $cells[$dateKey][$entryKey]['firstStart'] = $event->start;
                        $cells[$dateKey][$entryKey]['color'] = $event->eventType->hex_code ?? null;
                    }
                    if ($showRoomAbbreviations && isset($roomAbbreviations[$event->roomId])) {
                        $cells[$dateKey][$entryKey]['rooms'][$roomAbbreviations[$event->roomId]] = true;
                    }
                }
            }
        }

        foreach ($cells as &$dayEntries) {
            uasort($dayEntries, static function (array $a, array $b): int {
                return [$a['firstStart'], $a['name']] <=> [$b['firstStart'], $b['name']];
            });
            foreach ($dayEntries as &$entry) {
                $entry['rooms'] = array_keys($entry['rooms']);
            }
            unset($entry);
        }
        unset($dayEntries);

        return $cells;
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $cells
     * @param array<string, string> $holidayMap
     * @return array<string, mixed>
     */
    private function buildMonth(
        Carbon $monthStart,
        array $cells,
        array $holidayMap,
        array $options,
        Carbon $globalStart,
        Carbon $globalEnd
    ): array {
        $dayNames = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
        $daysInMonth = $monthStart->daysInMonth;
        $rangeStartDay = $globalStart->copy()->startOfDay();
        $rangeEndDay = $globalEnd->copy()->startOfDay();

        $days = [];
        for ($dayNumber = 1; $dayNumber <= 31; $dayNumber++) {
            if ($dayNumber > $daysInMonth) {
                $days[$dayNumber] = null;
                continue;
            }

            $day = $monthStart->copy()->setDay($dayNumber);

            // Tage vor dem Start-/nach dem Enddatum: Zeile bleibt (Tag + Wochentag
            // sichtbar), aber ausgegraut und ohne Inhalt
            if ($day->lt($rangeStartDay) || $day->gt($rangeEndDay)) {
                $days[$dayNumber] = [
                    'dayNumber' => $dayNumber,
                    'weekday' => $dayNames[$day->dayOfWeekIso - 1],
                    'outOfRange' => true,
                    'isSaturday' => false,
                    'isSunday' => false,
                    'isHoliday' => false,
                    'holidayName' => null,
                    'weekNumber' => null,
                    'entries' => [],
                ];
                continue;
            }

            $dateKey = $day->format('Y-m-d');
            $holidayName = $holidayMap[$dateKey] ?? null;

            $days[$dayNumber] = [
                'dayNumber' => $dayNumber,
                'weekday' => $dayNames[$day->dayOfWeekIso - 1],
                'outOfRange' => false,
                'isSaturday' => $options['highlightWeekends'] && $day->isSaturday(),
                'isSunday' => $options['highlightWeekends'] && $day->isSunday(),
                'isHoliday' => $holidayName !== null,
                'holidayName' => $holidayName,
                'weekNumber' => ($options['showWeekNumbers'] && $day->dayOfWeekIso === 1) ? $day->isoWeek() : null,
                'entries' => array_values($cells[$dateKey] ?? []),
            ];
        }

        return [
            'label' => $monthStart->translatedFormat('F Y'),
            'days' => $days,
        ];
    }

    /**
     * Feiertage inkl. jährlicher Gedenktage als Y-m-d => Name (mehrere per " / " verbunden).
     *
     * @return array<string, string>
     */
    private function buildHolidayMap(Carbon $globalStart, Carbon $globalEnd): array
    {
        $holidays = Holiday::query()
            ->select(['id', 'name', 'date', 'end_date', 'yearly'])
            ->where(function ($query) use ($globalStart, $globalEnd): void {
                $query->where(function ($range) use ($globalStart, $globalEnd): void {
                    $range->whereDate('date', '<=', $globalEnd->format('Y-m-d'))
                        ->whereDate('end_date', '>=', $globalStart->format('Y-m-d'));
                })->orWhere('yearly', true);
            })
            ->get();

        $map = [];
        foreach ($holidays as $holiday) {
            $holidayStart = Carbon::parse($holiday->date)->startOfDay();
            $holidayEnd = Carbon::parse($holiday->end_date ?? $holiday->date)->startOfDay();

            if ($holiday->yearly) {
                // Jährliche Einträge in jedes betroffene Jahr des Zeitraums projizieren
                $lengthInDays = $holidayStart->diffInDays($holidayEnd);
                for ($year = $globalStart->year; $year <= $globalEnd->year; $year++) {
                    $projectedStart = $holidayStart->copy()->setYear($year);
                    $this->fillHolidayRange(
                        $map,
                        $projectedStart,
                        $projectedStart->copy()->addDays($lengthInDays),
                        $holiday->name,
                        $globalStart,
                        $globalEnd
                    );
                }
                continue;
            }

            $this->fillHolidayRange($map, $holidayStart, $holidayEnd, $holiday->name, $globalStart, $globalEnd);
        }

        return $map;
    }

    /**
     * @param array<string, string> $map
     */
    private function fillHolidayRange(
        array &$map,
        Carbon $holidayStart,
        Carbon $holidayEnd,
        string $name,
        Carbon $globalStart,
        Carbon $globalEnd
    ): void {
        if ($holidayEnd->lt($globalStart) || $holidayStart->gt($globalEnd)) {
            return;
        }
        $rangeStart = $holidayStart->max($globalStart)->copy()->startOfDay();
        $rangeEnd = $holidayEnd->min($globalEnd)->copy()->startOfDay();

        foreach (CarbonPeriod::create($rangeStart, $rangeEnd) as $day) {
            $dateKey = $day->format('Y-m-d');
            $map[$dateKey] = isset($map[$dateKey]) && !str_contains($map[$dateKey], $name)
                ? $map[$dateKey] . ' / ' . $name
                : ($map[$dateKey] ?? $name);
        }
    }

    /**
     * Kürzel aus dem Raumnamen: Initialen der ersten Wörter, max. 3 Zeichen
     * (z. B. "Große Bühne" => "GB", "Studio" => "ST").
     *
     * @return array<int, string>
     */
    private function buildRoomAbbreviations(\Illuminate\Support\Collection $rooms): array
    {
        $abbreviations = [];
        foreach ($rooms as $room) {
            $words = preg_split('/\s+/', trim((string) $room->name)) ?: [];
            $words = array_values(array_filter($words, static fn (string $word): bool => $word !== ''));

            if (count($words) >= 2) {
                $abbreviation = mb_strtoupper(
                    implode('', array_map(static fn (string $word): string => mb_substr($word, 0, 1), array_slice($words, 0, 3)))
                );
            } else {
                $abbreviation = mb_strtoupper(mb_substr($words[0] ?? '', 0, 2));
            }

            $abbreviations[$room->id] = $abbreviation;
        }

        return $abbreviations;
    }
}

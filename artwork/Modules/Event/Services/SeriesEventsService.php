<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Einzige Stelle für Wiederholungstermine (Serien): Ausrollen, Vorschau, Serien-Tab,
 * Bearbeiten/Löschen mit Reichweite, Turnus-/Ende-Abgleich, Lösen und Wiederherstellen.
 *
 * Regeln (KONZEPT_Wiederholungstermine.md):
 * - Nie hart löschen; alles geht in den Papierkorb (Soft-Delete mit Kaskade).
 * - Ein Termin im Papierkorb gilt als belegt: sein Datum wird beim Ausrollen nie neu erzeugt.
 *   Ausnahme: Termine, die ein Neu-Ausrollen selbst gerade in den Papierkorb gelegt hat.
 * - Abweichende Termine (is_series_exception) bekommen keine Zeit-Deltas und werden beim
 *   Neu-Ausrollen weder gelöscht noch doppelt erzeugt.
 */
class SeriesEventsService
{
    public const SCOPE_SINGLE = 'single';
    public const SCOPE_FOLLOWING = 'following';
    public const SCOPE_ALL = 'all';

    public const SCOPES = [self::SCOPE_SINGLE, self::SCOPE_FOLLOWING, self::SCOPE_ALL];

    /** Sicherheitsgrenze pro Serie – schützt vor „täglich, Ende in 30 Jahren“. */
    public const MAX_OCCURRENCES = 500;

    /** Felder, die bei „Dieser und folgende“ / „Ganze Serie“ auf die Geschwister übertragen werden. */
    private const PROPAGATED_FIELDS = [
        'name',
        'eventName',
        'description',
        'event_type_id',
        'event_status_id',
        'room_id',
        'project_id',
        'audience',
        'is_loud',
        'allDay',
        'admission_time',
    ];

    public function __construct(
        private readonly EventService $eventService,
        private readonly WorkingHourCacheService $workingHourCacheService,
        private readonly RoomRequestNotificationService $roomRequestNotificationService,
    ) {
    }

    // ---------------------------------------------------------------------
    // Definition & Generator
    // ---------------------------------------------------------------------

    /**
     * Rohdaten aus dem Request in eine konsistente Seriendefinition überführen.
     *
     * @param array<string, mixed> $input  frequency, end_date, weekdays, occurrence_count
     * @return array{frequency_id:int,start_date:string,end_date:?string,weekdays:?array<int>,occurrence_count:?int}
     */
    public function normalizeDefinition(array $input, Carbon $anchorStart): array
    {
        $frequency = (int) ($input['frequency'] ?? $input['frequency_id'] ?? SeriesEvents::FREQUENCY_WEEKLY);
        if (!in_array($frequency, [1, 2, 3, 4], true)) {
            $frequency = SeriesEvents::FREQUENCY_WEEKLY;
        }

        $occurrenceCount = $input['occurrence_count'] ?? null;
        $occurrenceCount = $occurrenceCount !== null && $occurrenceCount !== ''
            ? max(1, min(self::MAX_OCCURRENCES, (int) $occurrenceCount))
            : null;

        $endDate = $input['end_date'] ?? null;
        $endDate = $endDate ? Carbon::parse($endDate)->toDateString() : null;
        // Genau ein Ende-Modus: Anzahl gewinnt, wenn beides kommt
        if ($occurrenceCount !== null) {
            $endDate = null;
        }

        $weekdays = null;
        if (in_array($frequency, [SeriesEvents::FREQUENCY_WEEKLY, SeriesEvents::FREQUENCY_BIWEEKLY], true)) {
            $raw = $input['weekdays'] ?? null;
            $raw = is_array($raw) ? $raw : [];
            $weekdays = array_values(array_unique(array_filter(
                array_map('intval', $raw),
                static fn (int $d) => $d >= 1 && $d <= 7
            )));
            // Der Wochentag des ersten Termins ist immer Teil der Serie
            $anchorWeekday = $anchorStart->isoWeekday();
            if (!in_array($anchorWeekday, $weekdays, true)) {
                $weekdays[] = $anchorWeekday;
            }
            sort($weekdays);
            // Nur der Anker-Wochentag = klassische Wochenserie, dann kein JSON nötig
            if ($weekdays === [$anchorWeekday]) {
                $weekdays = null;
            }
        }

        return [
            'frequency_id' => $frequency,
            'start_date' => $anchorStart->toDateString(),
            'end_date' => $endDate,
            'weekdays' => $weekdays,
            'occurrence_count' => $occurrenceCount,
        ];
    }

    /**
     * Alle Termine (inkl. des ersten) einer Definition ab $start erzeugen.
     *
     * @param array{frequency_id:int,end_date:?string,weekdays:?array<int>,occurrence_count:?int} $definition
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function generateOccurrences(Carbon $start, Carbon $end, array $definition): array
    {
        $durationMinutes = max(0, (int) $start->diffInMinutes($end, false));
        $endLimit = !empty($definition['end_date'])
            ? Carbon::parse($definition['end_date'])->endOfDay()
            : null;
        $max = $definition['occurrence_count'] ?? null;
        $max = $max ? min((int) $max, self::MAX_OCCURRENCES) : self::MAX_OCCURRENCES;

        $result = [];
        $push = static function (Carbon $occurrenceStart) use (&$result, $endLimit, $max, $durationMinutes): bool {
            if ($endLimit && $occurrenceStart->gt($endLimit)) {
                return false;
            }
            if (count($result) >= $max) {
                return false;
            }
            $result[] = [
                'start' => $occurrenceStart->copy(),
                'end' => $occurrenceStart->copy()->addMinutes($durationMinutes),
            ];
            return true;
        };

        $frequency = (int) $definition['frequency_id'];

        if ($frequency === SeriesEvents::FREQUENCY_DAILY) {
            for ($n = 0; $n < self::MAX_OCCURRENCES; $n++) {
                if (!$push($start->copy()->addDays($n))) {
                    break;
                }
            }
            return $result;
        }

        if ($frequency === SeriesEvents::FREQUENCY_MONTHLY) {
            for ($n = 0; $n < self::MAX_OCCURRENCES; $n++) {
                if (!$push($start->copy()->addMonthsNoOverflow($n))) {
                    break;
                }
            }
            return $result;
        }

        // wöchentlich / 14-tägig, optional an mehreren Wochentagen
        $weekInterval = $frequency === SeriesEvents::FREQUENCY_BIWEEKLY ? 2 : 1;
        $weekdays = $definition['weekdays'] ?? null;
        $weekdays = is_array($weekdays) && $weekdays !== [] ? $weekdays : [$start->isoWeekday()];
        sort($weekdays);
        $time = $start->format('H:i:s');
        $weekStart = $start->copy()->startOfWeek(Carbon::MONDAY);

        for ($w = 0; $w < self::MAX_OCCURRENCES * 7; $w++) {
            $base = $weekStart->copy()->addWeeks($w * $weekInterval);
            foreach ($weekdays as $weekday) {
                $candidate = $base->copy()->addDays($weekday - 1)->setTimeFromTimeString($time);
                if ($candidate->lt($start)) {
                    continue;
                }
                if (!$push($candidate)) {
                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * Vorschau beim Anlegen: Termine + Raumkollisionen je Termin (eine Abfrage für alle).
     *
     * @param array<string, mixed> $definitionInput
     * @return array{occurrences: array<int, array{start:string,end:string,collisions:int}>, total:int, capped:bool}
     */
    public function preview(Carbon $start, Carbon $end, array $definitionInput, ?int $roomId): array
    {
        $definition = $this->normalizeDefinition($definitionInput, $start);
        $occurrences = $this->generateOccurrences($start, $end, $definition);

        $roomEvents = collect();
        if ($roomId && $occurrences !== []) {
            $first = $occurrences[0]['start'];
            $last = $occurrences[count($occurrences) - 1]['end'];
            $roomEvents = Event::query()
                ->select(['id', 'start_time', 'end_time'])
                ->where('room_id', $roomId)
                ->where('start_time', '<', $last)
                ->where('end_time', '>', $first)
                ->get();
        }

        $items = [];
        foreach ($occurrences as $occurrence) {
            $collisions = $roomEvents->filter(
                fn (Event $event) => Carbon::parse($event->start_time)->lt($occurrence['end'])
                    && Carbon::parse($event->end_time)->gt($occurrence['start'])
            )->count();

            $items[] = [
                'start' => $occurrence['start']->format('Y-m-d H:i'),
                'end' => $occurrence['end']->format('Y-m-d H:i'),
                'collisions' => $collisions,
            ];
        }

        return [
            'definition' => $definition,
            'occurrences' => $items,
            'total' => count($items),
            'capped' => count($items) >= self::MAX_OCCURRENCES,
        ];
    }

    // ---------------------------------------------------------------------
    // Anlegen
    // ---------------------------------------------------------------------

    /**
     * Serie für einen frisch angelegten Termin erzeugen und die übrigen Termine ausrollen.
     *
     * @param array<string, mixed> $definitionInput
     * @param array<int> $propertyIds
     */
    public function createSeriesForEvent(Event $firstEvent, array $definitionInput, array $propertyIds): SeriesEvents
    {
        $start = Carbon::parse($firstEvent->start_time);
        $end = Carbon::parse($firstEvent->end_time);
        $definition = $this->normalizeDefinition($definitionInput, $start);

        return DB::transaction(function () use ($firstEvent, $start, $end, $definition, $propertyIds): SeriesEvents {
            /** @var SeriesEvents $series */
            $series = SeriesEvents::query()->create($definition);
            $firstEvent->forceFill(['is_series' => true, 'series_id' => $series->id, 'is_series_exception' => false]);
            $firstEvent->save();

            $firstDate = $start->toDateString();
            foreach ($this->generateOccurrences($start, $end, $definition) as $occurrence) {
                if ($occurrence['start']->toDateString() === $firstDate) {
                    continue;
                }
                $this->cloneForOccurrence($firstEvent, $series, $occurrence['start'], $occurrence['end'], $propertyIds);
            }

            return $series;
        });
    }

    /**
     * Einen Serientermin als Kopie des Quelltermins auf ein neues Datum legen.
     *
     * @param array<int> $propertyIds
     */
    public function cloneForOccurrence(
        Event $source,
        SeriesEvents $series,
        Carbon $start,
        Carbon $end,
        array $propertyIds
    ): Event {
        /** @var Event $event */
        $event = Event::query()->create([
            'name' => $source->name,
            'eventName' => $source->eventName,
            'description' => $source->description,
            'start_time' => $start,
            'end_time' => $end,
            'admission_time' => $source->admission_time,
            'occupancy_option' => $source->occupancy_option,
            'audience' => $source->audience,
            'is_loud' => $source->is_loud,
            'event_type_id' => $source->event_type_id,
            'event_status_id' => $source->event_status_id,
            'room_id' => $source->room_id,
            'user_id' => Auth::id() ?? $source->user_id,
            'project_id' => $source->project_id,
            'is_series' => true,
            'series_id' => $series->id,
            'is_series_exception' => false,
            'allDay' => $source->allDay,
            'is_planning' => (bool) $source->is_planning,
        ]);
        $event->eventProperties()->sync($propertyIds);

        if ($event->occupancy_option && !$event->is_planning) {
            $this->roomRequestNotificationService->notifyRoomAdmins($event);
        }

        broadcast(new EventCreated($event->fresh(), $event->room_id));

        return $event;
    }

    // ---------------------------------------------------------------------
    // Lesen (Modal-Vorbelegung + Serien-Tab)
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getSeriesPayload(Event $event): array
    {
        /** @var SeriesEvents|null $series */
        $series = $event->series_id ? SeriesEvents::query()->find($event->series_id) : null;
        if (!$series) {
            return ['series' => null, 'occurrences' => [], 'counts' => null];
        }

        $now = Carbon::now();
        $siblings = Event::withTrashed()
            ->where('series_id', $series->id)
            ->with('room:id,name')
            ->withCount('shifts')
            ->orderBy('start_time')
            ->get();

        $occurrences = $siblings->map(static function (Event $sibling) use ($event, $now): array {
            return [
                'id' => $sibling->id,
                'start' => Carbon::parse($sibling->start_time)->format('Y-m-d H:i'),
                'end' => Carbon::parse($sibling->end_time)->format('Y-m-d H:i'),
                'allDay' => (bool) $sibling->allDay,
                'eventName' => $sibling->eventName,
                'roomId' => $sibling->room_id,
                'roomName' => $sibling->room?->name,
                'isException' => (bool) $sibling->is_series_exception,
                'isTrashed' => $sibling->trashed(),
                'isPast' => Carbon::parse($sibling->end_time)->lt($now),
                'isCurrent' => $sibling->id === $event->id,
                'shiftsCount' => (int) $sibling->shifts_count,
            ];
        })->values()->all();

        $active = $siblings->filter(static fn (Event $e) => !$e->trashed());

        return [
            'series' => $series->toDefinitionArray(),
            'occurrences' => $occurrences,
            'counts' => [
                'total' => $siblings->count(),
                'active' => $active->count(),
                'trashed' => $siblings->count() - $active->count(),
                'exceptions' => $active->filter(static fn (Event $e) => $e->is_series_exception)->count(),
                'shifts' => (int) $active->sum('shifts_count'),
                'first' => $active->first() ? Carbon::parse($active->first()->start_time)->toDateString() : null,
                'last' => $active->last() ? Carbon::parse($active->last()->start_time)->toDateString() : null,
            ],
        ];
    }

    // ---------------------------------------------------------------------
    // Bearbeiten mit Reichweite
    // ---------------------------------------------------------------------

    public function normalizeScope(?string $scope): string
    {
        return in_array($scope, self::SCOPES, true) ? $scope : self::SCOPE_SINGLE;
    }

    /**
     * Aktive Geschwister eines Serientermins nach Reichweite (ohne den Termin selbst).
     *
     * @param Carbon $cutoff Ursprünglicher Start des bearbeiteten Termins (für „folgende“)
     */
    public function siblingsQuery(Event $event, string $scope, Carbon $cutoff, bool $withTrashed = false): Builder
    {
        $query = ($withTrashed ? Event::withTrashed() : Event::query())
            ->where('series_id', $event->series_id)
            ->where('id', '!=', $event->id);

        if ($scope === self::SCOPE_FOLLOWING) {
            $query->where('start_time', '>', $cutoff);
        }

        return $query;
    }

    /**
     * Geänderte Felder und Zeit-Delta des bearbeiteten Termins auf die Geschwister übertragen.
     *
     * @param array<string, mixed> $changedFields  Nur die tatsächlich geänderten Felder (Dirty-Subset)
     * @param array<int>|null $propertyIds  null = Eigenschaften nicht anfassen
     * @return int Anzahl aktualisierter Termine
     */
    public function propagateToSiblings(
        Event $event,
        string $scope,
        Carbon $cutoff,
        array $changedFields,
        int $deltaStartMinutes,
        int $deltaEndMinutes,
        ?array $propertyIds
    ): int {
        if ($scope === self::SCOPE_SINGLE || !$event->series_id) {
            return 0;
        }

        $fields = array_intersect_key($changedFields, array_flip(self::PROPAGATED_FIELDS));
        $hasDelta = $deltaStartMinutes !== 0 || $deltaEndMinutes !== 0;
        if ($fields === [] && !$hasDelta && $propertyIds === null) {
            return 0;
        }

        $siblings = $this->siblingsQuery($event, $scope, $cutoff)->orderBy('start_time')->get();
        $updated = 0;

        /** @var Event $sibling */
        foreach ($siblings as $sibling) {
            $sibling->fill($fields);

            if ($hasDelta && !$sibling->is_series_exception) {
                $oldStart = Carbon::parse($sibling->start_time);
                $newStart = $oldStart->copy()->addMinutes($deltaStartMinutes);
                $newEnd = Carbon::parse($sibling->end_time)->addMinutes($deltaEndMinutes);
                if ($newEnd->lte($newStart)) {
                    $duration = (int) $oldStart->diffInMinutes($sibling->end_time);
                    $newEnd = $newStart->copy()->addMinutes(max(15, $duration));
                }
                $sibling->start_time = $newStart;
                $sibling->end_time = $newEnd;
                $deltaDays = (int) $oldStart->copy()->startOfDay()->diffInDays($newStart->copy()->startOfDay(), false);
                $this->moveShiftsWithEvent($sibling, $deltaDays);
            }

            $this->eventService->save($sibling);
            if ($propertyIds !== null) {
                $sibling->eventProperties()->sync($propertyIds);
            }
            $updated++;
        }

        return $updated;
    }

    /**
     * Schichten eines Termins um ganze Tage mitziehen (Uhrzeiten der Schichten bleiben).
     */
    public function moveShiftsWithEvent(Event $event, int $deltaDays): void
    {
        $shifts = Shift::query()->where('event_id', $event->id)->get();
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->workingHourCacheService->forgetForShift($shift);
            $shift->fill([
                'start_date' => Carbon::parse($shift->start_date)->addDays($deltaDays)->format('Y-m-d'),
                'end_date' => Carbon::parse($shift->end_date)->addDays($deltaDays)->format('Y-m-d'),
                'event_start_day' => Carbon::parse($event->start_time)->format('Y-m-d'),
                'event_end_day' => Carbon::parse($event->end_time)->format('Y-m-d'),
            ]);
            app(ShiftService::class)->save($shift);
        }
    }

    // ---------------------------------------------------------------------
    // Turnus / Ende ändern
    // ---------------------------------------------------------------------

    /**
     * Plan für eine Definitionsänderung (Dry-Run): was würde in den Papierkorb gehen, was neu entstehen.
     *
     * @param array<string, mixed> $definitionInput
     * @return array{
     *   rebuild: bool, changed: bool, definition: array<string, mixed>,
     *   toTrash: Collection<int, Event>, toCreate: array<int, array{start: Carbon, end: Carbon}>,
     *   shifts: int, exceptions: int
     * }
     */
    public function planDefinitionChange(Event $event, SeriesEvents $series, array $definitionInput): array
    {
        $eventTime = Carbon::parse($event->start_time)->format('H:i:s');
        // Altbestand ohne start_date: Anker = frühester Termin der Serie (auch im Papierkorb)
        $anchorDate = $series->start_date
            ? Carbon::parse($series->start_date)
            : Carbon::parse(
                Event::withTrashed()->where('series_id', $series->id)->min('start_time') ?? $event->start_time
            );
        $anchorStart = $anchorDate->setTimeFromTimeString($eventTime);
        $new = $this->normalizeDefinition($definitionInput, $anchorStart);
        $old = $series->toDefinitionArray();

        $structuralChange = (int) $old['frequency_id'] !== $new['frequency_id']
            || ($old['weekdays'] ?? null) !== ($new['weekdays'] ?? null);
        $endChange = ($old['end_date'] ?? null) !== $new['end_date']
            || ($old['occurrence_count'] ?? null) !== $new['occurrence_count'];

        $empty = [
            'rebuild' => false, 'changed' => false, 'definition' => $new,
            'toTrash' => new Collection(), 'toCreate' => [], 'shifts' => 0, 'exceptions' => 0,
        ];
        if (!$structuralChange && !$endChange) {
            return $empty;
        }

        $eventStart = Carbon::parse($event->start_time);
        $eventEnd = Carbon::parse($event->end_time);
        $allSiblings = Event::withTrashed()
            ->where('series_id', $series->id)
            ->withCount('shifts')
            ->orderBy('start_time')
            ->get();
        $activeSiblings = $allSiblings->filter(static fn (Event $e) => !$e->trashed());

        if ($structuralChange) {
            // Zukunft ab dem bearbeiteten Termin neu ausrollen; abweichende Termine bleiben stehen
            $toTrash = $activeSiblings->filter(
                static fn (Event $e) => $e->id !== $event->id
                    && !$e->is_series_exception
                    && Carbon::parse($e->start_time)->gt($eventStart)
            );
            $trashIds = $toTrash->pluck('id')->all();
            $occupiedDates = $allSiblings
                ->reject(static fn (Event $e) => in_array($e->id, $trashIds, true))
                ->map(static fn (Event $e) => Carbon::parse($e->start_time)->toDateString())
                ->flip();

            // Ab dem bearbeiteten Termin mit dessen Uhrzeit/Dauer; sein eigenes Datum entfällt
            $definitionFromEvent = $new;
            if ($new['occurrence_count'] !== null) {
                // Anzahl bezieht sich auf die ganze Serie: bereits vorhandene Termine bis hier abziehen
                $before = $activeSiblings->filter(
                    static fn (Event $e) => Carbon::parse($e->start_time)->lte($eventStart)
                )->count();
                $definitionFromEvent['occurrence_count'] = max(1, $new['occurrence_count'] - $before + 1);
            }
            $toCreate = array_values(array_filter(
                $this->generateOccurrences($eventStart, $eventEnd, $definitionFromEvent),
                static fn (array $o) => !$occupiedDates->has($o['start']->toDateString())
            ));

            return [
                'rebuild' => true,
                'changed' => true,
                'definition' => $new,
                'toTrash' => $toTrash->values(),
                'toCreate' => $toCreate,
                'shifts' => (int) $toTrash->sum('shifts_count'),
                'exceptions' => $activeSiblings->filter(
                    static fn (Event $e) => $e->is_series_exception && Carbon::parse($e->start_time)->gt($eventStart)
                )->count(),
            ];
        }

        // Nur das Ende hat sich geändert: verlängern hängt an, kürzen schneidet ab
        $toTrash = new Collection();
        $toCreate = [];

        if ($new['occurrence_count'] !== null) {
            $surplus = $activeSiblings->values()->slice($new['occurrence_count']);
            $toTrash = $surplus->values();
            $missing = $new['occurrence_count'] - $activeSiblings->count();
            if ($missing > 0) {
                $toCreate = $this->occurrencesToAppend($series, $activeSiblings, $allSiblings, $new, $missing);
            }
        } else {
            $newEnd = Carbon::parse($new['end_date'])->endOfDay();
            $toTrash = $activeSiblings->filter(
                static fn (Event $e) => Carbon::parse($e->start_time)->gt($newEnd)
            )->values();
            $lastActive = $activeSiblings->last();
            if ($lastActive && Carbon::parse($lastActive->start_time)->lt($newEnd)) {
                $toCreate = $this->occurrencesToAppend($series, $activeSiblings, $allSiblings, $new, null);
            }
        }

        return [
            'rebuild' => false,
            'changed' => true,
            'definition' => $new,
            'toTrash' => $toTrash,
            'toCreate' => $toCreate,
            'shifts' => (int) $toTrash->sum('shifts_count'),
            'exceptions' => 0,
        ];
    }

    /**
     * Termine, die beim Verlängern hinter dem letzten vorhandenen Termin anzuhängen sind.
     *
     * @param Collection<int, Event> $activeSiblings
     * @param Collection<int, Event> $allSiblings
     * @param array<string, mixed> $definition
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    private function occurrencesToAppend(
        SeriesEvents $series,
        Collection $activeSiblings,
        Collection $allSiblings,
        array $definition,
        ?int $limit
    ): array {
        /** @var Event|null $template */
        $template = $activeSiblings->last();
        if (!$template) {
            return [];
        }

        $templateStart = Carbon::parse($template->start_time);
        $templateEnd = Carbon::parse($template->end_time);
        $anchor = ($series->start_date ? Carbon::parse($series->start_date) : $templateStart->copy())
            ->setTimeFromTimeString($templateStart->format('H:i:s'));
        $anchorEnd = $anchor->copy()->addMinutes((int) $templateStart->diffInMinutes($templateEnd));

        $occupied = $allSiblings
            ->map(static fn (Event $e) => Carbon::parse($e->start_time)->toDateString())
            ->flip();
        $lastDate = $templateStart->toDateString();

        // Beim Verlängern zählt das Ziel (Datum/Anzahl) ab dem Serienanker; die Anzahl-Grenze
        // deckelt hier nur die neu anzuhängenden Termine.
        $generatorDefinition = $definition;
        $generatorDefinition['occurrence_count'] = null;
        if ($definition['end_date'] === null) {
            // Anzahl-Modus ohne Datum: großzügig erzeugen und unten auf $limit kappen
            $generatorDefinition['occurrence_count'] = self::MAX_OCCURRENCES;
        }

        $result = [];
        foreach ($this->generateOccurrences($anchor, $anchorEnd, $generatorDefinition) as $occurrence) {
            $date = $occurrence['start']->toDateString();
            if ($date <= $lastDate || $occupied->has($date)) {
                continue;
            }
            $result[] = $occurrence;
            if ($limit !== null && count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * Dry-Run für die Warnung im Modal.
     *
     * @param array<string, mixed> $definitionInput
     * @return array<string, mixed>
     */
    public function impact(Event $event, SeriesEvents $series, array $definitionInput): array
    {
        $plan = $this->planDefinitionChange($event, $series, $definitionInput);

        return [
            'changed' => $plan['changed'],
            'rebuild' => $plan['rebuild'],
            'trash' => $plan['toTrash']->count(),
            'shifts' => $plan['shifts'],
            'create' => count($plan['toCreate']),
            'exceptions' => $plan['exceptions'],
            'definition' => $plan['definition'],
        ];
    }

    /**
     * Definitionsänderung ausführen.
     *
     * @param array<string, mixed> $definitionInput
     * @param array<int> $propertyIds
     * @return array{trashed:int, created:int, rebuild:bool}
     */
    public function applyDefinitionChange(
        Event $event,
        SeriesEvents $series,
        array $definitionInput,
        array $propertyIds
    ): array {
        $plan = $this->planDefinitionChange($event, $series, $definitionInput);
        if (!$plan['changed']) {
            return ['trashed' => 0, 'created' => 0, 'rebuild' => false];
        }

        return DB::transaction(function () use ($event, $series, $plan, $propertyIds): array {
            $series->fill($plan['definition']);
            $series->save();

            $this->trashEvents($plan['toTrash']);

            $template = $plan['rebuild'] ? $event : ($this->latestActiveSibling($series) ?? $event);
            foreach ($plan['toCreate'] as $occurrence) {
                $this->cloneForOccurrence($template, $series, $occurrence['start'], $occurrence['end'], $propertyIds);
            }

            return [
                'trashed' => $plan['toTrash']->count(),
                'created' => count($plan['toCreate']),
                'rebuild' => $plan['rebuild'],
            ];
        });
    }

    private function latestActiveSibling(SeriesEvents $series): ?Event
    {
        /** @var Event|null $event */
        $event = Event::query()->where('series_id', $series->id)->orderByDesc('start_time')->first();

        return $event;
    }

    // ---------------------------------------------------------------------
    // Löschen / Lösen / Wiederherstellen
    // ---------------------------------------------------------------------

    /**
     * Termin plus Geschwister nach Reichweite in den Papierkorb legen.
     *
     * @return int Anzahl Termine im Papierkorb
     */
    public function trashScoped(Event $event, string $scope): int
    {
        $cutoff = Carbon::parse($event->start_time);
        $targets = $this->siblingsQuery($event, $scope, $cutoff)->get()->push($event);

        $this->trashEvents($targets);

        return $targets->count();
    }

    /**
     * Termin aus der Serie lösen (mode=single) oder Serie beenden (mode=end: übrige in den Papierkorb).
     *
     * @return int Anzahl Termine im Papierkorb
     */
    public function detach(Event $event, string $mode): int
    {
        $trashed = 0;

        DB::transaction(function () use ($event, $mode, &$trashed): void {
            $seriesId = $event->series_id;

            if ($mode === 'end' && $seriesId) {
                $others = Event::query()->where('series_id', $seriesId)->where('id', '!=', $event->id)->get();
                $this->trashEvents($others);
                $trashed = $others->count();
            }

            $event->forceFill(['is_series' => false, 'series_id' => null, 'is_series_exception' => false]);
            $event->save();

            // Serie nur entfernen, wenn kein Termin (auch keiner im Papierkorb) mehr darauf zeigt
            if ($seriesId && !Event::withTrashed()->where('series_id', $seriesId)->exists()) {
                SeriesEvents::query()->whereKey($seriesId)->delete();
            }
        });

        return $trashed;
    }

    /**
     * Alle Termine einer Serie aus dem Papierkorb holen.
     *
     * @return int Anzahl wiederhergestellter Termine
     */
    public function restoreSeries(SeriesEvents $series): int
    {
        $trashed = Event::onlyTrashed()->where('series_id', $series->id)->get();
        if ($trashed->isEmpty()) {
            return 0;
        }

        foreach ($trashed as $event) {
            if ($event->project_id && !$event->project()->exists()) {
                $event->project_id = null;
                $event->saveQuietly();
            }
        }

        $this->eventService->restoreAll(
            $trashed,
            app(ShiftsQualificationsService::class),
            app(ChangeService::class),
            app(EventCommentService::class),
            app(TimelineService::class),
            app(ShiftService::class),
            app(SubEventService::class),
        );

        return $trashed->count();
    }

    /**
     * Soft-Delete mit Kaskade (Schichten, Zeitleisten, Untertermine, Kommentare) – ohne
     * Einzel-Benachrichtigungen, damit eine Serie nicht dutzende Meldungen erzeugt.
     *
     * @param Collection<int, Event>|array<Event> $events
     */
    public function trashEvents(Collection|array $events): void
    {
        if (count($events) === 0) {
            return;
        }

        $this->eventService->deleteAll(
            $events,
            app(ShiftsQualificationsService::class),
            app(ShiftUserService::class),
            app(ShiftFreelancerService::class),
            app(ShiftServiceProviderService::class),
            app(ChangeService::class),
            app(EventCommentService::class),
            app(TimelineService::class),
            app(ShiftService::class),
            app(SubEventService::class),
            app(NotificationService::class),
            app(ProjectTabService::class),
            false,
        );
    }
}

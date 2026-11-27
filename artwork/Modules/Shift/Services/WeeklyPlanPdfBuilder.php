<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Daten des Wochenplans je Person (PDF): eine Kalenderwoche mit Diensten, individuellen Zeiten,
 * Tagesdiensten, ganzen und halben freien Tagen sowie Abwesenheiten, dazu die unverbindliche Vorschau
 * auf die Folgewoche. Entspricht den Pflichtangaben des Wochenplans nach NV Bühne § 6 Abs. 1, soweit
 * sie die Schichtplanung kennt; Probenart und Mitwirkende gehören zur Besetzungsdisposition und
 * stehen nicht im Plan.
 *
 * Dienste, individuelle Zeiten und Tagesdienste kommen aus derselben Quelle wie Einsatzplan und
 * Monats-PDF (EventService), ein Dienst zählt zum Tag seines Beginns. Abwesenheiten erscheinen ohne
 * Art und ohne Kommentar: Im Plan steht nur, dass die Person abwesend oder nicht verfügbar ist.
 */
class WeeklyPlanPdfBuilder
{
    public const WORKER_MODELS = [
        'user' => User::class,
        'freelancer' => Freelancer::class,
        'service_provider' => ServiceProvider::class,
    ];

    public const MAX_WORKERS = 150;

    public function __construct(private readonly EventService $eventService)
    {
    }

    /**
     * Seite einer Person für die Woche ab $weekStart (wird auf den Montag gelegt).
     *
     * @return array<string, mixed>
     */
    public function buildPage(
        User|Freelancer|ServiceProvider $worker,
        Carbon $weekStart,
        bool $committedShiftsOnly = false,
    ): array {
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $previewEnd = $weekStart->copy()->addDays(13)->endOfDay();
        $type = $this->workerType($worker);

        $daysWithData = $this->eventService->getDaysWithEventsAndTotalPlannedWorkingHours(
            (int) $worker->getKey(),
            $type,
            $weekStart->copy(),
            $previewEnd->copy(),
            $committedShiftsOnly
        );

        $qualificationNames = ShiftQualification::query()->pluck('name', 'id')->all();
        $freeDays = $this->freeDaysByDate($worker, $weekStart, $previewEnd);
        $absences = $this->absencesByDate($worker, $weekStart, $previewEnd);

        $days = [];
        for ($offset = 0; $offset < 14; $offset++) {
            $date = $weekStart->copy()->addDays($offset);
            $key = $date->toDateString();
            $dayData = $daysWithData[$key] ?? [];

            $shifts = [];
            foreach (($dayData['shifts'] ?? []) as $shift) {
                $shifts[] = $this->shiftEntry((array) $shift, $type, (int) $worker->getKey(), $qualificationNames);
            }

            $individualTimes = [];
            foreach (($dayData['individualTimes'] ?? []) as $individualTime) {
                $individualTimes[] = [
                    'title' => (string) ($individualTime['title'] ?? ''),
                    'full_day' => (bool) ($individualTime['full_day'] ?? false),
                    'start' => $this->time($individualTime['start_time'] ?? null),
                    'end' => $this->time($individualTime['end_time'] ?? null),
                ];
            }

            $days[] = [
                'date' => $key,
                'weekday' => $date->translatedFormat('D'),
                'label' => $date->format('d.m.'),
                'isWeekend' => $date->isWeekend(),
                'holidays' => array_values(array_unique(array_map(
                    static fn (array $holiday): string => (string) ($holiday['name'] ?? ''),
                    $dayData['holidays'] ?? []
                ))),
                'shifts' => $shifts,
                'individualTimes' => $individualTimes,
                'dayServices' => array_values(array_map(
                    static fn (array $dayService): string => (string) ($dayService['name'] ?? ''),
                    $dayData['dayServices'] ?? []
                )),
                'freeDays' => $freeDays[$key] ?? [],
                'absences' => $absences[$key] ?? [],
            ];
        }

        $week = array_slice($days, 0, 7);
        $preview = array_slice($days, 7, 7);

        return [
            'name' => $this->workerName($worker),
            'workerType' => $type,
            'weekStart' => $weekStart->toDateString(),
            'weekLabel' => $this->weekLabel($weekStart),
            'previewLabel' => $this->weekLabel($weekStart->copy()->addWeek()),
            'days' => $week,
            'previewDays' => $preview,
            'hasEntries' => $this->hasEntries($week),
            'hasPreviewEntries' => $this->hasEntries($preview),
        ];
    }

    /**
     * Personen für das Sammel-PDF: entweder die ausdrücklich genannten oder alle einplanbaren
     * Personen der gewählten Arten, optional auf Gewerke eingeschränkt (zugeordnete Gewerke).
     *
     * @param array<int, array{type: string, id: int}> $explicitWorkers
     * @param array<int, string> $workerTypes
     * @param array<int, int> $craftIds
     * @return Collection<int, User|Freelancer|ServiceProvider>
     */
    public function resolveWorkers(array $explicitWorkers, array $workerTypes, array $craftIds): Collection
    {
        $workers = collect();

        if ($explicitWorkers !== []) {
            foreach (collect($explicitWorkers)->groupBy('type') as $type => $entries) {
                $modelClass = self::WORKER_MODELS[$type] ?? null;
                if ($modelClass === null) {
                    continue;
                }
                $workers = $workers->concat(
                    $modelClass::query()->whereKey($entries->pluck('id')->unique()->all())->get()
                );
            }
        } else {
            foreach (array_unique($workerTypes) as $type) {
                $modelClass = self::WORKER_MODELS[$type] ?? null;
                if ($modelClass === null) {
                    continue;
                }
                $query = $modelClass::query()->canWorkShifts();
                if ($craftIds !== []) {
                    $query->whereHas(
                        'assignedCrafts',
                        static fn ($craftQuery) => $craftQuery->whereIn('crafts.id', $craftIds)
                    );
                }
                $workers = $workers->concat($query->get());
            }
        }

        return $workers
            ->sortBy(fn (User|Freelancer|ServiceProvider $worker): string => mb_strtolower($this->sortName($worker)))
            ->values();
    }

    /**
     * @param array<string, mixed> $shift
     * @param array<int, string> $qualificationNames
     * @return array<string, mixed>
     */
    private function shiftEntry(array $shift, string $type, int $workerId, array $qualificationNames): array
    {
        $function = null;
        $note = null;
        foreach (($shift['workers'] ?? []) as $worker) {
            if (data_get($worker, 'type') === $type && (int) data_get($worker, 'id') === $workerId) {
                $qualificationId = data_get($worker, 'pivot.shift_qualification_id');
                $function = $qualificationId ? ($qualificationNames[$qualificationId] ?? null) : null;
                $note = data_get($worker, 'pivot.short_description') ?: null;
                break;
            }
        }

        $project = data_get($shift, 'project.name') ?? data_get($shift, 'event.project.name');
        $event = data_get($shift, 'event.eventName') ?: data_get($shift, 'event.name');
        if ($event !== null && $event === $project) {
            $event = null;
        }

        // Zeiten der Person (worker_start/-end) vor den Schichtzeiten, wie im Monats-PDF
        $start = $this->time($shift['worker_start'] ?? null) ?? $this->time($shift['start'] ?? null);
        $end = $this->time($shift['worker_end'] ?? null) ?? $this->time($shift['end'] ?? null);

        return [
            'start' => $start,
            'end' => $end,
            // Dienst über Mitternacht: zählt zum Tag des Beginns, das Ende liegt am Folgetag
            'endsNextDay' => $start !== null && $end !== null && $end <= $start,
            'room' => data_get($shift, 'room.name') ?? data_get($shift, 'event.room.name'),
            'project' => $project,
            'event' => $event,
            'craft' => data_get($shift, 'craft.name'),
            'craftColor' => data_get($shift, 'craft.color') ?: '#3730a3',
            'function' => $function,
            'description' => $shift['description'] ?? null,
            'note' => $note,
            'committed' => (bool) ($shift['is_committed'] ?? false),
        ];
    }

    /**
     * Ganze und halbe freie Tage je Datum: geplante freie Tage aus dem Tagesstatus (FREE_WORK) und
     * gewährte Ersatzfreitage. Einträge: ['half' => bool, 'period' => 'morning'|'afternoon'|null,
     * 'compensation' => bool].
     *
     * @return array<string, list<array{half: bool, period: string|null, compensation: bool}>>
     */
    private function freeDaysByDate(User|Freelancer|ServiceProvider $worker, Carbon $from, Carbon $to): array
    {
        $result = [];

        $planned = Vacation::query()
            ->where('vacationer_type', $worker::class)
            ->where('vacationer_id', $worker->getKey())
            ->where('type', VacationType::FREE_WORK->value)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['id', 'date', 'full_day', 'day_part']);

        foreach ($planned as $vacation) {
            $period = $this->dayPart($vacation->day_part);
            $result[Carbon::parse($vacation->date)->toDateString()][] = [
                'half' => $period !== null,
                'period' => $period,
                'compensation' => false,
            ];
        }

        if ($worker instanceof User) {
            $granted = CompensationDayOff::query()
                ->where('user_id', $worker->getKey())
                ->granted()
                ->whereDate('granted_date', '>=', $from->toDateString())
                ->whereDate('granted_date', '<=', $to->toDateString())
                ->get(['id', 'granted_date', 'value', 'half_day_period']);

            foreach ($granted as $dayOff) {
                $half = (float) $dayOff->value < 1.0;
                $result[$dayOff->granted_date->toDateString()][] = [
                    'half' => $half,
                    'period' => $half ? $this->dayPart($dayOff->half_day_period) : null,
                    'compensation' => true,
                ];
            }
        }

        return $result;
    }

    /**
     * Abwesenheiten je Datum, bewusst ohne Art und Kommentar. Einträge: ['unavailable' => bool,
     * 'full_day' => bool, 'start' => ?string, 'end' => ?string]; unavailable = "nicht verfügbar"
     * statt "abwesend".
     *
     * @return array<string, list<array{unavailable: bool, full_day: bool, start: string|null, end: string|null}>>
     */
    private function absencesByDate(User|Freelancer|ServiceProvider $worker, Carbon $from, Carbon $to): array
    {
        $result = [];

        $vacations = Vacation::query()
            ->where('vacationer_type', $worker::class)
            ->where('vacationer_id', $worker->getKey())
            ->whereIn('type', [VacationType::OFF_WORK->value, VacationType::NOT_AVAILABLE->value])
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->orderBy('date')
            ->get(['id', 'date', 'type', 'full_day', 'start_time', 'end_time']);

        foreach ($vacations as $vacation) {
            $start = $this->time($vacation->getRawOriginal('start_time'));
            $end = $this->time($vacation->getRawOriginal('end_time'));
            $fullDay = (bool) $vacation->full_day || $start === null || $end === null;

            $result[Carbon::parse($vacation->date)->toDateString()][] = [
                'unavailable' => (string) $vacation->type === VacationType::NOT_AVAILABLE->value,
                'full_day' => $fullDay,
                'start' => $fullDay ? null : $start,
                'end' => $fullDay ? null : $end,
            ];
        }

        return $result;
    }

    private function dayPart(mixed $value): ?string
    {
        return match (is_string($value) ? strtolower($value) : null) {
            'morning', 'am', 'vormittag' => 'morning',
            'afternoon', 'pm', 'nachmittag' => 'afternoon',
            default => null,
        };
    }

    /**
     * @param list<array<string, mixed>> $days
     */
    private function hasEntries(array $days): bool
    {
        foreach ($days as $day) {
            if (
                $day['shifts'] !== [] || $day['individualTimes'] !== [] || $day['dayServices'] !== []
                || $day['freeDays'] !== [] || $day['absences'] !== []
            ) {
                return true;
            }
        }

        return false;
    }

    private function weekLabel(Carbon $weekStart): string
    {
        return sprintf(
            'KW %d/%d · %s – %s',
            $weekStart->isoWeek,
            $weekStart->isoWeekYear,
            $weekStart->format('d.m.'),
            $weekStart->copy()->addDays(6)->format('d.m.Y')
        );
    }

    /**
     * Uhrzeit als "HH:MM" aus "HH:MM[:SS]" oder einem vollständigen Zeitstempel.
     */
    private function time(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/(\d{2}:\d{2})(:\d{2})?$/', $value, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public function workerType(User|Freelancer|ServiceProvider $worker): string
    {
        return match (true) {
            $worker instanceof Freelancer => 'freelancer',
            $worker instanceof ServiceProvider => 'service_provider',
            default => 'user',
        };
    }

    private function workerName(User|Freelancer|ServiceProvider $worker): string
    {
        if ($worker instanceof ServiceProvider) {
            return (string) $worker->provider_name;
        }

        return trim($worker->first_name . ' ' . $worker->last_name);
    }

    private function sortName(User|Freelancer|ServiceProvider $worker): string
    {
        if ($worker instanceof ServiceProvider) {
            return (string) $worker->provider_name;
        }

        return trim($worker->last_name . ' ' . $worker->first_name);
    }
}

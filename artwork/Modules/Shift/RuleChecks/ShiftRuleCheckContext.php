<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Holidays\Services\SpecialDayService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Datenkontext EINES Regellaufs für EINE Person: alle Schichten (mit personenindividuellen
 * Pivot-Zeiten), Individualzeiten, gewährten halben Ersatzfreitage und Sondertage des erweiterten
 * Zeitraums werden einmal geladen und von allen Checks gemeinsam genutzt. Damit bleibt die Zahl
 * der Datenbankabfragen je Person und Lauf konstant — unabhängig von der Anzahl der Tage.
 *
 * Der Zeitraum ist gegenüber dem Prüfzeitraum um einen Rand erweitert (Ruhezeiten brauchen den
 * Vortag, Wochenmaxima die ganze Woche, "Tage in Folge" den Rückblick). Fragt ein Check Daten
 * außerhalb des Kontextzeitraums an, fällt der Helfer im AbstractRuleCheck auf eine Direktabfrage
 * zurück (siehe covers()).
 */
final class ShiftRuleCheckContext
{
    /** @var Collection<int, Shift>|null */
    private ?Collection $shifts = null;

    /** @var Collection<int, \Artwork\Modules\IndividualTimes\Models\IndividualTime>|null */
    private ?Collection $individualTimes = null;

    /** @var Collection<string, Collection>|null granted_date => halbe Ersatzfreitage */
    private ?Collection $grantedHalvesByDate = null;

    /** @var array<string, string>|null 'Y-m-d' => Feiertagsname */
    private ?array $specialDays = null;

    /** @var array<string, int>|null 'Y-m-d' => Schichtminuten (Pause einmal am ersten Schichttag) */
    private ?array $shiftMinutesPerDay = null;

    /** @var array<string, list<int>>|null effektiver Tag 'Y-m-d' => Positionen in shifts() */
    private ?array $shiftIndexByDay = null;

    /** @var array<string, list<int>>|null Tag 'Y-m-d' => Positionen in individualTimes() */
    private ?array $individualTimeIndexByDay = null;

    private ?SpecialDayService $specialDayService = null;

    public function __construct(
        public readonly User $user,
        public readonly Carbon $from,
        public readonly Carbon $to,
    ) {
    }

    /**
     * Kontext für den Prüfzeitraum [$startDate, $endDate] mit Rand: $daysBefore Tage zurück
     * (mind. 7 für Wochenfenster + Vortag), $daysAfter Tage voraus (mind. 7 für das Wochenende).
     */
    public static function forRange(User $user, Carbon $startDate, Carbon $endDate, int $daysBefore = 7, int $daysAfter = 7): self
    {
        return new self(
            $user,
            $startDate->copy()->startOfDay()->subDays(max(7, $daysBefore)),
            $endDate->copy()->startOfDay()->addDays(max(7, $daysAfter)),
        );
    }

    public function covers(Carbon $from, Carbon $to): bool
    {
        return $from->copy()->startOfDay()->gte($this->from) && $to->copy()->startOfDay()->lte($this->to);
    }

    /**
     * Schichten der Person im Kontextzeitraum – über die users->shifts()-Relation, damit die
     * personenindividuellen Pivot-Zeiten (shift_workers.start_date/end_date/start_time/end_time)
     * als $shift->pivot verfügbar sind. Matcht Schichtzeitraum ODER Pivot-Zeitraum.
     *
     * @return Collection<int, Shift>
     */
    public function shifts(): Collection
    {
        if ($this->shifts !== null) {
            return $this->shifts;
        }

        $from = $this->from->toDateString();
        $to = $this->to->toDateString();

        return $this->shifts = $this->user->shifts()
            ->with('shiftGroup')
            ->where(function ($query) use ($from, $to): void {
                $query->where(function ($sub) use ($from, $to): void {
                    $sub->whereDate('shifts.start_date', '<=', $to)
                        ->whereDate('shifts.end_date', '>=', $from);
                })->orWhere(function ($sub) use ($from, $to): void {
                    $sub->whereDate('shift_workers.start_date', '<=', $to)
                        ->whereDate('shift_workers.end_date', '>=', $from);
                });
            })
            ->orderBy('shifts.start_date')
            ->orderBy('shifts.start')
            ->get();
    }

    /**
     * Schichten, deren EFFEKTIVER Zeitraum (Pivot-Datum vor Schichtdatum, Über-Mitternacht-Schichten
     * belegen beide Tage) den Bereich [$fromKey, $toKey] berührt — in der Reihenfolge von shifts().
     * Bedient aus einem einmal aufgebauten Tagesindex: die effektiven Tage werden je Schicht EINMAL
     * berechnet, eine Tagesabfrage kostet danach nur noch Array-Zugriffe statt eines Filters über alle
     * Schichten des Kontexts.
     *
     * @return Collection<int, Shift>
     */
    public function shiftsBetween(string $fromKey, string $toKey): Collection
    {
        $shifts = $this->shifts();
        $this->shiftIndexByDay ??= $this->buildDayIndex(
            $shifts->all(),
            static fn (Shift $shift): array => self::effectiveShiftDateKeys($shift)
        );

        return $this->pickBetween($shifts, $this->shiftIndexByDay, $fromKey, $toKey);
    }

    /**
     * Individualzeiten, die den Bereich [$fromKey, $toKey] berühren — in der Reihenfolge von individualTimes().
     *
     * @return Collection<int, \Artwork\Modules\IndividualTimes\Models\IndividualTime>
     */
    public function individualTimesBetween(string $fromKey, string $toKey): Collection
    {
        $individualTimes = $this->individualTimes();
        $this->individualTimeIndexByDay ??= $this->buildDayIndex(
            $individualTimes->all(),
            static fn ($it): array => [
                Carbon::parse((string) $it->start_date)->toDateString(),
                Carbon::parse((string) ($it->end_date ?: $it->start_date))->toDateString(),
            ]
        );

        return $this->pickBetween($individualTimes, $this->individualTimeIndexByDay, $fromKey, $toKey);
    }

    /**
     * Effektive Datumsgrenzen einer Schicht für die Person: Pivot-Datum vor Schichtdatum.
     *
     * @return array{0: string, 1: string} ['Y-m-d' Start, 'Y-m-d' Ende]
     */
    public static function effectiveShiftDateKeys(Shift $shift): array
    {
        $pivot = $shift->pivot ?? null;
        $start = $pivot?->start_date ?: $shift->start_date;
        $end = $pivot?->end_date ?: $shift->end_date;

        return [
            Carbon::parse((string) $start)->toDateString(),
            Carbon::parse((string) ($end ?: $start))->toDateString(),
        ];
    }

    /**
     * Tagesindex: jeder Eintrag landet unter jedem Tag seines Zeitraums [startKey, endKey] (Position in
     * der Ausgangsliste). Verdrehte Grenzen (Ende vor Beginn) zählen nur am Starttag.
     *
     * @param list<mixed> $items
     * @param callable(mixed): array{0: string, 1: string} $dateKeysOf
     * @return array<string, list<int>>
     */
    private function buildDayIndex(array $items, callable $dateKeysOf): array
    {
        $index = [];
        foreach ($items as $position => $item) {
            [$startKey, $endKey] = $dateKeysOf($item);
            $index[$startKey][] = $position;
            if ($endKey <= $startKey) {
                continue;
            }
            $cursor = Carbon::parse($startKey)->addDay();
            while ($cursor->toDateString() <= $endKey) {
                $index[$cursor->toDateString()][] = $position;
                $cursor->addDay();
            }
        }

        return $index;
    }

    /**
     * Einträge, die im Index unter einem Tag von $fromKey bis $toKey stehen — dedupliziert, in
     * Ausgangsreihenfolge. Bei sehr großen Bereichen (mehr Tage als Indexeinträge) wird statt der Tage
     * der Index durchlaufen.
     *
     * @param array<string, list<int>> $index
     */
    private function pickBetween(Collection $items, array $index, string $fromKey, string $toKey): Collection
    {
        if ($toKey < $fromKey || $index === []) {
            return collect();
        }

        $positions = [];
        $from = Carbon::parse($fromKey);
        $spanDays = (int) $from->diffInDays(Carbon::parse($toKey)) + 1;
        if ($spanDays <= count($index)) {
            $cursor = $from;
            for ($i = 0; $i < $spanDays; $i++) {
                foreach ($index[$cursor->toDateString()] ?? [] as $position) {
                    $positions[$position] = true;
                }
                $cursor->addDay();
            }
        } else {
            foreach ($index as $dayKey => $dayPositions) {
                if ($dayKey < $fromKey || $dayKey > $toKey) {
                    continue;
                }
                foreach ($dayPositions as $position) {
                    $positions[$position] = true;
                }
            }
        }

        if ($positions === []) {
            return collect();
        }
        ksort($positions);

        $all = $items->all();

        return collect(array_map(static fn (int $position) => $all[$position], array_keys($positions)));
    }

    /**
     * @return Collection<int, \Artwork\Modules\IndividualTimes\Models\IndividualTime>
     */
    public function individualTimes(): Collection
    {
        return $this->individualTimes ??= $this->user->individualTimes()
            ->individualByDateRange($this->from->toDateString(), $this->to->toDateString())
            ->orderBy('start_date')
            ->get();
    }

    /**
     * @return Collection<string, Collection> granted_date ('Y-m-d') => gewährte halbe Ersatzfreitage
     */
    public function grantedHalvesByDate(): Collection
    {
        return $this->grantedHalvesByDate ??= app(CompensationDayOffRepository::class)
            ->getGrantedHalvesForUserInRange($this->user->id, $this->from->toDateString(), $this->to->toDateString())
            ->groupBy(fn ($half): string => Carbon::parse($half->granted_date)->format('Y-m-d'));
    }

    /**
     * @return array<string, string> 'Y-m-d' => Feiertagsname (nur Sondertage)
     */
    public function specialDays(): array
    {
        return $this->specialDays ??= $this->specialDayService()->specialDaysBetween($this->from, $this->to);
    }

    /**
     * Eine SpecialDayService-Instanz je Lauf: der Service cached Feiertage und Tagesentscheidungen
     * pro Instanz — ein frisches app() je Tag würde die Feiertagstabelle je Tag neu laden.
     */
    public function specialDayService(): SpecialDayService
    {
        return $this->specialDayService ??= app(SpecialDayService::class);
    }

    /**
     * Schichtminuten je Tag aus dem WorkTimeCalculationService (pivot-aware, Pause einmal am ersten
     * Schichttag) — berechnet auf den bereits geladenen Schichten, keine zweite Schichtabfrage.
     *
     * @return array<string, int>
     */
    public function shiftMinutesPerDay(): array
    {
        if ($this->shiftMinutesPerDay !== null) {
            return $this->shiftMinutesPerDay;
        }

        // Kopie, damit die geladene Schichtliste nicht als Relation am (ggf. geteilten) User hängen bleibt.
        $userForCalculation = clone $this->user;
        $userForCalculation->setRelation('shifts', $this->shifts());

        return $this->shiftMinutesPerDay = app(WorkTimeCalculationService::class)
            ->shiftMinutesPerDay($userForCalculation, $this->from->copy(), $this->to->copy());
    }
}

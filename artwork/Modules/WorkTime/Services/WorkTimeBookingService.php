<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Artwork\Modules\WorkTime\Support\NightWindow;
use Carbon\Carbon;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;
use Throwable;

/**
 * Nächtliche Buchung des Arbeitszeitkontos (ein Datensatz je User und Tag).
 *
 * Soll und Ist kommen ausschließlich aus dem WorkTimeCalculationService (Sondertage,
 * Ersatzfreie Tage, Krank/Urlaub, Dreimonatsdurchschnitt). Hier bleibt nur die
 * Nachtstunden-Ermittlung und die atomare Buchung inkl. Saldo-Delta.
 */
class WorkTimeBookingService
{
    public function __construct(
        protected GeneralSettings $settings,
        protected WorkTimeBookingRepository $repository,
        protected WorkingHourCacheService $workingHourCacheService,
        protected WorkTimeCalculationService $workTimeCalculationService,
    ) {
    }

    public function calculateDailyWorkingHours(): void
    {
        // Arbeitszeitberechnung ausgeschaltet: keine Buchung, kein Saldo, keine Überstunden
        if (!WorkTimeAccounting::isEnabled()) {
            return;
        }

        $this->refreshWorkTimeActivations();

        $users = $this->repository->getWorkShiftUsers();
        $today = now()->startOfDay();

        foreach ($users as $user) {
            // Ein fehlerhafter Datensatz (z. B. kaputtes Muster) darf die Buchung der
            // übrigen Personen nicht verhindern: melden und mit der nächsten weitermachen.
            try {
                if ($this->bookDay($user, $today) !== null) {
                    $this->workingHourCacheService->forgetForEntity('user', $user->id);
                    // Rebuild overtime entries + deadlines (flips expired open entries to "payable").
                    app(OvertimeService::class)->recomputeForUser($user);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Bucht vergangene Tage einer Person neu bzw. erstmals („Tag neu buchen“ in den Arbeitszeiten):
     * gleiche Rechnung wie die nächtliche Buchung, Delta gegen die vorhandene Tagesbuchung. Heute und
     * künftige Tage werden übersprungen – die bucht der Nachtlauf. Liefert die neu gebuchten Tage.
     *
     * @param iterable<Carbon|string> $days
     * @return array<string, int> Tag (Y-m-d) => Saldo-Delta
     */
    public function rebookPastDays(User $user, iterable $days): array
    {
        if (!WorkTimeAccounting::isEnabled()) {
            return [];
        }

        $today = now()->startOfDay();

        $pastDays = collect($days)
            ->map(fn ($day): Carbon => Carbon::parse($day)->startOfDay())
            ->filter(fn (Carbon $day): bool => $day->lt($today))
            ->sortBy(fn (Carbon $day): int => $day->getTimestamp())
            ->values();
        if ($pastDays->isEmpty()) {
            return [];
        }
        // Frisch laden (nur der Zeitraum ±1 Tag): Aufrufer ändern direkt davor Schichtzeiten
        $this->loadWorkForRange($user, $pastDays->first(), $pastDays->last());
        // Ein Kontext für den ganzen Zeitraum statt je Tag (Schichten, Muster, Abwesenheiten einmal laden)
        $context = $this->workTimeCalculationService->buildContext($user, $pastDays->first(), $pastDays->last(), [
            'use_bookings' => false,
            'legacy_adjustments' => true,
        ]);

        $deltas = [];
        foreach ($pastDays as $day) {
            $delta = $this->bookDay($user, $day, $context);
            if ($delta !== null) {
                $deltas[$day->toDateString()] = $delta;
            }
        }

        if ($deltas !== []) {
            $this->workingHourCacheService->forgetForEntity('user', $user->id);
            app(OvertimeService::class)->recomputeForUser($user);
        }

        return $deltas;
    }

    /**
     * Aktuelle Rechnung (ohne Buchungen) je Tag eines Zeitraums: Soll, Ist und Nachtminuten. Aufgenommen vor
     * und nach einer Schichtänderung, damit bookShiftTimeChange nur deren Wirkung bucht.
     *
     * @return array<string, array{target: int|null, actual: int, night: int}> 'Y-m-d' => Werte
     */
    public function liveDaySnapshot(User $user, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $this->loadWorkForRange($user, $from, $to);
        $context = $this->workTimeCalculationService->buildContext($user, $from, $to, [
            'use_bookings' => false,
            'legacy_adjustments' => false,
        ]);

        $snapshot = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $breakdown = $this->workTimeCalculationService->dayBreakdown($user, $day, $context);
            $snapshot[$day->toDateString()] = [
                'target' => !empty($breakdown['target_unknown']) ? null : $breakdown['target'],
                'actual' => (int) $breakdown['actual'],
                'night' => $breakdown['is_sick'] && $breakdown['sick_factor'] >= 1.0
                    ? 0
                    : $this->calculateNightMinutes($day, $user),
            ];
        }

        return $snapshot;
    }

    /**
     * Genehmigte Zeitänderung: auf bereits gebuchte Tage (bis heute) nur die Wirkung der Schichtänderung buchen
     * – Differenz der aktuellen Rechnung nach und vor der Änderung, Tag für Tag (Anteil nach Mitternacht am
     * Folgetag, Pause ab dem ersten Tag, Krank/Urlaub, ganztägige Zeiten). Andere Abweichungen eines Tages
     * (rückwirkend Krank, Altregeln …) bleiben unberührt und weiter ein Hinweis („Tag neu buchen“). Nie
     * gebuchte Tage bleiben ungebucht.
     *
     * @param array<string, array{target: int|null, actual: int, night: int}> $before liveDaySnapshot vor der Änderung
     * @return array<string, int> Tag (Y-m-d) => Saldo-Delta
     */
    public function bookShiftTimeChange(User $user, array $before): array
    {
        if (!WorkTimeAccounting::isEnabled() || $before === []) {
            return [];
        }

        $dayKeys = array_keys($before);
        sort($dayKeys);
        $after = $this->liveDaySnapshot($user, Carbon::parse($dayKeys[0]), Carbon::parse(end($dayKeys)));
        $today = now()->toDateString();

        $deltas = [];
        foreach ($dayKeys as $dayKey) {
            $old = $before[$dayKey];
            $new = $after[$dayKey] ?? null;
            // Ohne Muster gibt es keine Tagesbuchung; künftige Tage bucht der Nachtlauf
            if ($new === null || $old['target'] === null || $new['target'] === null || $dayKey > $today) {
                continue;
            }
            $workedDelta = $new['actual'] - $old['actual'];
            $wantedDelta = $new['target'] - $old['target'];
            $nightDelta = $new['night'] - $old['night'];
            if ($workedDelta === 0 && $wantedDelta === 0 && $nightDelta === 0) {
                continue;
            }

            $delta = $this->repository->adjustDailyBookingWithLockedBalance(
                $user,
                Carbon::parse($dayKey),
                $workedDelta,
                $wantedDelta,
                $nightDelta
            );
            if ($delta !== null) {
                $deltas[$dayKey] = $delta;
            }
        }

        if ($deltas !== []) {
            $this->workingHourCacheService->forgetForEntity('user', $user->id);
            app(OvertimeService::class)->recomputeForUser($user);
        }

        return $deltas;
    }

    /**
     * Schichten und individuelle Zeiten frisch und nur für den Zeitraum (±1 Tag für Zeiten über Mitternacht) laden
     * statt aller Jahre der Person.
     */
    private function loadWorkForRange(User $user, Carbon $from, Carbon $to): void
    {
        $earliest = $from->copy()->subDay()->toDateString();
        $latest = $to->copy()->addDay()->toDateString();

        $user->setRelation('shifts', $user->shifts()
            ->where('shifts.start_date', '<=', $latest)
            ->where(function ($query) use ($earliest): void {
                $query->where('shifts.end_date', '>=', $earliest)
                    ->orWhere('shift_workers.end_date', '>=', $earliest);
            })
            ->get());
        $user->setRelation('individualTimes', $user->individualTimes()
            ->individualByDateRange($earliest, $latest)
            ->get());
    }

    /**
     * Tagesbuchung für genau einen Tag anlegen oder aktualisieren (Re-Run): Soll/Ist aus dem
     * WorkTimeCalculationService ohne vorhandene Buchungen, Saldo-Delta gegen die eigene Tageszeile.
     * Manuelle und Korrekturbuchungen desselben Tages bleiben unberührt. Alte Korrekturzeilen aus Zeitänderungen
     * (am Genehmigungstag gebucht, gehören zu diesem Schichttag) werden vom Ist abgezogen – wie rebook_difference,
     * sonst zählte die Änderung doppelt. Null = nicht buchbar (an diesem Tag kein gültiges Arbeitszeitmuster →
     * Soll unbekannt). $context: vorab mit use_bookings=false und legacy_adjustments=true für einen Zeitraum
     * gebaut, der den Tag enthält.
     *
     * @param array<string, mixed>|null $context
     */
    public function bookDay(User $user, Carbon $day, ?array $context = null): ?int
    {
        $day = $day->copy()->startOfDay();

        // Bestehende Buchung des Tages darf nicht als Ist zurückfließen -> use_bookings=false
        $context ??= $this->workTimeCalculationService->buildContext($user, $day, $day, [
            'use_bookings' => false,
            'legacy_adjustments' => true,
        ]);
        $breakdown = $this->workTimeCalculationService->dayBreakdown($user, $day, $context);

        // Ohne gültiges Muster ist das Soll unbekannt: keine Buchung (kein Fallback auf 0 Soll,
        // sonst würde jede Arbeit als Überstunde verbucht)
        if ($breakdown['target'] === null || !empty($breakdown['target_unknown'])) {
            return null;
        }

        $wantedMinutes = (int) $breakdown['target'];
        $workedMinutes = (int) $breakdown['actual']
            - (int) ($context['legacy_adjustments'][$day->toDateString()] ?? 0);
        $nightMinutes = $breakdown['is_sick'] && $breakdown['sick_factor'] >= 1.0
            ? 0 // Krankheit zählt keine Nachtzeit
            : $this->calculateNightMinutes($day, $user);

        $workTimeBalanceChange = $this->calculateWorkTimeBalanceChange($workedMinutes, $wantedMinutes);

        // Nur die eigene Tagesbuchung (über den Namen): Korrektur-/manuelle Buchungen desselben Tages bleiben
        // unberührt. Buchung, Delta und Saldo atomar unter Sperre der User-Zeile (siehe Repository).
        $delta = $this->repository->bookDailyWithLockedBalance($user, $day, [
            'name' => WorkTimeBookingRepository::dailyBookingName($day),
            'wanted_working_hours' => $wantedMinutes,
            'worked_hours' => $workedMinutes,
            'nightly_working_hours' => $nightMinutes,
            'is_special_day' => (bool) $breakdown['is_special_day'],
            'work_time_balance_change' => $workTimeBalanceChange,
        ]);

        return $delta;
    }

    /**
     * Nachtminuten (Schichten + Individualzeiten) im Nachtfenster der GeneralSettings.
     *
     * Rechenbasis ist NightWindow::minutesWithin() — dieselbe wie in NightWorkMaxHoursCheck. Zuordnung:
     * Schichten zählen ganz zu ihrem Starttag (inkl. Anteil nach Mitternacht), individuelle Zeiten werden
     * je Kalendertag zugeschnitten (der Folgetag zählt seinen Anteil selbst).
     */
    private function calculateNightMinutes(Carbon $day, User $user): int
    {
        $night = 0;
        $window = NightWindow::fromSettings($this->settings);

        $dayStart = $day->copy()->startOfDay();
        $nextDayStart = $dayStart->copy()->addDay();
        $dayKey = $dayStart->toDateString();

        foreach ($user->shifts as $shift) {
            $pivot = $shift->pivot;
            if (!$pivot?->start_date || !$pivot?->start_time || !$pivot?->end_date || !$pivot?->end_time) {
                continue;
            }
            // Schichten werden ihrem Starttag zugerechnet (inkl. Anteil nach Mitternacht)
            if (Carbon::parse($pivot->start_date)->toDateString() !== $dayKey) {
                continue;
            }

            $start = Carbon::parse($pivot->start_date)->setTimeFrom(Carbon::parse($pivot->start_time));
            $end = Carbon::parse($pivot->end_date)->setTimeFrom(Carbon::parse($pivot->end_time));
            if ($end->lte($start) && $end->isSameDay($start)) {
                $end->addDay(); // Ende ohne Folgedatum gespeichert (wie WorkTimeCalculationService)
            }
            $night += $window->minutesWithin($start, $end);
        }

        foreach ($user->individualTimes as $individualTime) {
            if ($individualTime->start_time && $individualTime->end_time && !$individualTime->full_day) {
                // Individuelle Zeiten werden je Kalendertag zugeschnitten (der Folgetag zählt seinen Anteil selbst).
                // Über Datum+Uhrzeit statt days_of_individual_time: dort fehlt bei Zeiten über Mitternacht der
                // Folgetag, dessen Nachtanteil sonst nie gezählt würde.
                $startDate = Carbon::parse($individualTime->start_date)->toDateString();
                $endDate = Carbon::parse($individualTime->end_date ?? $individualTime->start_date)->toDateString();
                $start = Carbon::parse($startDate . ' ' . $individualTime->start_time);
                $end = Carbon::parse($endDate . ' ' . $individualTime->end_time);
                if ($end->lte($start) && $end->isSameDay($start)) {
                    $end->addDay(); // Ende ohne Folgedatum gespeichert
                }
                if ($end->lte($dayStart) || $start->gte($nextDayStart)) {
                    continue;
                }
                $night += $window->minutesWithin(
                    $start->greaterThan($dayStart) ? $start : $dayStart,
                    $end->lessThan($nextDayStart) ? $end : $nextDayStart,
                );
            }
        }

        return $night;
    }

    private function calculateWorkTimeBalanceChange(int $workedHours, int $wantedWorkHours): int
    {
        return $workedHours - $wantedWorkHours;
    }

    public function refreshWorkTimeActivations(): void
    {
        UserWorkTime::query()
            ->whereDate('valid_from', '<=', now())
            ->where(function ($q): void {
                // DATE-Spalte gegen Datum vergleichen: gegen now() (23:59) fiele der letzte Gültigkeitstag raus
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', now());
            })
            ->update(['is_active' => true]);

        UserWorkTime::query()
            ->where(function ($q): void {
                $q->whereDate('valid_from', '>', now())->orWhereDate('valid_until', '<', now());
            })
            ->update(['is_active' => false]);
    }
}

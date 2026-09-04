<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Berechnet die spielzeitbezogenen Kennzahlen je User (DP-18).
 *
 * Eine Methode `computeForUser()` wird sowohl vom nächtlichen Tracking-Job (Persistenz)
 * als auch vom Modal-Endpoint (Live-Nachrechnung der laufenden Spielzeit) genutzt
 * -> garantiert identische Ergebnisse, kein Drift.
 *
 * Sequenzabhängige Kennzahlen (1,5-Tage-Kombinationen) werden NICHT tagesweise inkrementiert,
 * sondern über eine geordnete Tagesliste mit Sliding-Window berechnet. Es fließen nur
 * abgeschlossene Tage (Datum < heute) ein.
 */
class ShiftKpiTrackingService
{
    public function __construct(
        private readonly GeneralSettings $generalSettings,
        private readonly ContractSettingsResolver $contractSettings,
        private readonly WorkTimeCalculationService $workTimeCalculationService,
    ) {
    }

    /**
     * Spielzeit-Fenster aus den GeneralSettings (Toolsettings > Kommunikation & Rechtliches).
     * null, wenn die Spielzeit nicht (oder ungültig) konfiguriert ist – Aufrufer zeigen einen Hinweis.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function getSeasonBounds(): ?array
    {
        $startRaw = trim((string) ($this->generalSettings->playing_time_window_start ?? ''));
        $endRaw = trim((string) ($this->generalSettings->playing_time_window_end ?? ''));
        if ($startRaw === '' || $endRaw === '') {
            return null;
        }

        try {
            $start = Carbon::parse($startRaw)->startOfDay();
            $end = Carbon::parse($endRaw)->endOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($end->lt($start)) {
            return null;
        }

        return [$start, $end];
    }

    /**
     * Kalendarischer Mittelpunkt der Spielzeit -> trennt 1. und 2. Hälfte.
     */
    public function getSeasonMidpoint(Carbon $start, Carbon $end): Carbon
    {
        $midTimestamp = (int) (($start->getTimestamp() + $end->getTimestamp()) / 2);

        return Carbon::createFromTimestamp($midTimestamp)->startOfDay();
    }

    /**
     * Liefert alle Kennzahlen für die angegebene Spielzeit.
     */
    public function computeForUser(User $user, Carbon $seasonStart, Carbon $seasonEnd): array
    {
        $seasonStart = $seasonStart->copy()->startOfDay();
        $seasonEnd = $seasonEnd->copy()->endOfDay();
        $midpoint = $this->getSeasonMidpoint($seasonStart, $seasonEnd);

        $year = Carbon::now()->year;
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        $validFrom = $this->contractSettings->assignFor($user)?->valid_from;
        $validFrom = $validFrom ? Carbon::parse($validFrom)->startOfDay() : null;

        // Gemeinsamer Klassifizierungsbereich (Superset aus Spielzeit + Kalenderjahr + 26-Wochen-Fenster).
        $rangeFrom = $seasonStart->copy()->min($yearStart);
        $rangeTo = $seasonEnd->copy()->max($yearEnd);
        if ($validFrom) {
            $rangeFrom = $rangeFrom->min($validFrom);
            $rangeTo = $rangeTo->max($validFrom->copy()->addWeeks(26));
        }

        $days = $this->classifyDays($user, $rangeFrom, $rangeTo);

        // --- Freie Sonntage Sa/Mo je Hälfte ---
        [$satMonH1, $satMonH2] = $this->freeSundaysWithSatMonPerHalf($days, $seasonStart, $seasonEnd, $midpoint);

        // --- Freie Sonntage + Samstage je Spielzeit ---
        $sundaysAndSaturdays = $this->freeSundaysAndSaturdays($days, $seasonStart, $seasonEnd);

        // --- Freie Sonntage pro Kalenderjahr ---
        $sundaysYear = $this->freeSundaysInRange($days, $yearStart, $yearEnd);

        // --- Freie Sonntage je Spielzeit (gesamt) ---
        $sundaysSeason = $this->freeSundaysInRange($days, $seasonStart, $seasonEnd);

        // --- 1,5-Tage-Kombinationen je Hälfte ---
        [$combosH1, $combosH2] = $this->oneAndHalfDayCombinationsPerHalf($days, $seasonStart, $seasonEnd, $midpoint);

        // --- Gewährte halbe freie Tage je Hälfte ---
        [$halfH1, $halfH2] = $this->grantedHalfFreeDaysPerHalf($days, $seasonStart, $seasonEnd, $midpoint);

        // --- Freie Tage in den ersten 26 Wochen ---
        $daysOff26 = 0.0;
        if ($validFrom) {
            $daysOff26 = $this->freeDayUnitsInRange($days, $validFrom, $validFrom->copy()->addWeeks(26));
        }

        // --- Urlaub Kalenderjahr (gewährt) ---
        $grantedVacation = $this->grantedVacationDaysInRange($days, $yearStart, $yearEnd);

        return [
            'season_start' => $seasonStart->toDateString(),
            'season_end' => $seasonEnd->toDateString(),
            'calendar_year' => $year,
            'free_sundays_sat_mon_half1' => $satMonH1,
            'free_sundays_sat_mon_half2' => $satMonH2,
            'free_sundays_and_saturdays_season' => $sundaysAndSaturdays,
            'free_sundays_calendar_year' => $sundaysYear,
            'free_sundays_per_season' => $sundaysSeason,
            'one_and_half_combos_half1' => $combosH1,
            'one_and_half_combos_half2' => $combosH2,
            'granted_half_free_days_half1' => $halfH1,
            'granted_half_free_days_half2' => $halfH2,
            'days_off_first_26_weeks_count' => $daysOff26,
            'granted_vacation_days_year' => $grantedVacation,
            // Zielwerte ("X") + Aktiv-Flags: Zuweisung vor Vorlage (für die Anzeige im Modal)
            'targets' => $this->extractTargets($user),
            'counted_until' => Carbon::yesterday()->toDateString(),
        ];
    }

    /**
     * Zielwerte "Ist / X": Feld auf der Zuweisung gesetzt (nicht null) -> Zuweisung, sonst Vorlage.
     *
     * @return array<string, array{active: bool, value: int|float}>
     */
    public function extractTargets(User $user): array
    {
        if ($this->contractSettings->assignFor($user) === null) {
            return [];
        }

        $c = $this->contractSettings;

        return [
            'free_sundays_per_season' => [
                'active' => $c->bool($user, 'free_sundays_per_season_active'),
                'value' => $c->int($user, 'free_sundays_per_season'),
            ],
            'days_off_first_26_weeks' => [
                'active' => $c->bool($user, 'days_off_first_26_weeks_active'),
                'value' => $c->float($user, 'days_off_first_26_weeks'),
            ],
            'free_sundays_sat_mon_per_half' => [
                'active' => $c->bool($user, 'free_sundays_sat_mon_per_half_active'),
                'value' => $c->int($user, 'free_sundays_sat_mon_per_half'),
            ],
            'free_sundays_and_saturdays_per_season' => [
                'active' => $c->bool($user, 'free_sundays_and_saturdays_per_season_active'),
                'value' => $c->int($user, 'free_sundays_and_saturdays_per_season'),
            ],
            'free_sundays_per_calendar_year' => [
                'active' => $c->bool($user, 'free_sundays_per_calendar_year_active'),
                'value' => $c->int($user, 'free_sundays_per_calendar_year'),
            ],
            'one_and_half_day_combinations' => [
                'active' => $c->bool($user, 'one_and_half_day_combinations_active'),
                'value' => $c->int($user, 'one_and_half_day_combinations'),
            ],
            'annual_vacation_days' => [
                'active' => true,
                'value' => $this->annualVacationEntitlement($user),
            ],
        ];
    }

    public function annualVacationEntitlement(User $user): int
    {
        return $this->contractSettings->int($user, 'annual_vacation_days');
    }

    /**
     * Gewährte Urlaubstage (OFF_WORK) im Bereich, ganzer Tag = 1, halber Tag = 0,5 (max. 1 je Tag).
     * includePlanned=false zählt nur abgeschlossene Tage (bis gestern) – wie die Spielzeit-Kennzahlen.
     */
    public function grantedVacationUnitsForUser(User $user, Carbon $from, Carbon $to, bool $includePlanned = false): float
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        if (!$includePlanned) {
            $to = $to->min(Carbon::yesterday()->endOfDay());
        }
        if ($to->lt($from)) {
            return 0.0;
        }

        $units = [];
        $vacations = $user->vacations()
            ->where('type', 'OFF_WORK')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', 'full_day']);
        foreach ($vacations as $vacation) {
            $key = Carbon::parse($vacation->date)->toDateString();
            $units[$key] = min(1.0, ($units[$key] ?? 0.0) + ($vacation->full_day ? 1.0 : 0.5));
        }

        return (float) array_sum($units);
    }

    /**
     * Klassifiziert jeden abgeschlossenen Tag (< heute) im Bereich.
     *
     * Zählregeln (Anzeige im Info-Fenster):
     *  - Ganzer freier Tag (GFT): FREE_WORK ganztägig, gewährter Ersatzfreier Tag (Wert >= 1),
     *    beide Halbtage frei oder ein leerer Arbeitstag laut Muster ohne Schicht/Individualzeit/Eintrag.
     *  - Halber freier Tag: genau ein freier Halbtag (Vormittag XOR Nachmittag) oder Ersatzfrei-Halbtag.
     *  - Urlaub (OFF_WORK): ganzer Tag = 1, halber Tag = 0,5.
     *
     * @return array<string, array{occupied: bool, gft: bool, halfMorning: bool, halfAfternoon: bool,
     *                              grantedHalf: bool, offWork: bool, offWorkUnits: float, notAvailable: bool}>
     */
    private function classifyDays(User $user, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $cutoff = Carbon::yesterday()->endOfDay(); // nur abgeschlossene Tage
        $to = $to->copy()->endOfDay();
        if ($to->gt($cutoff)) {
            $to = $cutoff;
        }
        if ($to->lt($from)) {
            return [];
        }

        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        // belegte Tage: Schichten (Pivot) + individuelle Zeiten
        $occupied = [];
        $shiftRows = DB::table('shift_workers')
            ->where('employable_type', \Artwork\Modules\User\Models\User::class)
            ->where('employable_id', $user->id)
            ->whereNull('deleted_at')
            ->whereNotNull('start_date')
            ->where('start_date', '<=', $toStr)
            ->where(function ($q) use ($fromStr): void {
                $q->where('end_date', '>=', $fromStr)->orWhereNull('end_date');
            })
            ->get(['start_date', 'end_date']);
        foreach ($shiftRows as $row) {
            $s = Carbon::parse($row->start_date);
            $e = $row->end_date ? Carbon::parse($row->end_date) : $s->copy();
            for ($d = $s->copy(); $d->lte($e); $d->addDay()) {
                $occupied[$d->toDateString()] = true;
            }
        }
        foreach ($user->individualTimes as $it) {
            foreach (($it->days_of_individual_time ?? []) as $day) {
                $occupied[Carbon::parse($day)->toDateString()] = true;
            }
        }

        // Verfügbarkeits-/Urlaubseinträge je Tag
        $vacByDate = [];
        foreach ($user->vacations()->whereBetween('date', [$fromStr, $toStr])->get() as $v) {
            $vacByDate[Carbon::parse($v->date)->toDateString()][] = $v;
        }

        // gewährte Ersatzfreie Tage je Tag
        $compByDate = [];
        foreach (
            $user->compensationDayOffs()
                ->whereNotNull('granted_at')
                ->whereNotNull('granted_date')
                ->whereBetween('granted_date', [$fromStr, $toStr])
                ->get() as $c
        ) {
            $compByDate[Carbon::parse($c->granted_date)->toDateString()][] = $c;
        }

        // Geplante Arbeitstage aus dem zum Datum gültigen Arbeitszeitmuster (für "leerer Tag = GFT").
        // Ein Tag ohne Schicht zählt nur dann als ganzer freier Tag, wenn an diesem Wochentag
        // laut Muster überhaupt gearbeitet würde – sonst wäre jeder ohnehin freie Tag ein GFT.
        $workingWeekdaysByDate = $this->getWorkingWeekdays($user, $from, $to);

        $result = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $key = $day->toDateString();
            $occupiedDay = isset($occupied[$key]);
            $isWorkingWeekday = $workingWeekdaysByDate[$key] ?? false;

            $halfMorning = false;
            $halfAfternoon = false;
            $freeFull = false;
            $offWork = false;
            $offWorkUnits = 0.0;
            $notAvailable = false;

            foreach ($vacByDate[$key] ?? [] as $v) {
                $type = $v->type;
                if ($type === 'OFF_WORK') {
                    $offWork = true;
                    $offWorkUnits = min(1.0, $offWorkUnits + ($v->full_day ? 1.0 : 0.5));
                } elseif ($type === 'NOT_AVAILABLE') {
                    $notAvailable = true;
                } elseif ($type === 'FREE_WORK') {
                    $part = $v->day_part;
                    if ($v->full_day || $part === null || $part === 'full') {
                        $freeFull = true;
                    } elseif ($part === 'morning') {
                        $halfMorning = true;
                    } elseif ($part === 'afternoon') {
                        $halfAfternoon = true;
                    }
                }
            }

            $compFull = false;
            $compHalf = false;
            foreach ($compByDate[$key] ?? [] as $c) {
                if ((float) $c->value >= 1.0) {
                    $compFull = true;
                } else {
                    $compHalf = true;
                }
            }

            $hasAnyEntry = isset($vacByDate[$key]) || isset($compByDate[$key]);
            // Nur geplante Arbeitstage (Tagessoll laut Muster) zählen als leerer-Tag-GFT.
            $emptyPastDay = !$occupiedDay && !$hasAnyEntry && $isWorkingWeekday;

            $gft = $freeFull || $compFull || ($halfMorning && $halfAfternoon) || $emptyPastDay;

            // gewährte halbe freie Tage (positioniert ODER Ersatzfrei-Halbtag), aber nicht wenn ganzer Tag frei
            $grantedHalf = !$gft && (($halfMorning xor $halfAfternoon) || $compHalf);

            $result[$key] = [
                'occupied' => $occupiedDay,
                'gft' => $gft,
                'halfMorning' => $halfMorning && !$gft,
                'halfAfternoon' => $halfAfternoon && !$gft,
                'grantedHalf' => $grantedHalf,
                'offWork' => $offWork,
                'offWorkUnits' => $offWorkUnits,
                'notAvailable' => $notAvailable,
            ];
        }

        return $result;
    }

    /**
     * Je Datum: ist laut dem an diesem Tag gültigen Arbeitszeitmuster ein Arbeitstag?
     * Ohne Muster gilt die Fünftagewoche (Mo–Fr) aus dem WorkTimeCalculationService.
     *
     * @return array<string, bool> 'Y-m-d' => Arbeitstag
     */
    private function getWorkingWeekdays(User $user, Carbon $from, Carbon $to): array
    {
        $result = [];
        foreach ($this->workTimeCalculationService->baseTargetsForRange($user, $from, $to) as $date => $minutes) {
            $result[$date] = $minutes > 0;
        }

        return $result;
    }

    private function isGft(array $days, string $key): bool
    {
        return ($days[$key]['gft'] ?? false) === true;
    }

    private function freeSundaysInRange(array $days, Carbon $from, Carbon $to): int
    {
        $count = 0;
        foreach ($this->sundaysIn($from, $to) as $sunday) {
            if ($this->isGft($days, $sunday)) {
                $count++;
            }
        }

        return $count;
    }

    private function freeSundaysWithSatMonPerHalf(
        array $days,
        Carbon $from,
        Carbon $to,
        Carbon $midpoint
    ): array {
        $h1 = 0;
        $h2 = 0;
        foreach ($this->sundaysIn($from, $to) as $sunday) {
            if (!$this->isGft($days, $sunday)) {
                continue;
            }
            $sundayDate = Carbon::parse($sunday);
            $saturday = $sundayDate->copy()->subDay()->toDateString();
            $monday = $sundayDate->copy()->addDay()->toDateString();
            if (!$this->isGft($days, $saturday) && !$this->isGft($days, $monday)) {
                continue;
            }
            if ($sundayDate->lt($midpoint)) {
                $h1++;
            } else {
                $h2++;
            }
        }

        return [$h1, $h2];
    }

    private function freeSundaysAndSaturdays(array $days, Carbon $from, Carbon $to): int
    {
        $count = 0;
        foreach ($this->sundaysIn($from, $to) as $sunday) {
            $sundayDate = Carbon::parse($sunday);
            $saturday = $sundayDate->copy()->subDay()->toDateString();
            if ($this->isGft($days, $sunday) && $this->isGft($days, $saturday)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 1,5-Tage-Kombinationen:
     *  - Lauf aus n aufeinanderfolgenden GFT (n>=2) -> (n-1) Kombinationen.
     *  - Einzelner GFT + halber freier Nachmittag davor ODER halber freier Vormittag danach -> 1 Kombination.
     * Zuordnung zur Hälfte über den Starttag der Kombination.
     */
    private function oneAndHalfDayCombinationsPerHalf(
        array $days,
        Carbon $from,
        Carbon $to,
        Carbon $midpoint
    ): array {
        $h1 = 0;
        $h2 = 0;
        $assign = function (Carbon $startDay) use ($midpoint, &$h1, &$h2): void {
            if ($startDay->lt($midpoint)) {
                $h1++;
            } else {
                $h2++;
            }
        };

        $period = collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(fn ($d) => $d->toDateString())
            ->values()
            ->all();

        $i = 0;
        $n = count($period);
        while ($i < $n) {
            $key = $period[$i];
            if (!$this->isGft($days, $key)) {
                $i++;
                continue;
            }
            // maximalen GFT-Lauf bestimmen
            $runStart = $i;
            while ($i + 1 < $n && $this->isGft($days, $period[$i + 1])) {
                $i++;
            }
            $runLength = $i - $runStart + 1;

            if ($runLength >= 2) {
                for ($k = 0; $k < $runLength - 1; $k++) {
                    $assign(Carbon::parse($period[$runStart + $k]));
                }
            } else {
                // einzelner GFT: Halbtag davor (Nachmittag) ODER danach (Vormittag)
                $prevKey = Carbon::parse($key)->subDay()->toDateString();
                $nextKey = Carbon::parse($key)->addDay()->toDateString();
                $halfBefore = ($days[$prevKey]['halfAfternoon'] ?? false) === true;
                $halfAfter = ($days[$nextKey]['halfMorning'] ?? false) === true;
                if ($halfBefore || $halfAfter) {
                    $assign(Carbon::parse($key));
                }
            }
            $i++;
        }

        return [$h1, $h2];
    }

    private function grantedHalfFreeDaysPerHalf(
        array $days,
        Carbon $from,
        Carbon $to,
        Carbon $midpoint
    ): array {
        $h1 = 0;
        $h2 = 0;
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $key = $day->toDateString();
            if (($days[$key]['grantedHalf'] ?? false) === true) {
                if ($day->lt($midpoint)) {
                    $h1++;
                } else {
                    $h2++;
                }
            }
        }

        return [$h1, $h2];
    }

    /**
     * Freie-Tage-Einheiten (GFT = 1, halber freier Tag = 0,5) im Bereich.
     */
    private function freeDayUnitsInRange(array $days, Carbon $from, Carbon $to): float
    {
        $units = 0.0;
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $key = $day->toDateString();
            if (($days[$key]['gft'] ?? false) === true) {
                $units += 1.0;
            } elseif (($days[$key]['grantedHalf'] ?? false) === true) {
                $units += 0.5;
            }
        }

        return $units;
    }

    /**
     * Gewährte Urlaubstage (Vacation-Typ OFF_WORK) im Bereich; ganzer Tag = 1, halber = 0,5.
     */
    private function grantedVacationDaysInRange(array $days, Carbon $from, Carbon $to): float
    {
        $units = 0.0;
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $key = $day->toDateString();
            if (($days[$key]['offWork'] ?? false) === true) {
                $units += (float) ($days[$key]['offWorkUnits'] ?? 1.0);
            }
        }

        return $units;
    }

    /**
     * @return list<string> Y-m-d aller Sonntage im Bereich
     */
    private function sundaysIn(Carbon $from, Carbon $to): array
    {
        $sundays = [];
        $cursor = $from->copy()->startOfDay();
        // zum ersten Sonntag springen
        while ($cursor->dayOfWeek !== Carbon::SUNDAY && $cursor->lte($to)) {
            $cursor->addDay();
        }
        for ($d = $cursor; $d->lte($to); $d->addWeek()) {
            $sundays[] = $d->toDateString();
        }

        return $sundays;
    }
}

<?php

namespace Artwork\Modules\Holidays\Services;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Einzige Quelle für die Frage "Ist dieser Tag ein Sondertag?".
 *
 * Ein Sondertag ist ein Feiertag mit gesetztem Flag `treatAsSpecialDay`. Berücksichtigt werden
 * mehrtägige Einträge (date..end_date) und jährliche Wiederholungen (auch über den Jahreswechsel).
 * Schulferien tragen das Flag nach dem Import nicht und sind damit keine Sondertage.
 *
 * Für personenbezogene Berechnungen (Tagessoll, Regelprüfung) gilt zusätzlich der Vertragsschalter
 * `special_day_rule_active`: Ist er aus, zählen Sondertage für diese Person nicht und jeder Tag hat
 * das normale Tagessoll (Verträge, bei denen Feiertage "egal" sind).
 */
class SpecialDayService
{
    /** @var Collection<int, Holiday>|null */
    private ?Collection $flaggedHolidays = null;

    /** @var array<string, bool> */
    private array $dateCache = [];

    /** @var array<string, bool> Cache je "userId|Y-m-d" (Vertragshistorie: Schalter kann je Tag abweichen) */
    private array $userRuleCache = [];

    public function __construct(private readonly ContractSettingsResolver $contractSettings)
    {
    }

    public function isSpecialDay(Carbon|string $date): bool
    {
        $day = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();
        $key = $day->toDateString();

        if (array_key_exists($key, $this->dateCache)) {
            return $this->dateCache[$key];
        }

        return $this->dateCache[$key] = $this->matchingHoliday($day) !== null;
    }

    public function isSundayOrSpecialDay(Carbon|string $date): bool
    {
        $day = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $day->isSunday() || $this->isSpecialDay($day);
    }

    /**
     * Name des Sondertags (für Anzeige/Tooltips) oder null.
     */
    public function specialDayName(Carbon|string $date): ?string
    {
        $day = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();

        return $this->matchingHoliday($day)?->name;
    }

    /**
     * Sondertage im Zeitraum als Map 'Y-m-d' => Feiertagsname.
     *
     * @return array<string, string>
     */
    public function specialDaysBetween(Carbon $start, Carbon $end): array
    {
        $result = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $holiday = $this->matchingHoliday($cursor);
            if ($holiday !== null) {
                $result[$cursor->toDateString()] = $holiday->name;
                $this->dateCache[$cursor->toDateString()] = true;
            } else {
                $this->dateCache[$cursor->toDateString()] = false;
            }
            $cursor->addDay();
        }

        return $result;
    }

    /**
     * Gilt die Sondertag-Regel für diese Person am Stichtag (Default heute)? Zuweisung (per-User)
     * vor Vertragsvorlage (ContractSettingsResolver, löst den am Tag gültigen Vertragszeitraum auf);
     * ohne Vertrag zählen Sondertage.
     */
    public function specialDayRuleActiveFor(User $user, Carbon|string|null $date = null): bool
    {
        $day = $date === null
            ? Carbon::today()
            : ($date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay());
        $key = $user->id . '|' . $day->toDateString();

        if (array_key_exists($key, $this->userRuleCache)) {
            return $this->userRuleCache[$key];
        }

        return $this->userRuleCache[$key] = $this->contractSettings
            ->bool($user, 'special_day_rule_active', true, $day);
    }

    /**
     * Sondertag UND die Person hat die Sondertag-Regel an diesem Tag aktiv.
     */
    public function countsAsSpecialDayForUser(User $user, Carbon|string $date): bool
    {
        if (!$this->isSpecialDay($date)) {
            return false;
        }

        return $this->specialDayRuleActiveFor($user, $date);
    }

    public function flush(): void
    {
        $this->flaggedHolidays = null;
        $this->dateCache = [];
        $this->userRuleCache = [];
    }

    private function matchingHoliday(Carbon $day): ?Holiday
    {
        foreach ($this->flaggedHolidays() as $holiday) {
            if ($this->holidayCoversDay($holiday, $day)) {
                return $holiday;
            }
        }

        return null;
    }

    private function holidayCoversDay(Holiday $holiday, Carbon $day): bool
    {
        if (!$holiday->date) {
            return false;
        }

        $start = Carbon::parse($holiday->date)->startOfDay();
        $end = $holiday->end_date ? Carbon::parse($holiday->end_date)->startOfDay() : $start->copy();

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        if (!$holiday->yearly) {
            return $day->betweenIncluded($start, $end);
        }

        // Jährlich: Monat/Tag auf das Jahr des geprüften Tags legen, Jahreswechsel (31.12.–02.01.) beachten.
        $thisYearStart = Carbon::create($day->year, $start->month, $start->day)->startOfDay();
        $thisYearEnd = Carbon::create($day->year, $end->month, $end->day)->startOfDay();

        if ($thisYearEnd->lt($thisYearStart)) {
            // Zeitraum läuft über den Jahreswechsel: entweder Ende des Vorjahresblocks ...
            $previousYearStart = $thisYearStart->copy()->subYear();
            if ($day->betweenIncluded($previousYearStart, $thisYearEnd)) {
                return true;
            }
            // ... oder Anfang des Blocks in diesem Jahr.
            $thisYearEnd->addYear();
        }

        return $day->betweenIncluded($thisYearStart, $thisYearEnd);
    }

    /**
     * @return Collection<int, Holiday>
     */
    private function flaggedHolidays(): Collection
    {
        if ($this->flaggedHolidays === null) {
            $this->flaggedHolidays = Holiday::query()
                ->where('treatAsSpecialDay', true)
                ->get(['id', 'name', 'date', 'end_date', 'yearly']);
        }

        return $this->flaggedHolidays;
    }
}

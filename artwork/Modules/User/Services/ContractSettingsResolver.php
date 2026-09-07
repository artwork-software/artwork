<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Carbon\Carbon;

/**
 * Liest Vertragswerte "Zuweisung vor Vorlage":
 * Ist das Feld auf der Zuweisung (user_contract_assigns) gesetzt, gilt die Zuweisung,
 * sonst der Wert der Vertragsvorlage (user_contracts), sonst der Default.
 *
 * "Gesetzt" heißt je Spaltentyp:
 *  - nullable Spalten (z. B. overtime_compensation_period): nicht null.
 *  - numerische NOT-NULL-DEFAULT-0-Spalten der Zuweisung (ZERO_MEANS_UNSET_ON_ASSIGN): ungleich 0.
 *    Eine 0 kann dort nicht von "nie eingetragen" unterschieden werden und fällt deshalb auf die
 *    Vorlage zurück (Vorlage 0 bleibt 0). Auf der Vorlage selbst ist 0 ein echter Wert.
 *  - Bool-Spalten (special_day_rule_active, overtime_rule_active, *_active; NOT NULL DEFAULT 0):
 *    die Zuweisung gilt IMMER – false auf der Zuweisung ist nicht von "nicht gesetzt" unterscheidbar,
 *    das UI spiegelt beim Wählen einer Vorlage deren Schalter ohnehin auf die Zuweisung. Ein
 *    Rückfall auf die Vorlage bräuchte eine nullable Spalte (bewusst nicht in der Härtung geändert).
 *
 * Gilt für Zielwerte (DP-18 "Ist / X"), Dreimonatsflag, Sondertag-Regel und Überstundenregel.
 *
 * Seit 2026-09 ist die Zuweisung eine Historie: Jede Methode nimmt optional einen Stichtag
 * ($date, Default heute) und löst über den an diesem Tag gültigen Zeitraum auf
 * (User::contractAssignFor). Bestehende Aufrufer ohne Datum verhalten sich wie bisher (heute).
 */
class ContractSettingsResolver
{
    /**
     * Numerische Spalten der Zuweisung, die NOT NULL DEFAULT 0 sind (Schema user_contract_assigns):
     * 0 = "nicht gesetzt" → Vorlage. overtime_compensation_period ist nullable und gehört NICHT hierher.
     */
    public const ZERO_MEANS_UNSET_ON_ASSIGN = [
        'free_full_days_per_week',
        'free_half_days_per_week',
        'compensation_period',
        'free_sundays_per_season',
        'days_off_first_26_weeks',
        'free_sundays_sat_mon_per_half',
        'free_sundays_and_saturdays_per_season',
        'free_sundays_per_calendar_year',
        'one_and_half_day_combinations',
        'annual_vacation_days',
    ];

    /** @var array<string, UserContractAssign|null> Cache je "userId|Y-m-d" */
    private array $assignCache = [];

    public function assignFor(User $user, ?Carbon $date = null): ?UserContractAssign
    {
        $day = ($date ?? Carbon::today())->copy()->startOfDay();
        $cacheKey = $user->id . '|' . $day->toDateString();

        if (array_key_exists($cacheKey, $this->assignCache)) {
            return $this->assignCache[$cacheKey];
        }

        // Bereits geladene HasOne-Relation "contract" = heute gültiger Satz (spart die Abfrage);
        // für andere Stichtage gilt die geladene Historie oder eine Abfrage (contractAssignFor).
        $loaded = $user->relationLoaded('contract') ? $user->contract : null;
        if ($loaded !== null && $loaded->coversDate($day)) {
            $assign = $loaded;
        } elseif ($loaded === null && $day->isSameDay(Carbon::today()) && $user->relationLoaded('contract')) {
            $assign = null;
        } else {
            $assign = $user->contractAssignFor($day);
        }

        return $this->assignCache[$cacheKey] = $assign;
    }

    public function templateFor(User $user, ?Carbon $date = null): ?UserContract
    {
        $assign = $this->assignFor($user, $date);
        if ($assign === null) {
            return null;
        }

        if (!$assign->relationLoaded('userContract')) {
            $assign->load('userContract');
        }

        return $assign->userContract;
    }

    public function value(User $user, string $key, mixed $default = null, ?Carbon $date = null): mixed
    {
        $assign = $this->assignFor($user, $date);
        if ($assign === null) {
            return $default;
        }

        if (self::hasValue($assign, $key)) {
            return $assign->getAttribute($key);
        }

        $template = $this->templateFor($user, $date);
        if ($template !== null && self::hasValue($template, $key)) {
            return $template->getAttribute($key);
        }

        return $default;
    }

    public function bool(User $user, string $key, bool $default = false, ?Carbon $date = null): bool
    {
        return (bool) $this->value($user, $key, $default, $date);
    }

    public function int(User $user, string $key, int $default = 0, ?Carbon $date = null): int
    {
        return (int) $this->value($user, $key, $default, $date);
    }

    public function float(User $user, string $key, float $default = 0.0, ?Carbon $date = null): float
    {
        return (float) $this->value($user, $key, $default, $date);
    }

    /**
     * Ersatzfrei-Frist in Tagen: Zuweisung vor Vorlage (0 auf der Zuweisung = nicht gesetzt, siehe
     * ZERO_MEANS_UNSET_ON_ASSIGN – dieselbe Regel gilt seit der Härtung für alle NOT-NULL-DEFAULT-0-Spalten).
     */
    public function compensationPeriod(User $user, ?Carbon $date = null): int
    {
        return $this->int($user, 'compensation_period', 0, $date);
    }

    public function flush(): void
    {
        $this->assignCache = [];
    }

    private static function hasValue(UserContractAssign|UserContract $model, string $key): bool
    {
        if (!array_key_exists($key, $model->getAttributes())) {
            return false;
        }

        $value = $model->getAttribute($key);
        if ($value === null) {
            return false;
        }

        // Zuweisung: 0 in NOT-NULL-DEFAULT-0-Zahlenspalten ist "nicht gesetzt" → Vorlage greift.
        if ($model instanceof UserContractAssign && in_array($key, self::ZERO_MEANS_UNSET_ON_ASSIGN, true)) {
            return (float) $value != 0.0;
        }

        return true;
    }
}

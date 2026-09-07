<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Carbon\Carbon;

/**
 * Liest Vertragswerte "Zuweisung vor Vorlage":
 * Ist das Feld auf der Zuweisung (user_contract_assigns) gesetzt (nicht null), gilt die Zuweisung,
 * sonst der Wert der Vertragsvorlage (user_contracts), sonst der Default.
 *
 * Gilt für Zielwerte (DP-18 "Ist / X"), Dreimonatsflag, Sondertag-Regel und Überstundenregel.
 *
 * Seit 2026-09 ist die Zuweisung eine Historie: Jede Methode nimmt optional einen Stichtag
 * ($date, Default heute) und löst über den an diesem Tag gültigen Zeitraum auf
 * (User::contractAssignFor). Bestehende Aufrufer ohne Datum verhalten sich wie bisher (heute).
 */
class ContractSettingsResolver
{
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
     * Ersatzfrei-Frist in Tagen: Zuweisung vor Vorlage.
     *
     * Die Spalte user_contract_assigns.compensation_period ist NOT NULL DEFAULT 0 – eine 0 auf der
     * Zuweisung bedeutet daher "nicht gesetzt" (nicht "0 Tage Frist") und fällt auf die Vorlage
     * zurück. Einzige Stelle, an der diese Sonderregel gilt; alle Leser der Frist gehen hier durch.
     */
    public function compensationPeriod(User $user, ?Carbon $date = null): int
    {
        $assign = $this->assignFor($user, $date);
        if ($assign === null) {
            return 0;
        }

        $assigned = (int) ($assign->getAttribute('compensation_period') ?? 0);
        if ($assigned > 0) {
            return $assigned;
        }

        return (int) ($this->templateFor($user, $date)?->getAttribute('compensation_period') ?? 0);
    }

    public function flush(): void
    {
        $this->assignCache = [];
    }

    private static function hasValue(UserContractAssign|UserContract $model, string $key): bool
    {
        return array_key_exists($key, $model->getAttributes()) && $model->getAttribute($key) !== null;
    }
}

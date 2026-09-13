<?php

namespace Artwork\Modules\User\Models;

use Artwork\Modules\Shift\Services\ShiftRuleRevalidationService;
use Database\Factories\UserContractAssignFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $user_contract_id
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property int $free_full_days_per_week
 * @property int $free_half_days_per_week
 * @property bool $special_day_rule_active
 * @property int $compensation_period
 * @property int $free_sundays_per_season
 * @property float $days_off_first_26_weeks
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 * @property UserContract $userContract
 */
class UserContractAssign extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return UserContractAssignFactory::new();
    }

    /**
     * Vertragszuweisung erstellt/geändert/gelöscht: Regeln der Person neu prüfen (Queue-Job,
     * nach Commit). Der Cron sieht nur 14 Tage — Vertragswechsel gelten aber für alle geplanten Schichten.
     * Rückwirkende Zeiträume (valid_from < heute) prüfen ab dem frühesten betroffenen Datum,
     * damit festgeschriebene Schichten im Zeitraum neu bewertet werden.
     */
    protected static function booted(): void
    {
        static::saved(function (UserContractAssign $assign): void {
            if ($assign->wasRecentlyCreated || $assign->wasChanged()) {
                app(ShiftRuleRevalidationService::class)
                    ->revalidateForUsers([(int) $assign->user_id], $assign->affectedFrom());
            }
        });

        static::deleted(function (UserContractAssign $assign): void {
            app(ShiftRuleRevalidationService::class)
                ->revalidateForUsers([(int) $assign->user_id], $assign->affectedFrom());
        });
    }

    /**
     * Frühestes Datum, ab dem diese Änderung Schichten betreffen kann: neuer und alter Gültigkeitsbeginn,
     * bei verschobenem Ende auch die beiden Endwerte. Null (offen ab Beginn / nichts ermittelbar) heißt
     * "ab heute" – der Revalidierungs-Service deckelt die Vergangenheit ohnehin.
     */
    public function affectedFrom(): ?Carbon
    {
        $dates = [$this->valid_from, self::toDate($this->getOriginal('valid_from'))];

        if ($this->wasChanged('valid_until') || $this->wasRecentlyCreated || !$this->exists) {
            $dates[] = $this->valid_until;
            $dates[] = self::toDate($this->getOriginal('valid_until'));
        }

        $dates = array_values(array_filter($dates));
        if ($dates === []) {
            return null;
        }

        usort($dates, static fn (Carbon $a, Carbon $b): int => $a <=> $b);

        return $dates[0]->copy()->startOfDay();
    }

    private static function toDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse((string) $value);
    }

    /**
     * Gültig am Stichtag: (valid_from null oder <= Tag) und (valid_until null oder >= Tag).
     */
    public function scopeValidOn(Builder $query, Carbon|string $date): Builder
    {
        $day = ($date instanceof Carbon ? $date : Carbon::parse($date))->toDateString();

        return $query
            ->where(function (Builder $q) use ($day): void {
                $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $day);
            })
            ->where(function (Builder $q) use ($day): void {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $day);
            });
    }

    public function coversDate(Carbon|string $date): bool
    {
        $day = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date))->startOfDay();

        if ($this->valid_from !== null && $this->valid_from->copy()->startOfDay()->gt($day)) {
            return false;
        }

        return $this->valid_until === null || !$this->valid_until->copy()->startOfDay()->lt($day);
    }

    /**
     * Überschneidet sich dieser Zeitraum mit [$from, $until] (null = offen)?
     */
    public function overlaps(?Carbon $from, ?Carbon $until): bool
    {
        $ownFrom = $this->valid_from?->copy()->startOfDay();
        $ownUntil = $this->valid_until?->copy()->startOfDay();

        $startsBeforeOtherEnds = $ownFrom === null || $until === null || $ownFrom->lte($until->copy()->startOfDay());
        $endsAfterOtherStarts = $ownUntil === null || $from === null || $ownUntil->gte($from->copy()->startOfDay());

        return $startsBeforeOtherEnds && $endsAfterOtherStarts;
    }

    protected $fillable = [
        'user_id',
        'user_contract_id',
        'valid_from',
        'valid_until',
        'free_full_days_per_week',
        'free_half_days_per_week',
        'special_day_rule_active',
        'compensation_period',
        'overtime_rule_active',
        'overtime_compensation_period',
        'free_sundays_per_season',
        'days_off_first_26_weeks',
        // Arbeitszeitmuster-Felder (work_time_pattern_id, monday..sunday) gehören zu user_work_times
        // und sind hier KEINE Spalten – UserContractAssignController trennt sie vor dem Update ab.
        // valid_from/valid_until gibt es seit 2026-09 auf BEIDEN Tabellen (eigene Historie je Tabelle).
        // Spielzeitbezogene Infodaten (DP-18)
        'free_sundays_per_season_active',
        'days_off_first_26_weeks_active',
        'free_sundays_sat_mon_per_half',
        'free_sundays_sat_mon_per_half_active',
        'free_sundays_and_saturdays_per_season',
        'free_sundays_and_saturdays_per_season_active',
        'free_sundays_per_calendar_year',
        'free_sundays_per_calendar_year_active',
        'one_and_half_day_combinations',
        'one_and_half_day_combinations_active',
        'annual_vacation_days',
    ];

    protected $casts = [
        'valid_from' => 'date:Y-m-d',
        'valid_until' => 'date:Y-m-d',
        'special_day_rule_active' => 'boolean',
        'overtime_rule_active' => 'boolean',
        'overtime_compensation_period' => 'integer',
        'days_off_first_26_weeks' => 'float',
        'free_full_days_per_week' => 'integer',
        'free_half_days_per_week' => 'integer',
        'compensation_period' => 'integer',
        'free_sundays_per_season' => 'integer',
        // Spielzeitbezogene Infodaten (DP-18)
        'free_sundays_per_season_active' => 'boolean',
        'days_off_first_26_weeks_active' => 'boolean',
        'free_sundays_sat_mon_per_half' => 'integer',
        'free_sundays_sat_mon_per_half_active' => 'boolean',
        'free_sundays_and_saturdays_per_season' => 'integer',
        'free_sundays_and_saturdays_per_season_active' => 'boolean',
        'free_sundays_per_calendar_year' => 'integer',
        'free_sundays_per_calendar_year_active' => 'boolean',
        'one_and_half_day_combinations' => 'integer',
        'one_and_half_day_combinations_active' => 'boolean',
        'annual_vacation_days' => 'integer',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'user_contract_assigns'
        );
    }

    public function userContract(): BelongsTo
    {
        return $this->belongsTo(
            UserContract::class,
            'user_contract_id',
            'id',
            'user_contract_assigns'
        );
    }
}

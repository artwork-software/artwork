<?php

namespace Tests\Concerns;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Carbon\Carbon;

/**
 * Gemeinsame Fixtures für Regel-Engine-Tests: Person mit Vertrag, Regel am Vertrag, Schichten,
 * Sondertage. Die Werte sind bewusst explizit (special_day_rule_active auf der Zuweisung ist NOT NULL
 * DEFAULT 0 — ohne explizites true zählen Sondertage für die Person nicht).
 */
trait CreatesShiftRuleFixtures
{
    protected function userWithContract(
        array $contractAttributes = [],
        array $assignAttributes = []
    ): array {
        $user = User::factory()->create();
        $contract = UserContract::factory()->create(array_merge([
            'special_day_rule_active' => true,
            'compensation_period' => 30,
        ], $contractAttributes));

        UserContractAssign::factory()->create(array_merge([
            'user_id' => $user->id,
            'user_contract_id' => $contract->id,
            'special_day_rule_active' => true,
            'compensation_period' => 30,
        ], $assignAttributes));

        return [$user, $contract];
    }

    protected function ruleForContract(UserContract $contract, string $triggerType, float $value = 0.0, array $attributes = []): ShiftRule
    {
        $rule = ShiftRule::factory()->create(array_merge([
            'trigger_type' => $triggerType,
            'individual_number_value' => $value,
            'is_active' => true,
        ], $attributes));
        $rule->contracts()->sync([$contract->id]);

        return $rule;
    }

    /**
     * Schicht mit zugewiesener Person. $end < $start oder $endDate gesetzt = über Mitternacht.
     */
    protected function shiftFor(
        User $user,
        Carbon $date,
        string $start = '08:00:00',
        string $end = '16:00:00',
        array $attributes = [],
        ?Carbon $endDate = null
    ): Shift {
        $shift = Shift::factory()->create(array_merge([
            'start_date' => $date->toDateString(),
            'end_date' => ($endDate ?? $date)->toDateString(),
            'start' => $start,
            'end' => $end,
            'break_minutes' => 0,
            'shift_group_id' => null,
            'is_committed' => false,
        ], $attributes));

        $qualification = ShiftQualification::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $qualification->id,
            'shift_count' => 1,
        ]);

        return $shift;
    }

    /**
     * Personenindividuelle Zeiten auf der shift_workers-Pivot setzen (gehen in allen Checks vor die Schichtzeit).
     */
    protected function setPivotTimes(
        Shift $shift,
        User $user,
        ?string $startTime,
        ?string $endTime,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): void {
        $shift->users()->updateExistingPivot($user->id, [
            'start_date' => ($startDate ?? Carbon::parse($shift->start_date))->toDateString(),
            'end_date' => ($endDate ?? $startDate ?? Carbon::parse($shift->end_date))->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Schicht mit abweichender Pivot-Zeit der Person: Schicht $shiftStart–$shiftEnd, Person $pivotStart–$pivotEnd.
     */
    protected function shiftWithPivotTimesFor(
        User $user,
        Carbon $date,
        string $shiftStart,
        string $shiftEnd,
        string $pivotStart,
        string $pivotEnd,
        array $attributes = [],
        ?Carbon $pivotEndDate = null
    ): Shift {
        $shift = $this->shiftFor($user, $date, $shiftStart, $shiftEnd, $attributes);
        $this->setPivotTimes($shift, $user, $pivotStart, $pivotEnd, $date, $pivotEndDate ?? $date);

        return $shift->fresh();
    }

    /**
     * Individuelle Zeit der Person (null-Zeiten = ganztägig).
     */
    protected function individualTimeFor(
        User $user,
        Carbon $date,
        ?string $start = '09:00',
        ?string $end = '17:00',
        int $breakMinutes = 0,
        ?Carbon $endDate = null
    ): IndividualTime {
        return $user->individualTimes()->create([
            'title' => 'Individuelle Zeit',
            'start_date' => $date->toDateString(),
            'end_date' => ($endDate ?? $date)->toDateString(),
            'start_time' => $start,
            'end_time' => $end,
            'full_day' => $start === null && $end === null,
            'break_minutes' => $breakMinutes,
        ]);
    }

    /**
     * Überstunden-Eintrag (user_overtimes) für den Regeltyp overtimeDeadline.
     */
    protected function overtimeEntryFor(
        User $user,
        Carbon $bookingDay,
        int $minutes,
        Carbon $deadline,
        string $status = UserOvertime::STATUS_OPEN,
        ?int $remainingMinutes = null
    ): UserOvertime {
        return UserOvertime::create([
            'user_id' => $user->id,
            'date' => $bookingDay->toDateString(),
            'minutes' => $minutes,
            'remaining_minutes' => $remainingMinutes ?? $minutes,
            'paid_out_minutes' => 0,
            'deadline' => $deadline->toDateString(),
            'status' => $status,
        ]);
    }

    /**
     * Spielzeit (playing_time_window) in den GeneralSettings setzen; null/null = nicht konfiguriert.
     */
    protected function configureSeason(?Carbon $start, ?Carbon $end): void
    {
        $settings = app(GeneralSettings::class);
        $settings->playing_time_window_start = $start?->toDateString() ?? '';
        $settings->playing_time_window_end = $end?->toDateString() ?? '';
        $settings->save();
    }

    protected function holiday(Carbon $date, string $name = 'Feiertag', bool $treatAsSpecialDay = true): Holiday
    {
        return Holiday::create([
            'name' => $name,
            'date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'yearly' => false,
            'from_api' => false,
            'treatAsSpecialDay' => $treatAsSpecialDay,
        ]);
    }

    /**
     * Ein Datum in der Zukunft mit gewünschtem Wochentag (Carbon::MONDAY …), mind. 3 Wochen voraus,
     * damit Lookbacks/Wochenfenster der Checks nicht mit "heute" kollidieren.
     */
    protected function futureWeekday(int $weekday, int $weeksAhead = 3): Carbon
    {
        return Carbon::now()->startOfDay()->addWeeks($weeksAhead)->next($weekday);
    }
}

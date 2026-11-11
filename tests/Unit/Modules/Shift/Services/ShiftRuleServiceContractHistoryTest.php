<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Vertragswechsel im Prüfzeitraum: Je Tag gelten die Regeln des an diesem Tag gültigen Vertrags, bei Regeln
 * über mehrere Tage die des Tages, auf den der Verstoß gebucht wird. Heute ist Mittwoch, 11.11.2026; der alte
 * Vertrag gilt bis Sonntag, 15.11., der neue ab Montag, 16.11.
 */
final class ShiftRuleServiceContractHistoryTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(): ShiftRuleService
    {
        return app(ShiftRuleService::class);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function contractPeriod(
        User $user,
        ?string $validFrom,
        ?string $validUntil,
        array $attributes = []
    ): UserContract {
        $contract = UserContract::factory()->create(array_merge([
            'special_day_rule_active' => true,
            'compensation_period' => 30,
        ], $attributes));
        UserContractAssign::factory()->create(array_merge([
            'user_id' => $user->id,
            'user_contract_id' => $contract->id,
            'special_day_rule_active' => true,
            'compensation_period' => 30,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
        ], $attributes));

        return $contract;
    }

    #[Test]
    public function each_day_is_checked_against_the_rules_of_the_contract_valid_that_day(): void
    {
        $user = User::factory()->create();
        $old = $this->contractPeriod($user, null, '2026-11-15');
        $new = $this->contractPeriod($user, '2026-11-16', null);
        $oldRule = $this->ruleForContract($old, 'maxWorkingHoursOnDay', 8.0);
        $newRule = $this->ruleForContract($new, 'maxWorkingHoursOnDay', 10.0);

        $this->shiftFor($user, Carbon::parse('2026-11-12'), '08:00:00', '17:00:00'); // 9 h, alter Vertrag
        $this->shiftFor($user, Carbon::parse('2026-11-17'), '08:00:00', '17:00:00'); // 9 h, neuer Vertrag
        $this->shiftFor($user, Carbon::parse('2026-11-18'), '08:00:00', '19:00:00'); // 11 h, neuer Vertrag

        $this->service()->validateRulesForUser($user, Carbon::parse('2026-11-11'), Carbon::parse('2026-11-24'));

        $violations = ShiftRuleViolation::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(static fn (ShiftRuleViolation $violation): string => $violation->violation_date->toDateString()
                . ':' . $violation->shift_rule_id)
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            '2026-11-12:' . $oldRule->id,
            '2026-11-18:' . $newRule->id,
        ], $violations);
    }

    #[Test]
    public function weekly_rules_use_the_contract_of_the_day_the_violation_is_booked_on(): void
    {
        $user = User::factory()->create();
        $old = $this->contractPeriod($user, null, '2026-11-15', ['free_full_days_per_week' => 1]);
        $new = $this->contractPeriod($user, '2026-11-16', null, ['free_full_days_per_week' => 3]);
        $rule = $this->ruleForContract($old, 'minFreeDaysPerWeek');
        $rule->contracts()->sync([$old->id, $new->id]);

        foreach (['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20'] as $date) {
            $this->shiftFor($user, Carbon::parse($date));
        }

        $this->service()->validateRulesForUser($user, Carbon::parse('2026-11-11'), Carbon::parse('2026-11-24'));

        // Woche 16.–22.11. hat zwei freie Tage: zu wenig für den neuen Vertrag (3), genug für den alten (1)
        $violation = ShiftRuleViolation::query()->where('user_id', $user->id)->sole();
        $this->assertSame('2026-11-22', $violation->violation_date->toDateString());
        $this->assertSame(3, $violation->violation_data['target']);
        $this->assertSame(2, $violation->violation_data['free_days']);
    }

    /**
     * @return list<string> "Datum:Regel-ID" aller Verstöße der Person, sortiert
     */
    private function violationKeys(User $user): array
    {
        return ShiftRuleViolation::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(static fn (ShiftRuleViolation $violation): string => $violation->violation_date->toDateString()
                . ':' . $violation->shift_rule_id)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Austritt am 20.11.: Die Überstunden stammen aus dem Vertrag mit Überstundenregel, ihre Frist (25.11.) liegt
     * nach dem Vertragsende. Die Warnung kommt trotzdem, auch im Lauf nach dem Austritt ohne Vertragstag.
     */
    #[Test]
    public function overtime_deadline_warns_after_the_contract_has_ended(): void
    {
        $user = User::factory()->create();
        $contract = $this->contractPeriod($user, null, '2026-11-20', [
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 90,
        ]);
        $rule = $this->ruleForContract($contract, 'overtimeDeadline', 30.0);
        $this->overtimeEntryFor($user, Carbon::parse('2026-08-27'), 180, Carbon::parse('2026-11-25'));

        $this->service()->validateRulesForUser($user, Carbon::parse('2026-11-21'), Carbon::parse('2026-12-04'));

        $this->assertSame(['2026-11-25:' . $rule->id], $this->violationKeys($user));
    }

    #[Test]
    public function overtime_from_an_earlier_contract_is_judged_by_that_contract(): void
    {
        $user = User::factory()->create();
        $old = $this->contractPeriod($user, null, '2026-10-31', [
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 90,
        ]);
        $this->contractPeriod($user, '2026-11-01', null, ['overtime_rule_active' => false]);
        $rule = $this->ruleForContract($old, 'overtimeDeadline', 30.0);
        $this->overtimeEntryFor($user, Carbon::parse('2026-08-27'), 180, Carbon::parse('2026-11-25'));
        $this->overtimeEntryFor($user, Carbon::parse('2026-11-02'), 60, Carbon::parse('2026-11-30'));

        $this->service()->validateRulesForUser($user, Carbon::parse('2026-11-11'), Carbon::parse('2026-11-24'));

        // Nur der Eintrag aus dem alten Vertrag; der vom 02.11. stammt aus dem Vertrag ohne Regel
        $this->assertSame(['2026-11-25:' . $rule->id], $this->violationKeys($user));
    }

    /**
     * Befristeter Vertrag bis 31.12.: Der letzte Sonntag der ersten Spielzeithälfte (24.01.2027) liegt danach.
     * Es gilt der Vertrag des letzten Vertragstags der Hälfte; das Ziel wird wie im Info-Fenster anteilig
     * gerechnet (20 × 153 von 182 Tagen = 16,8).
     */
    #[Test]
    public function free_sundays_per_half_use_the_last_contract_day_of_the_half(): void
    {
        $this->configureSeason(Carbon::parse('2026-08-01'), Carbon::parse('2027-07-31'));
        $user = User::factory()->create();
        $contract = $this->contractPeriod($user, null, '2026-12-31', [
            'free_sundays_sat_mon_per_half' => 20,
            'free_sundays_sat_mon_per_half_active' => true,
        ]);
        $rule = $this->ruleForContract($contract, 'minFreeSundaysPerSeasonHalf');

        $this->service()->validateRulesForUser($user, Carbon::parse('2026-11-11'), Carbon::parse('2026-11-24'));

        $violation = ShiftRuleViolation::query()->where('user_id', $user->id)->sole();
        $this->assertSame($rule->id, $violation->shift_rule_id);
        $this->assertSame('2027-01-24', $violation->violation_date->toDateString());
        $this->assertSame(1, $violation->violation_data['half']);
        $this->assertSame(17, $violation->violation_data['target']);
    }

    /**
     * Wechsel am 1.1.: Das Jahr 2026 gehört zum alten Vertrag (mit Regel), 2027 zum neuen (ohne). Der Verstoß
     * für 2026 liegt auf dem 27.12. und damit vor dem Prüfzeitraum.
     */
    #[Test]
    public function a_change_on_new_years_day_splits_the_years_between_the_contracts(): void
    {
        $user = User::factory()->create();
        $old = $this->contractPeriod($user, null, '2026-12-31');
        $this->contractPeriod($user, '2027-01-01', null);
        $rule = $this->ruleForContract($old, 'minFreeSundaysPerYear', 60.0);

        $this->service()->validateRulesForUser($user, Carbon::parse('2026-12-28'), Carbon::parse('2027-01-03'));

        $this->assertSame(['2026-12-27:' . $rule->id], $this->violationKeys($user));
    }

    /**
     * Mehrere Wechsel im Jahr, danach kein Vertrag: Es entscheidet der letzte Vertrag des Jahres, nicht ein
     * früherer mit Regel und nicht der Buchungstag (27.12., ohne Vertrag).
     */
    #[Test]
    public function with_several_changes_the_last_contract_of_the_period_decides(): void
    {
        $withRuleAtEnd = User::factory()->create();
        $a = $this->contractPeriod($withRuleAtEnd, null, '2026-04-30');
        $this->contractPeriod($withRuleAtEnd, '2026-05-01', '2026-08-31');
        $c = $this->contractPeriod($withRuleAtEnd, '2026-09-01', '2026-10-31');
        $rule = $this->ruleForContract($a, 'minFreeSundaysPerYear', 60.0);
        $rule->contracts()->sync([$a->id, $c->id]);

        $withoutRuleAtEnd = User::factory()->create();
        $d = $this->contractPeriod($withoutRuleAtEnd, null, '2026-08-31');
        $this->contractPeriod($withoutRuleAtEnd, '2026-09-01', '2026-10-31');
        $rule->contracts()->attach($d->id);

        $this->service()->validateRulesForUser(
            $withRuleAtEnd,
            Carbon::parse('2026-11-11'),
            Carbon::parse('2026-11-24')
        );
        $this->service()->validateRulesForUser(
            $withoutRuleAtEnd,
            Carbon::parse('2026-11-11'),
            Carbon::parse('2026-11-24')
        );

        $this->assertSame(['2026-12-27:' . $rule->id], $this->violationKeys($withRuleAtEnd));
        $this->assertSame([], $this->violationKeys($withoutRuleAtEnd));
    }

    #[Test]
    public function days_without_contract_have_no_rules_and_lose_stale_violations(): void
    {
        $user = User::factory()->create();
        $old = $this->contractPeriod($user, null, '2026-11-15');
        $rule = $this->ruleForContract($old, 'maxWorkingHoursOnDay', 8.0);
        $shift = $this->shiftFor($user, Carbon::parse('2026-11-17'), '08:00:00', '19:00:00');
        $stale = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'violation_date' => '2026-11-17',
            'status' => 'active',
            'is_manual' => false,
        ]);

        $this->service()->validateRulesForUser($user, Carbon::parse('2026-11-11'), Carbon::parse('2026-11-24'));

        $this->assertDatabaseMissing('shift_rule_violations', ['id' => $stale->id]);
        $this->assertSame(0, ShiftRuleViolation::query()->where('user_id', $user->id)->count());
    }
}

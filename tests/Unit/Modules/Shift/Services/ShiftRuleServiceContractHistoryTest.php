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

<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ersatzfrei-Frist (compensation_period): Zuweisung vor Vorlage; 0 auf der Zuweisung
 * (Spalte NOT NULL DEFAULT 0) gilt als "nicht gesetzt" und fällt auf die Vorlage zurück.
 */
final class ShiftRuleServiceCompensationPeriodTest extends TestCase
{
    private function userWithContract(int $assignedPeriod, int $templatePeriod): User
    {
        $user = User::factory()->create(['can_work_shifts' => true]);
        $template = UserContract::create([
            'name' => 'Vorlage',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => $templatePeriod,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);
        UserContractAssign::create([
            'user_id' => $user->id,
            'user_contract_id' => $template->id,
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => $assignedPeriod,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);

        return $user->fresh();
    }

    #[Test]
    public function compensation_period_from_the_assignment_wins_over_the_template(): void
    {
        $user = $this->userWithContract(30, 60);

        $data = app(ShiftRuleService::class)->getCompensationDataForUser($user);

        $this->assertSame(30, $data['compensationPeriod']);
        $this->assertSame(30, app(ContractSettingsResolver::class)->compensationPeriod($user));
    }

    #[Test]
    public function compensation_period_zero_on_the_assignment_falls_back_to_the_template(): void
    {
        $user = $this->userWithContract(0, 60);

        $data = app(ShiftRuleService::class)->getCompensationDataForUser($user);

        $this->assertSame(60, $data['compensationPeriod']);
        $this->assertSame(60, app(ContractSettingsResolver::class)->compensationPeriod($user));
    }

    #[Test]
    public function compensation_period_is_zero_without_an_assignment(): void
    {
        $user = User::factory()->create(['can_work_shifts' => true]);

        $this->assertSame(0, app(ShiftRuleService::class)->getCompensationDataForUser($user)['compensationPeriod']);
    }
}

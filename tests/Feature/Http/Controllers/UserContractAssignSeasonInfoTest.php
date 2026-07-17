<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserContractAssignSeasonInfoTest extends FeatureTestCase
{
    #[Test]
    public function assigning_a_contract_copies_the_season_info_fields_to_the_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $this->actingAs($user);

        $contract = UserContract::create([
            'name' => 'DP-18 Vertrag',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 1,
            'special_day_rule_active' => true,
            'compensation_period' => 90,
            'free_sundays_per_season' => 8,
            'days_off_first_26_weeks' => 10,
            'free_sundays_per_season_active' => true,
            'days_off_first_26_weeks_active' => true,
            'free_sundays_sat_mon_per_half' => 3,
            'free_sundays_sat_mon_per_half_active' => true,
            'free_sundays_and_saturdays_per_season' => 4,
            'free_sundays_and_saturdays_per_season_active' => true,
            'free_sundays_per_calendar_year' => 12,
            'free_sundays_per_calendar_year_active' => true,
            'one_and_half_day_combinations' => 5,
            'one_and_half_day_combinations_active' => true,
            'annual_vacation_days' => 30,
        ]);

        $response = $this->patch(route('user-contract-settings.update-user', ['user' => $user->id]), [
            'user_contract_id' => $contract->id,
            'free_full_days_per_week' => $contract->free_full_days_per_week,
            'free_half_days_per_week' => $contract->free_half_days_per_week,
            'special_day_rule_active' => $contract->special_day_rule_active,
            'compensation_period' => $contract->compensation_period,
            'free_sundays_per_season' => $contract->free_sundays_per_season,
            'days_off_first_26_weeks' => $contract->days_off_first_26_weeks,
            'free_sundays_per_season_active' => true,
            'days_off_first_26_weeks_active' => true,
            'free_sundays_sat_mon_per_half' => 3,
            'free_sundays_sat_mon_per_half_active' => true,
            'free_sundays_and_saturdays_per_season' => 4,
            'free_sundays_and_saturdays_per_season_active' => true,
            'free_sundays_per_calendar_year' => 12,
            'free_sundays_per_calendar_year_active' => true,
            'one_and_half_day_combinations' => 5,
            'one_and_half_day_combinations_active' => true,
            'annual_vacation_days' => 30,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('user_contract_assigns', [
            'user_id' => $user->id,
            'user_contract_id' => $contract->id,
            'free_sundays_per_season_active' => true,
            'days_off_first_26_weeks_active' => true,
            'free_sundays_sat_mon_per_half' => 3,
            'free_sundays_sat_mon_per_half_active' => true,
            'free_sundays_and_saturdays_per_season' => 4,
            'free_sundays_and_saturdays_per_season_active' => true,
            'free_sundays_per_calendar_year' => 12,
            'free_sundays_per_calendar_year_active' => true,
            'one_and_half_day_combinations' => 5,
            'one_and_half_day_combinations_active' => true,
            'annual_vacation_days' => 30,
        ]);
    }

    #[Test]
    public function removing_the_contract_resets_the_season_info_fields(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $this->actingAs($user);

        $user->contract()->create([
            'user_contract_id' => null,
            'free_sundays_per_season_active' => true,
            'free_sundays_sat_mon_per_half' => 3,
            'free_sundays_sat_mon_per_half_active' => true,
            'annual_vacation_days' => 30,
        ]);

        $response = $this->patch(route('user-contract-settings.update-user', ['user' => $user->id]), [
            'user_contract_id' => null,
            'free_sundays_per_season_active' => false,
            'free_sundays_sat_mon_per_half' => 0,
            'free_sundays_sat_mon_per_half_active' => false,
            'annual_vacation_days' => 0,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('user_contract_assigns', [
            'user_id' => $user->id,
            'user_contract_id' => null,
            'free_sundays_per_season_active' => false,
            'free_sundays_sat_mon_per_half' => 0,
            'free_sundays_sat_mon_per_half_active' => false,
            'annual_vacation_days' => 0,
        ]);
    }
}

<?php

namespace Tests\Unit\Modules\User;

use Artwork\Modules\Holidays\Services\SpecialDayService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ContractSettingsResolver mit Stichtag: Werte kommen aus dem am Tag gültigen Vertragszeitraum;
 * ohne Datum wie bisher (heute). Cache je Person UND Tag.
 */
final class ContractSettingsResolverDateTest extends TestCase
{
    private function template(string $name, array $attributes = []): UserContract
    {
        return UserContract::create(array_merge([
            'name' => $name,
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => 30,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ], $attributes));
    }

    private function assign(User $user, UserContract $template, array $attributes = []): UserContractAssign
    {
        return UserContractAssign::create(array_merge([
            'user_id' => $user->id,
            'user_contract_id' => $template->id,
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => 0,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ], $attributes));
    }

    #[Test]
    public function values_follow_the_period_valid_on_the_given_date(): void
    {
        $user = User::factory()->create();
        $old = $this->template('Alt', ['compensation_period' => 30, 'special_day_rule_active' => true]);
        $new = $this->template('Neu', ['compensation_period' => 60, 'special_day_rule_active' => false]);

        $this->assign($user, $old, ['valid_from' => null, 'valid_until' => '2026-06-30']);
        $this->assign($user, $new, [
            'valid_from' => '2026-07-01',
            'valid_until' => null,
            'special_day_rule_active' => false,
            'free_full_days_per_week' => 1,
        ]);

        $resolver = app(ContractSettingsResolver::class);

        // compensation_period 0 auf der Zuweisung → Vorlage des jeweiligen Zeitraums
        $this->assertSame(30, $resolver->compensationPeriod($user, Carbon::parse('2026-06-30')));
        $this->assertSame(60, $resolver->compensationPeriod($user, Carbon::parse('2026-07-01')));

        $this->assertTrue($resolver->bool($user, 'special_day_rule_active', true, Carbon::parse('2026-01-15')));
        $this->assertFalse($resolver->bool($user, 'special_day_rule_active', true, Carbon::parse('2026-12-24')));

        $this->assertSame(2, $resolver->int($user, 'free_full_days_per_week', 0, Carbon::parse('2026-03-01')));
        $this->assertSame(1, $resolver->int($user, 'free_full_days_per_week', 0, Carbon::parse('2026-09-01')));

        $this->assertSame('Alt', $resolver->templateFor($user, Carbon::parse('2026-02-01'))?->name);
        $this->assertSame('Neu', $resolver->templateFor($user, Carbon::parse('2027-02-01'))?->name);
    }

    #[Test]
    public function without_a_date_the_period_valid_today_is_used(): void
    {
        $user = User::factory()->create();
        $current = $this->template('Heute', ['compensation_period' => 21]);
        $future = $this->template('Zukunft', ['compensation_period' => 77]);

        $this->assign($user, $current, ['valid_from' => null, 'valid_until' => Carbon::today()->addDays(9)]);
        $this->assign($user, $future, ['valid_from' => Carbon::today()->addDays(10), 'valid_until' => null]);

        $resolver = app(ContractSettingsResolver::class);

        $this->assertSame(21, $resolver->compensationPeriod($user));
        $this->assertSame('Heute', $resolver->templateFor($user)?->name);
        $this->assertSame(77, $resolver->compensationPeriod($user, Carbon::today()->addDays(10)));
    }

    #[Test]
    public function a_gap_without_contract_returns_the_defaults(): void
    {
        $user = User::factory()->create();
        $this->assign($user, $this->template('Befristet'), ['valid_from' => '2026-01-01', 'valid_until' => '2026-03-31']);

        $resolver = app(ContractSettingsResolver::class);

        $this->assertNull($resolver->assignFor($user, Carbon::parse('2026-04-01')));
        $this->assertSame(0, $resolver->compensationPeriod($user, Carbon::parse('2026-04-01')));
        $this->assertSame(30, $resolver->compensationPeriod($user, Carbon::parse('2026-03-31')));
        $this->assertTrue($resolver->bool($user, 'special_day_rule_active', true, Carbon::parse('2026-04-01')));
    }

    #[Test]
    public function zero_in_not_null_default_columns_of_the_assignment_falls_back_to_the_template(): void
    {
        $user = User::factory()->create();
        $template = $this->template('Vorlage', [
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 1,
            'annual_vacation_days' => 30,
            'free_sundays_per_calendar_year' => 15,
            'days_off_first_26_weeks' => 4.5,
            'compensation_period' => 30,
        ]);
        $this->assign($user, $template, [
            'valid_from' => null,
            'valid_until' => null,
            'free_full_days_per_week' => 0,   // 0 = nicht gesetzt → Vorlage 2
            'free_half_days_per_week' => 3,   // gesetzt → Zuweisung gewinnt
            'annual_vacation_days' => 0,      // → Vorlage 30
            'free_sundays_per_calendar_year' => 0,
            'days_off_first_26_weeks' => 0,
            'compensation_period' => 0,
        ]);

        $resolver = app(ContractSettingsResolver::class);

        $this->assertSame(2, $resolver->int($user, 'free_full_days_per_week'));
        $this->assertSame(3, $resolver->int($user, 'free_half_days_per_week'));
        $this->assertSame(30, $resolver->int($user, 'annual_vacation_days'));
        $this->assertSame(15, $resolver->int($user, 'free_sundays_per_calendar_year'));
        $this->assertEqualsWithDelta(4.5, $resolver->float($user, 'days_off_first_26_weeks'), 0.001);
        $this->assertSame(30, $resolver->compensationPeriod($user));
        $this->assertSame(30, $resolver->int($user, 'compensation_period'));

        foreach (['free_full_days_per_week', 'annual_vacation_days', 'compensation_period'] as $key) {
            $this->assertContains($key, ContractSettingsResolver::ZERO_MEANS_UNSET_ON_ASSIGN);
        }
    }

    #[Test]
    public function zero_on_the_template_stays_zero_and_the_default_applies_only_without_contract(): void
    {
        $user = User::factory()->create();
        $template = $this->template('Null-Vorlage', ['free_full_days_per_week' => 0, 'annual_vacation_days' => 0]);
        $this->assign($user, $template, ['valid_from' => null, 'valid_until' => null, 'free_full_days_per_week' => 0]);

        $resolver = app(ContractSettingsResolver::class);

        // Vorlage ist die letzte Stufe: dort ist 0 ein echter Wert (kein Default 7)
        $this->assertSame(0, $resolver->int($user, 'free_full_days_per_week', 7));
        $this->assertSame(0, $resolver->int($user, 'annual_vacation_days', 7));

        // ohne Vertragszeitraum greift der Default
        $this->assertSame(7, $resolver->int(User::factory()->create(), 'free_full_days_per_week', 7));
    }

    #[Test]
    public function nullable_and_boolean_columns_keep_their_rules(): void
    {
        $user = User::factory()->create();
        $template = $this->template('Vorlage', [
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 45,
            'special_day_rule_active' => true,
        ]);
        $this->assign($user, $template, [
            'valid_from' => null,
            'valid_until' => null,
            'overtime_rule_active' => false,       // Bool: Zuweisung gilt immer (false ≠ "nicht gesetzt")
            'overtime_compensation_period' => null, // nullable: null → Vorlage
            'special_day_rule_active' => false,
        ]);

        $resolver = app(ContractSettingsResolver::class);

        $this->assertSame(45, $resolver->int($user, 'overtime_compensation_period'));
        $this->assertFalse($resolver->bool($user, 'overtime_rule_active', true));
        $this->assertFalse($resolver->bool($user, 'special_day_rule_active', true));
        $this->assertNotContains('overtime_compensation_period', ContractSettingsResolver::ZERO_MEANS_UNSET_ON_ASSIGN);
    }

    #[Test]
    public function the_cache_is_keyed_by_person_and_date(): void
    {
        $user = User::factory()->create();
        $this->assign($user, $this->template('Alt', ['compensation_period' => 30]), [
            'valid_from' => null,
            'valid_until' => '2026-06-30',
        ]);
        $this->assign($user, $this->template('Neu', ['compensation_period' => 60]), [
            'valid_from' => '2026-07-01',
            'valid_until' => null,
        ]);

        $resolver = new ContractSettingsResolver();

        $this->assertSame(30, $resolver->compensationPeriod($user, Carbon::parse('2026-06-01')));
        $this->assertSame(60, $resolver->compensationPeriod($user, Carbon::parse('2026-08-01')));
        // erneuter Aufruf desselben Tages liefert den gecachten Satz (kein Umschlag auf den anderen Zeitraum)
        $this->assertSame(30, $resolver->compensationPeriod($user, Carbon::parse('2026-06-01')));
    }

    #[Test]
    public function special_day_service_reads_the_switch_of_the_period_valid_on_that_day(): void
    {
        $user = User::factory()->create();
        $this->assign($user, $this->template('Alt', ['special_day_rule_active' => true]), [
            'valid_from' => null,
            'valid_until' => '2026-06-30',
            'special_day_rule_active' => true,
        ]);
        $this->assign($user, $this->template('Neu', ['special_day_rule_active' => false]), [
            'valid_from' => '2026-07-01',
            'valid_until' => null,
            'special_day_rule_active' => false,
        ]);

        $service = app(SpecialDayService::class);

        $this->assertTrue($service->specialDayRuleActiveFor($user, '2026-05-01'));
        $this->assertFalse($service->specialDayRuleActiveFor($user, '2026-10-03'));
    }
}

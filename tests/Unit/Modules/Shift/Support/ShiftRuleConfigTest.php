<?php

namespace Tests\Unit\Modules\Shift\Support;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Support\ShiftRuleConfig;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Zusatzparameter je Regeltyp: Standardwerte, Normalisierung, Vergleich und Validierung.
 */
final class ShiftRuleConfigTest extends TestCase
{
    #[Test]
    public function rule_types_without_additional_settings_have_no_config(): void
    {
        $this->assertFalse(ShiftRuleConfig::hasSchema('maxWorkingHoursOnDay'));
        $this->assertNull(ShiftRuleConfig::normalize('maxWorkingHoursOnDay', ['anything' => 1]));
        $this->assertSame([], ShiftRuleConfig::defaults('maxWorkingHoursOnDay'));
        $this->assertNull(ShiftRuleConfig::normalize(null, null));
    }

    #[Test]
    public function missing_config_normalizes_to_the_defaults(): void
    {
        $this->assertSame(
            ['weekday' => 4, 'time' => '14:00', 'weeks_ahead' => 1],
            ShiftRuleConfig::normalize('weeklyPlanCommitDeadline', null)
        );
        $this->assertSame(
            ['workdays_only' => false, 'excluded_dates' => []],
            ShiftRuleConfig::normalize('minFreeDaysPerWeek', [])
        );
        $this->assertSame(['skip_same_day' => false], ShiftRuleConfig::normalize('restTimeBeforeWorkday', null));
    }

    #[Test]
    public function normalize_casts_drops_unknown_keys_and_cleans_lists(): void
    {
        $config = ShiftRuleConfig::normalize('minFreeDaysPerWeek', [
            'workdays_only' => '1',
            'excluded_dates' => ['12-24', '05-01', '12-24', 'kaputt', '13-40'],
            'unknown' => 'x',
        ]);

        $this->assertSame(['workdays_only' => true, 'excluded_dates' => ['05-01', '12-24']], $config);

        $config = ShiftRuleConfig::normalize('halfDayOffMaxHours', [
            'boundary' => '25:99',
            'extended_hours' => '',
            'extended_shift_group_ids' => ['7', 3, 3, 'x'],
        ]);

        $this->assertSame(
            ['boundary' => '14:00', 'extended_hours' => null, 'extended_shift_group_ids' => [3, 7]],
            $config
        );
    }

    #[Test]
    public function integers_are_clamped_to_their_range(): void
    {
        $config = ShiftRuleConfig::normalize('weeklyPlanCommitDeadline', ['weekday' => 9, 'weeks_ahead' => 0]);

        $this->assertSame(7, $config['weekday']);
        $this->assertSame(1, $config['weeks_ahead']);
    }

    #[Test]
    public function get_reads_the_rule_value_or_the_default(): void
    {
        $withoutConfig = new ShiftRule(['trigger_type' => 'weeklyPlanCommitDeadline']);
        $withConfig = new ShiftRule(['trigger_type' => 'weeklyPlanCommitDeadline', 'config' => ['weekday' => 2]]);

        $this->assertSame(4, ShiftRuleConfig::get($withoutConfig, 'weekday'));
        $this->assertSame(2, ShiftRuleConfig::get($withConfig, 'weekday'));
        $this->assertSame('14:00', ShiftRuleConfig::get($withConfig, 'time'));
        $this->assertNull(ShiftRuleConfig::get(new ShiftRule(['trigger_type' => 'workOnSunday']), 'weekday'));
    }

    #[Test]
    public function equals_treats_null_and_defaults_as_the_same(): void
    {
        $this->assertTrue(ShiftRuleConfig::equals('restTimeBeforeWorkday', null, ['skip_same_day' => false]));
        $this->assertFalse(ShiftRuleConfig::equals('restTimeBeforeWorkday', null, ['skip_same_day' => true]));
        $this->assertTrue(ShiftRuleConfig::equals(
            'minFreeDaysPerWeek',
            ['workdays_only' => true, 'excluded_dates' => ['12-24', '05-01']],
            ['excluded_dates' => ['05-01', '12-24'], 'workdays_only' => 1]
        ));
        $this->assertTrue(ShiftRuleConfig::equals('maxWorkingHoursOnDay', null, ['x' => 1]));
    }

    #[Test]
    public function validation_rules_accept_valid_and_reject_invalid_values(): void
    {
        $rules = ShiftRuleConfig::validationRules('weeklyPlanCommitDeadline');

        $this->assertTrue(Validator::make(
            ['config' => ['weekday' => 4, 'time' => '14:00', 'weeks_ahead' => 1]],
            $rules
        )->passes());
        $this->assertTrue(Validator::make([], $rules)->passes());
        $this->assertFalse(Validator::make(['config' => ['weekday' => 8]], $rules)->passes());
        $this->assertFalse(Validator::make(['config' => ['time' => '2 Uhr']], $rules)->passes());

        $rules = ShiftRuleConfig::validationRules('freeDayAfterWorkOnDate');
        $this->assertTrue(Validator::make(['config' => ['worked' => '12-24', 'free' => '05-01']], $rules)->passes());
        $this->assertFalse(Validator::make(['config' => ['worked' => '12-24']], $rules)->passes());
        $this->assertFalse(Validator::make(['config' => ['worked' => '24.12.', 'free' => '05-01']], $rules)->passes());

        $rules = ShiftRuleConfig::validationRules('minFreeDaysPerWeek');
        $this->assertFalse(Validator::make(['config' => ['excluded_dates' => ['1.5.']]], $rules)->passes());
        $this->assertTrue(Validator::make(['config' => ['excluded_dates' => ['05-01']]], $rules)->passes());
    }

    #[Test]
    public function calendar_days_must_exist_and_differ(): void
    {
        $validate = static fn (array $config): bool => Validator::make(
            ['config' => $config],
            ShiftRuleConfig::validationRules('freeDayAfterWorkOnDate')
        )->passes();

        $this->assertTrue($validate(['worked' => '12-24', 'free' => '05-01']));
        $this->assertTrue($validate(['worked' => '02-29', 'free' => '05-01']));
        $this->assertFalse($validate(['worked' => '02-30', 'free' => '05-01']));
        $this->assertFalse($validate(['worked' => '04-31', 'free' => '05-01']));
        $this->assertFalse($validate(['worked' => '05-01', 'free' => '05-01']));
    }

    #[Test]
    public function ids_of_deleted_shift_groups_do_not_block_saving(): void
    {
        $validator = Validator::make(
            ['config' => ['excluded_shift_group_ids' => [999999]]],
            ShiftRuleConfig::validationRules('restTimeBetweenShiftsOnDay')
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function id_lists_are_ignored_when_comparing_rules_of_a_rule_set(): void
    {
        $this->assertFalse(ShiftRuleConfig::equals(
            'restTimeBetweenShiftsOnDay',
            ['excluded_shift_group_ids' => [3]],
            ['excluded_shift_group_ids' => []]
        ));
        $this->assertTrue(ShiftRuleConfig::equalsIgnoringIdLists(
            'restTimeBetweenShiftsOnDay',
            ['excluded_shift_group_ids' => [3]],
            ['excluded_shift_group_ids' => []]
        ));
        $this->assertFalse(ShiftRuleConfig::equalsIgnoringIdLists(
            'halfDayOffMaxHours',
            ['boundary' => '13:00'],
            ['boundary' => '14:00']
        ));
    }
}

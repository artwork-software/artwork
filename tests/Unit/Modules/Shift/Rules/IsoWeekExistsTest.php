<?php

namespace Tests\Unit\Modules\Shift\Rules;

use Artwork\Modules\Shift\Rules\IsoWeekExists;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * IsoWeekExists: KW 53 nur in 53-Wochen-Jahren; Jahr als Geschwisterfeld (auch verschachtelt)
 * oder aus dem Array-Element; Typ-/Bereichsfehler bleiben den Standardregeln überlassen.
 */
final class IsoWeekExistsTest extends TestCase
{
    #[Test]
    public function week_53_fails_in_a_52_week_year_and_passes_in_a_53_week_year(): void
    {
        $rules = ['week_number' => ['required', 'integer', 'min:1', 'max:53', new IsoWeekExists('year')]];

        $failing = Validator::make(['week_number' => 53, 'year' => 2025], $rules);
        $this->assertTrue($failing->fails());
        $this->assertSame(
            'Calendar week 53 does not exist in 2025.',
            $failing->errors()->first('week_number')
        );

        $this->assertTrue(Validator::make(['week_number' => 53, 'year' => 2026], $rules)->passes());
        $this->assertTrue(Validator::make(['week_number' => 52, 'year' => 2025], $rules)->passes());
    }

    #[Test]
    public function year_field_name_is_configurable(): void
    {
        $rules = ['source_week' => [new IsoWeekExists('source_year')]];

        $this->assertTrue(Validator::make(['source_week' => 53, 'source_year' => 2025], $rules)->fails());
        $this->assertTrue(Validator::make(['source_week' => 53, 'source_year' => 2026], $rules)->passes());
    }

    #[Test]
    public function sibling_year_is_resolved_for_nested_attributes(): void
    {
        $validator = Validator::make(
            ['targets' => [['week' => 53, 'year' => 2025], ['week' => 53, 'year' => 2026]]],
            ['targets.*.week' => [new IsoWeekExists('year')]]
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('targets.0.week'));
        $this->assertFalse($validator->errors()->has('targets.1.week'));
    }

    #[Test]
    public function array_element_mode_attaches_the_error_to_the_element(): void
    {
        $validator = Validator::make(
            ['targets' => [['week' => 2, 'year' => 2026], ['week' => 53, 'year' => 2025]]],
            ['targets.*' => ['array', new IsoWeekExists('year', 'week')]]
        );

        $this->assertTrue($validator->fails());
        $this->assertFalse($validator->errors()->has('targets.0'));
        $this->assertSame(
            'Calendar week 53 does not exist in 2025.',
            $validator->errors()->first('targets.1')
        );
    }

    #[Test]
    public function rule_stays_silent_for_non_numeric_or_out_of_range_input(): void
    {
        $rules = ['week_number' => [new IsoWeekExists('year')]];

        // Typ-/Bereichsfehler melden integer/min/max — hier keine zweite Meldung
        $this->assertTrue(Validator::make(['week_number' => 'abc', 'year' => 2025], $rules)->passes());
        $this->assertTrue(Validator::make(['week_number' => 54, 'year' => 2025], $rules)->passes());
        $this->assertTrue(Validator::make(['week_number' => 0, 'year' => 2025], $rules)->passes());
        $this->assertTrue(Validator::make(['week_number' => 53], $rules)->passes());
        $this->assertTrue(Validator::make(['week_number' => 53, 'year' => 'x'], $rules)->passes());
    }
}

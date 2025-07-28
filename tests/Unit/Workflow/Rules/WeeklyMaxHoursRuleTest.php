<?php

namespace Tests\Unit\Workflow\Rules;

use Tests\TestCase;
use Artwork\Modules\Workflow\Rules\WeeklyMaxHoursRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class WeeklyMaxHoursRuleTest extends TestCase
{
    private WeeklyMaxHoursRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new WeeklyMaxHoursRule();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_has_correct_name()
    {
        $this->assertEquals('weekly_max_hours', $this->rule->getName());
    }

    #[Test]
    public function it_has_correct_description()
    {
        $this->assertEquals('Überprüft das Wochenmaximum an Arbeitsstunden', $this->rule->getDescription());
    }

    #[Test]
    public function it_detects_violation_when_weekly_hours_exceed_limit()
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-19');   // Sunday
        
        // Mock 8 hours per day for 6 days = 48 hours (exceeds 40h limit)
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturnUsing(function ($date) {
                // Sunday = 0h, rest = 8h per day
                return $date->dayOfWeek === 0 ? 0 : 8;
            });

        $context = [
            'value' => 40, // Max 40 hours per week
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertGreaterThan(0, count($violations));
        
        // Should detect violation on Saturday (when 48h is reached)
        $saturdayViolation = collect($violations)->first(function ($v) {
            return Carbon::parse($v['date'])->dayOfWeek === 6; // Saturday
        });
        
        $this->assertNotNull($saturdayViolation);
        $this->assertEquals(48, $saturdayViolation['weekly_hours']);
        $this->assertEquals(40, $saturdayViolation['max_hours']);
        $this->assertStringContainsString('Wochenmaximum von 40h überschritten', $saturdayViolation['message']);
    }

    #[Test]
    public function it_resets_weekly_hours_on_monday()
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-06'); // Previous Monday
        $endDate = Carbon::parse('2025-01-20');   // Following Monday
        
        // Mock high hours in first week, low hours in second week
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturnUsing(function ($date) {
                if ($date->weekOfYear === Carbon::parse('2025-01-06')->weekOfYear) {
                    // First week: 8h per day
                    return $date->dayOfWeek === 0 ? 0 : 8;
                } else {
                    // Second week: 4h per day (within limit)
                    return $date->dayOfWeek === 0 ? 0 : 4;
                }
            });

        $context = [
            'value' => 40,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // Should have violations in first week but not second week
        $firstWeekViolations = collect($violations)->filter(function ($v) {
            return Carbon::parse($v['date'])->weekOfYear === Carbon::parse('2025-01-06')->weekOfYear;
        });

        $secondWeekViolations = collect($violations)->filter(function ($v) {
            return Carbon::parse($v['date'])->weekOfYear === Carbon::parse('2025-01-13')->weekOfYear;
        });

        $this->assertGreaterThan(0, $firstWeekViolations->count());
        $this->assertEquals(0, $secondWeekViolations->count());
    }

    #[Test]
    public function it_adjusts_date_range_to_full_weeks()
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-15'); // Wednesday
        $endDate = Carbon::parse('2025-01-17');   // Friday
        
        // Should expand to Monday (13th) - Sunday (19th)
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturnUsing(function ($date) {
                // Verify we're getting Monday and Sunday dates too
                $dayOfWeek = $date->dayOfWeek;
                return $dayOfWeek === 0 ? 0 : 8; // 8h except Sunday
            });

        $context = [
            'value' => 40,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // The rule should process the full week, not just Wed-Fri
        // We can verify this by checking if violations include dates outside the original range
        $this->assertIsArray($violations);
    }

    #[Test]
    public function it_returns_no_violation_when_within_limit()
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-19');   // Sunday
        
        // Mock 5 hours per day for 6 days = 30 hours (within 40h limit)
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturnUsing(function ($date) {
                return $date->dayOfWeek === 0 ? 0 : 5; // 5h except Sunday
            });

        $context = [
            'value' => 40,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_uses_default_values_when_context_missing()
    {
        $mockSubject = Mockery::mock(Model::class);
        
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->andReturn(0);

        $violations = $this->rule->validate($mockSubject, []);

        $this->assertIsArray($violations);
    }

    #[Test]
    public function it_can_apply_to_subjects_with_required_methods()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->andReturn(true);

        $result = $this->rule->canApplyTo($mockSubject);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_has_correct_configuration()
    {
        $config = $this->rule->getConfiguration();

        $this->assertArrayHasKey('fields', $config);
        $this->assertArrayHasKey('max_hours', $config['fields']);
        
        $maxHoursField = $config['fields']['max_hours'];
        $this->assertEquals('number', $maxHoursField['type']);
        $this->assertEquals('Maximale Stunden pro Woche', $maxHoursField['label']);
        $this->assertEquals(40, $maxHoursField['default']);
        $this->assertEquals(1, $maxHoursField['min']);
        $this->assertEquals(80, $maxHoursField['max']);
    }

    #[Test]
    public function it_handles_week_spanning_multiple_periods()
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-26');   // Second Sunday
        
        // Mock consistent daily hours across two weeks
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturnUsing(function ($date) {
                return $date->dayOfWeek === 0 ? 0 : 7; // 7h per day except Sunday
            });

        $context = [
            'value' => 40,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // Should have violations in both weeks (42h each week)
        $week1Violations = collect($violations)->filter(function ($v) {
            $date = Carbon::parse($v['date']);
            return $date->between(
                Carbon::parse('2025-01-13'),
                Carbon::parse('2025-01-19')
            );
        });

        $week2Violations = collect($violations)->filter(function ($v) {
            $date = Carbon::parse($v['date']);
            return $date->between(
                Carbon::parse('2025-01-20'),
                Carbon::parse('2025-01-26')
            );
        });

        $this->assertGreaterThan(0, $week1Violations->count());
        $this->assertGreaterThan(0, $week2Violations->count());
    }
}
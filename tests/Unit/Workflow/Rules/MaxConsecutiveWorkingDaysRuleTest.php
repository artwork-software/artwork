<?php

namespace Tests\Unit\Workflow\Rules;

use Tests\TestCase;
use Artwork\Modules\Workflow\Rules\MaxConsecutiveWorkingDaysRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class MaxConsecutiveWorkingDaysRuleTest extends TestCase
{
    private MaxConsecutiveWorkingDaysRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new MaxConsecutiveWorkingDaysRule();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function testHasCorrectName(): void
    {
        $this->assertEquals('max_consecutive_working_days', $this->rule->getName());
    }

    #[Test]
    public function testHasCorrectDescription(): void
    {
        $this->assertEquals('Überprüft die maximale Anzahl aufeinanderfolgender Arbeitstage', $this->rule->getDescription());
    }

    #[Test]
    public function testDetectsViolationWhenConsecutiveDaysExceedLimit(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-19');   // Sunday

        // Mock 6 consecutive working days (Mon-Sat)
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturnUsing(function ($date) use ($startDate) {
                $dayOfWeek = $date->dayOfWeek;
                // Return 8 hours for Mon-Sat (1-6), 0 for Sunday (0)
                return $dayOfWeek === 0 ? 0 : 8;
            });

        $context = [
            'value' => 5, // Max 5 consecutive days
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // Should detect violation on Saturday (6th consecutive day)
        $this->assertGreaterThan(0, count($violations));

        $violation = $violations[0];
        $this->assertEquals(6, $violation['consecutive_days']);
        $this->assertEquals(5, $violation['max_days']);
        $this->assertStringContainsString('Zu viele aufeinanderfolgende Arbeitstage', $violation['message']);
    }

    #[Test]
    public function testResetsCounterOnRestDay(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-19');   // Sunday

        // Mock: Work Mon-Wed, Rest Thu, Work Fri-Sun
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturnUsing(function ($date) {
                $dayOfWeek = $date->dayOfWeek;
                // Mon(1), Tue(2), Wed(3) = 8h, Thu(4) = 0h, Fri(5), Sat(6), Sun(0) = 8h
                return $dayOfWeek === 4 ? 0 : 8;
            });

        $context = [
            'value' => 5,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // Should not detect violations since consecutive days are reset by Thursday rest
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testReturnsNoViolationWhenWithinLimit(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-17');   // Friday

        // Mock 5 consecutive working days (exactly at limit)
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturn(8);

        $context = [
            'value' => 5,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testUsesDefaultValuesWhenContextMissing(): void
    {
        $mockSubject = Mockery::mock(Model::class);

        // Mock method to avoid issues with default date range
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->andReturn(0);

        $violations = $this->rule->validate($mockSubject, []);

        // Should not throw errors and use defaults
        $this->assertIsArray($violations);
    }

    #[Test]
    public function testHandlesZeroWorkingHoursCorrectly(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-13');
        $endDate = Carbon::parse('2025-01-17');

        // All days have zero working hours
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturn(0);

        $context = [
            'value' => 3,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testCanApplyToSubjectsWithRequiredMethods(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->andReturn(true);

        $result = $this->rule->canApplyTo($mockSubject);

        $this->assertTrue($result);
    }

    #[Test]
    public function testHasCorrectConfiguration(): void
    {
        $config = $this->rule->getConfiguration();

        $this->assertArrayHasKey('fields', $config);
        $this->assertArrayHasKey('max_days', $config['fields']);

        $maxDaysField = $config['fields']['max_days'];
        $this->assertEquals('number', $maxDaysField['type']);
        $this->assertEquals('Maximale aufeinanderfolgende Arbeitstage', $maxDaysField['label']);
        $this->assertEquals(5, $maxDaysField['default']);
        $this->assertEquals(1, $maxDaysField['min']);
        $this->assertEquals(14, $maxDaysField['max']);
    }

    #[Test]
    public function testTracksConsecutiveDaysCorrectlyAcrossWeekBoundary(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::parse('2025-01-11'); // Saturday
        $endDate = Carbon::parse('2025-01-20');   // Following Monday

        // Work Sat, Sun, Mon, Tue, Wed, Thu (6 consecutive days)
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with(Mockery::type(Carbon::class))
            ->andReturnUsing(function ($date) {
                // Friday = rest, Sat-Thu = work
                return $date->dayOfWeek === 5 ? 0 : 8;
            });

        $context = [
            'value' => 5,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertGreaterThan(0, count($violations));
        $violation = $violations[0];
        $this->assertEquals(6, $violation['consecutive_days']);
    }
}

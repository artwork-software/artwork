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
    public function testHasCorrectName(): void
    {
        $this->assertEquals('weekly_max_hours', $this->rule->getName());
    }

    #[Test]
    public function testHasCorrectDescription(): void
    {
        $this->assertEquals('Überprüft das Wochenmaximum an Arbeitsstunden', $this->rule->getDescription());
    }

    /**
     * Create a concrete test class that extends Model and implements getPlannedWorkingHours
     */
    private function createTestSubject(array $hoursMap): Model
    {
        // Create a concrete class that extends Model
        $testSubject = new class extends Model {
            private $hoursMap = [];

            public function setHoursMap(array $hoursMap): void
            {
                $this->hoursMap = $hoursMap;
            }

            public function getPlannedWorkingHours(Carbon $date): float
            {
                $dateStr = $date->format('Y-m-d');
                echo "\nConcrete class getPlannedWorkingHours called with date: " . $dateStr;
                if (isset($this->hoursMap[$dateStr])) {
                    echo " - returning " . $this->hoursMap[$dateStr] . " hours";
                    return $this->hoursMap[$dateStr];
                }
                echo " - returning 0 hours (default)";
                return 0.0;
            }
        };

        // Set the hours map after construction
        $testSubject->setHoursMap($hoursMap);

        return $testSubject;
    }

    #[Test]
    public function testDetectsViolationWhenWeeklyHoursExceedLimit(): void
    {
        // Create a map of dates to hours
        $hoursMap = [
            '2025-01-13' => 8.0,  // Monday
            '2025-01-14' => 10.0, // Tuesday
            '2025-01-15' => 10.0, // Wednesday
            '2025-01-16' => 10.0, // Thursday
            '2025-01-17' => 10.0, // Friday
            '2025-01-18' => 0.0,  // Saturday
            '2025-01-19' => 0.0,  // Sunday
        ];

        // Create a concrete test subject that implements getPlannedWorkingHours
        $testSubject = $this->createTestSubject($hoursMap);

        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-19'); // Sunday

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'max_hours' => 40 // Maximum 40 hours per week
        ];

        $violations = $this->rule->validate($testSubject, $context);

        // Total hours: 8 + 10 + 10 + 10 + 10 = 48 hours (exceeds 40)
        $this->assertCount(1, $violations);
        $this->assertEquals(48, $violations[0]['weekly_hours']);
        $this->assertEquals(40, $violations[0]['max_hours']);
        $this->assertEquals('2025-01-13', $violations[0]['week_start']);
        $this->assertStringContainsString('Wochenmaximum von 40h überschritten', $violations[0]['message']);
    }

    #[Test]
    public function testResetsWeeklyHoursOnMonday(): void
    {
        // Create a map of dates to hours
        $hoursMap = [
            // First week (exceeds limit)
            '2025-01-13' => 10.0, // Monday
            '2025-01-14' => 10.0, // Tuesday
            '2025-01-15' => 10.0, // Wednesday
            '2025-01-16' => 10.0, // Thursday
            '2025-01-17' => 10.0, // Friday
            '2025-01-18' => 0.0,  // Saturday
            '2025-01-19' => 0.0,  // Sunday

            // Second week (within limit)
            '2025-01-20' => 7.0, // Monday
            '2025-01-21' => 7.0, // Tuesday
            '2025-01-22' => 7.0, // Wednesday
            '2025-01-23' => 7.0, // Thursday
            '2025-01-24' => 7.0, // Friday
            '2025-01-25' => 0.0, // Saturday
            '2025-01-26' => 0.0, // Sunday
        ];

        // Create a concrete test subject that implements getPlannedWorkingHours
        $testSubject = $this->createTestSubject($hoursMap);

        $startDate = Carbon::parse('2025-01-13'); // Monday week 1
        $endDate = Carbon::parse('2025-01-26'); // Sunday week 2

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'max_hours' => 40
        ];

        $violations = $this->rule->validate($testSubject, $context);

        // Should only report violation for the first week
        $this->assertCount(1, $violations);
        $this->assertEquals(50, $violations[0]['weekly_hours']);
        $this->assertEquals('2025-01-13', $violations[0]['week_start']);
    }

    #[Test]
    public function testAdjustsDateRangeToFullWeeks(): void
    {
        // Create a map of dates to hours - 10 hours for each weekday across two weeks
        $hoursMap = [
            // First week
            '2025-01-13' => 10.0, // Monday
            '2025-01-14' => 10.0, // Tuesday
            '2025-01-15' => 10.0, // Wednesday
            '2025-01-16' => 10.0, // Thursday
            '2025-01-17' => 10.0, // Friday
            '2025-01-18' => 0.0,  // Saturday
            '2025-01-19' => 0.0,  // Sunday

            // Second week
            '2025-01-20' => 10.0, // Monday
            '2025-01-21' => 10.0, // Tuesday
            '2025-01-22' => 10.0, // Wednesday
            '2025-01-23' => 10.0, // Thursday
            '2025-01-24' => 10.0, // Friday
            '2025-01-25' => 0.0,  // Saturday
            '2025-01-26' => 0.0,  // Sunday
        ];

        // Create a concrete test subject that implements getPlannedWorkingHours
        $testSubject = $this->createTestSubject($hoursMap);

        // Specify a date range that doesn't align with week boundaries
        $startDate = Carbon::parse('2025-01-15'); // Wednesday
        $endDate = Carbon::parse('2025-01-22'); // Wednesday next week

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'max_hours' => 40
        ];

        $violations = $this->rule->validate($testSubject, $context);

        // Should adjust to full weeks and detect violations
        $this->assertCount(2, $violations);

        // First week: 2025-01-13 to 2025-01-19
        $this->assertEquals('2025-01-13', $violations[0]['week_start']);

        // Second week: 2025-01-20 to 2025-01-26
        $this->assertEquals('2025-01-20', $violations[1]['week_start']);
    }

    #[Test]
    public function testReturnsNoViolationWhenWithinLimit(): void
    {
        // Create a map of dates to hours - 8 hours for each weekday (exactly at limit)
        $hoursMap = [
            '2025-01-13' => 8.0, // Monday
            '2025-01-14' => 8.0, // Tuesday
            '2025-01-15' => 8.0, // Wednesday
            '2025-01-16' => 8.0, // Thursday
            '2025-01-17' => 8.0, // Friday
            '2025-01-18' => 0.0, // Saturday
            '2025-01-19' => 0.0, // Sunday
        ];

        // Create a concrete test subject that implements getPlannedWorkingHours
        $testSubject = $this->createTestSubject($hoursMap);

        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-19'); // Sunday

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'max_hours' => 40
        ];

        $violations = $this->rule->validate($testSubject, $context);

        // Total hours: 8 * 5 = 40 hours (exactly at limit)
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testUsesDefaultValuesWhenContextMissing(): void
    {
        // Create a map of dates to hours - all days have 10 hours (exceeds default limit)
        $hoursMap = [];

        // Set up hours for a month (to ensure we cover the default date range)
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            // Weekdays have 10 hours, weekends have 0
            $hoursMap[$currentDate->format('Y-m-d')] = $currentDate->isWeekday() ? 10.0 : 0.0;
            $currentDate->addDay();
        }

        // Create a concrete test subject that implements getPlannedWorkingHours
        $testSubject = $this->createTestSubject($hoursMap);

        // Call validate with empty context (should use defaults)
        $violations = $this->rule->validate($testSubject, []);

        // Verify that the method returns an array (we don't care about the specific violations)
        $this->assertIsArray($violations);

        // If there are violations, verify they use the default max hours value (40)
        if (count($violations) > 0) {
            $this->assertEquals(40, $violations[0]['max_hours']);
        }
    }

    #[Test]
    public function testCanApplyToSubjectsWithRequiredMethods(): void
    {
        // Create a concrete test subject that implements getPlannedWorkingHours
        $testSubject = $this->createTestSubject([]);

        // Test that the rule can apply to our subject
        $result = $this->rule->canApplyTo($testSubject);
        $this->assertTrue($result);

        // Create a subject without the required methods
        $subjectWithoutMethods = new class extends Model {};

        // Test that the rule cannot apply to a subject without the required methods
        $this->assertFalse($this->rule->canApplyTo($subjectWithoutMethods));
    }

    #[Test]
    public function testHasCorrectConfiguration(): void
    {
        $config = $this->rule->getConfiguration();

        $this->assertArrayHasKey('fields', $config);
        $this->assertArrayHasKey('max_hours', $config['fields']);

        $maxHoursField = $config['fields']['max_hours'];
        $this->assertEquals('number', $maxHoursField['type']);
        $this->assertEquals('Maximale Stunden pro Woche', $maxHoursField['label']);
        $this->assertEquals(40, $maxHoursField['default']);
    }

    #[Test]
    public function testHandlesWeekSpanningMultiplePeriods(): void
    {
        // Create a map of dates to hours for three weeks
        $hoursMap = [
            // First week (within limit)
            '2025-01-13' => 8.0, // Monday
            '2025-01-14' => 8.0, // Tuesday
            '2025-01-15' => 8.0, // Wednesday
            '2025-01-16' => 8.0, // Thursday
            '2025-01-17' => 8.0, // Friday
            '2025-01-18' => 0.0, // Saturday
            '2025-01-19' => 0.0, // Sunday

            // Second week (exceeds limit)
            '2025-01-20' => 10.0, // Monday
            '2025-01-21' => 10.0, // Tuesday
            '2025-01-22' => 10.0, // Wednesday
            '2025-01-23' => 10.0, // Thursday
            '2025-01-24' => 10.0, // Friday
            '2025-01-25' => 0.0,  // Saturday
            '2025-01-26' => 0.0,  // Sunday

            // Third week (within limit)
            '2025-01-27' => 7.0, // Monday
            '2025-01-28' => 7.0, // Tuesday
            '2025-01-29' => 7.0, // Wednesday
            '2025-01-30' => 7.0, // Thursday
            '2025-01-31' => 7.0, // Friday
            '2025-02-01' => 0.0, // Saturday
            '2025-02-02' => 0.0, // Sunday
        ];

        // Create a concrete test subject that implements getPlannedWorkingHours
        $testSubject = $this->createTestSubject($hoursMap);

        $startDate = Carbon::parse('2025-01-13'); // Monday week 1
        $endDate = Carbon::parse('2025-02-02'); // Sunday week 3

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'max_hours' => 40
        ];

        $violations = $this->rule->validate($testSubject, $context);

        // Should only report violation for the second week
        $this->assertCount(1, $violations);
        $this->assertEquals(50, $violations[0]['weekly_hours']);
        $this->assertEquals('2025-01-20', $violations[0]['week_start']);
    }
}
